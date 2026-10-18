<?php

namespace Tests\Feature;

use App\Ai\Agents\DietPlanner;
use App\Ai\Agents\DishChef;
use App\Ai\Testing\FakeAi;
use App\Jobs\CreateAiDish;
use App\Services\Ai\DishIdea;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\Diet;
use App\Models\DietPlanRequest;
use App\Models\Dish;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    private FakeAi $ai;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        // Never hit a real AI provider from tests: unscripted prompts throw.
        $this->ai = FakeAi::install($this->app);
        Storage::fake('public');
    }

    public function test_root_redirects_to_a_locale(): void
    {
        // Ukrainian is the default language whatever the browser prefers.
        $this->get('/', ['Accept-Language' => 'en-US,en;q=0.9'])->assertRedirect('/uk');
        $this->get('/')->assertRedirect('/uk');
        $this->get('/uk')->assertOk()->assertSee('<link rel="alternate" hreflang="x-default" href="'.url('/uk').'">', false);
    }

    public function test_public_pages_render_in_every_locale(): void
    {
        $diet = Diet::first();
        $dish = Dish::first();

        foreach (array_keys(config('app.locales')) as $locale) {
            app()->setLocale($locale);

            foreach (['', '/diets', "/diets/{$diet->slug}", '/recipes', "/recipes/{$dish->slug}", '/ai-plan', '/about', '/contact'] as $path) {
                $this->get("/{$locale}{$path}")
                    ->assertOk()
                    ->assertSee('<html lang="'.$locale.'"', false);
            }

            $this->get("/{$locale}/recipes/{$dish->slug}")->assertSee($dish->translate($locale)->name);
        }
    }

    public function test_production_ignores_a_stray_vite_hot_file(): void
    {
        $hot = public_path('hot');
        $existed = is_file($hot);
        $existed || file_put_contents($hot, 'http://[::1]:5173');

        try {
            $this->app['env'] = 'production';
            (new \App\Providers\AppServiceProvider($this->app))->boot();

            $this->get('/en')->assertOk()
                ->assertDontSee('[::1]:5173', false)
                ->assertSee('/build/assets/', false);
        } finally {
            $existed || unlink($hot);
        }
    }

    public function test_unknown_locale_is_404(): void
    {
        $this->get('/de/diets')->assertNotFound();
    }

    public function test_recipes_can_be_filtered(): void
    {
        $this->get('/en/recipes?category=breakfast')
            ->assertOk()
            ->assertSee('Protein Pancakes')
            ->assertDontSee('Turkey Meatballs');

        $this->get('/en/recipes?diet=keto&max_kcal=500')
            ->assertOk()
            ->assertSee('Baked Salmon')
            ->assertDontSee('Steak with Roasted Vegetables');

        $this->get('/ru/recipes?q='.urlencode('лосось'))->assertOk()->assertSee('Запечённый лосось');
    }

    public function test_database_translation_overrides_lang_file(): void
    {
        \Spatie\TranslationLoader\LanguageLine::where(['group' => 'site', 'key' => 'nav.recipes'])
            ->first()
            ->update(['text' => ['ru' => 'Блюда', 'en' => 'Dishes', 'uk' => 'Страви']]);

        $this->get('/en')->assertSee('Dishes');
    }

    public function test_ai_plan_creates_new_dishes_with_recipes_and_photos(): void
    {
        $this->ai->respond(DietPlanner::class, $this->fakePlan())
            ->respond(DishChef::class, fn (DishChef $task) => $this->fakeRecipe($task->idea->title));

        $response = $this->post('/en/ai-plan', [
            'gender' => 'female', 'age' => 32, 'height_cm' => 168, 'weight_kg' => 72,
            'activity' => 'moderate', 'goal' => 'lose', 'meals_per_day' => 4,
            'preferred_diet_id' => Diet::where('slug', 'mediterranean')->value('id'),
            'allergies' => 'peanuts',
        ]);

        $plan = DietPlanRequest::sole();
        $response->assertRedirect("/en/ai-plan/{$plan->uuid}");

        // QUEUE_CONNECTION=sync in phpunit.xml, so every job has already run.
        $plan->refresh();
        $this->assertSame('completed', $plan->status);
        $this->assertSame(0, $plan->dishes_pending);

        $this->ai->assertPrompted(DietPlanner::class, fn (DietPlanner $t) => str_contains($t->input(), 'peanuts') && str_contains($t->input(), 'Mediterranean'));
        $this->ai->assertPrompted(DishChef::class, fn (DishChef $t) => str_contains($t->input(), 'peanuts'));

        // 3 distinct meals without a catalogue recipe -> 3 new dishes, the catalogue dish is only linked.
        $created = Dish::where('source', 'ai')->get();
        $this->assertCount(3, $created);
        $this->assertSame(3, $plan->dishes()->wherePivot('created', true)->count());
        $this->assertTrue($plan->dishes()->where('slug', 'salmon-quinoa-bowl')->exists());

        $dish = $created->firstWhere('slug', 'ai-oats');
        $this->assertSame('Овсянка AI', $dish->translate('ru')->name);
        $this->assertSame("Oats — 60 g\nMilk — 200 ml", $dish->translate('en')->ingredients);
        $this->assertEqualsCanonicalizing(['ru', 'en', 'uk'], $dish->translations->pluck('locale')->all());
        $this->assertTrue($dish->diets()->where('slug', 'mediterranean')->exists());
        Storage::disk('public')->assertExists($dish->image);
        $this->ai->assertImageGenerated(fn (string $prompt) => str_contains($prompt, 'A bowl of Oats'));

        $this->get("/en/ai-plan/{$plan->uuid}")
            ->assertOk()
            ->assertSee('Monday')
            ->assertSee('Salmon &amp; Quinoa Bowl', false)
            ->assertSee(route('dishes.show', ['locale' => 'en', 'dish' => 'ai-oats']))
            ->assertSee('1650');

        $this->get('/ru/recipes/ai-oats')->assertOk()->assertSee('Овсянка AI')->assertSee('Рецепт создан ИИ');
    }

    public function test_ai_failures_do_not_break_the_plan(): void
    {
        $this->withoutExceptionHandling();
        $this->ai->images(fn () => throw new \RuntimeException('You have not started a billing plan yet'))
            ->respond(DietPlanner::class, $this->fakePlan())
            ->respond(DishChef::class, fn (DishChef $task) => $task->idea->title === 'Yoghurt'
                ? throw new \RuntimeException('Overloaded')
                : $this->fakeRecipe($task->idea->title));

        $this->post('/en/ai-plan', [
            'gender' => 'male', 'age' => 40, 'height_cm' => 180, 'weight_kg' => 90,
            'activity' => 'light', 'goal' => 'lose', 'meals_per_day' => 4,
        ])->assertRedirect();

        $plan = DietPlanRequest::sole();
        $this->assertSame('completed', $plan->status);
        $this->assertSame(0, $plan->dishes_pending);                 // counter released for the failed recipe too
        $this->assertSame(2, Dish::where('source', 'ai')->count());   // 2 of 3 recipes saved
        $this->assertNull(Dish::where('source', 'ai')->first()->image); // photo failed -> placeholder
    }

    public function test_taken_name_makes_ai_create_a_different_unique_dish(): void
    {
        $recipes = [
            array_merge($this->fakeRecipe('X'), ['name' => ['ru' => 'Что-то', 'en' => 'Baked Salmon with Asparagus', 'uk' => 'Щось']]),
            $this->fakeRecipe('Lemon Cod'),
        ];
        $this->ai->respond(DishChef::class, ...$recipes);

        // The idea itself already exists in the catalogue.
        CreateAiDish::dispatch(new DishIdea('  baked SALMON with asparagus! '));

        $this->ai->assertPromptedTimes(DishChef::class, 2);
        $this->ai->assertPrompted(DishChef::class, fn (DishChef $t) => str_contains($t->input(), 'ALREADY TAKEN')
            && str_contains($t->input(), 'Baked Salmon with Asparagus'));

        $dish = Dish::where('source', 'ai')->sole();
        $this->assertSame('AI Lemon Cod', $dish->translate('en')->name);
        $this->assertSame(1, Dish::where('slug', 'baked-salmon-greens')->count());
    }

    public function test_every_plan_adds_new_dishes(): void
    {
        $n = 0;
        $this->ai->respond(DietPlanner::class, $this->fakePlan());
        $this->ai->respond(DishChef::class, function () use (&$n) {
            $n++;

            return $this->fakeRecipe("Unique {$n}");
        });

        $form = ['gender' => 'male', 'age' => 40, 'height_cm' => 180, 'weight_kg' => 90, 'activity' => 'light', 'goal' => 'lose', 'meals_per_day' => 4];
        $this->post('/en/ai-plan', $form);
        $this->post('/en/ai-plan', $form);   // same meal ideas again -> still new dishes

        $this->assertSame(6, Dish::where('source', 'ai')->count());
        $this->assertSame(6, \App\Models\DishTranslation::whereIn('dish_id', Dish::where('source', 'ai')->pluck('id'))
            ->where('locale', 'en')->distinct()->count('name'));
    }

    public function test_seed_images_are_downloaded_to_local_storage(): void
    {
        Storage::fake('public');
        Http::preventStrayRequests();
        $broken = true;
        Http::fake(function ($request) use (&$broken) {
            return $broken && str_contains($request->url(), 'photo-1546069901-ba9599a7e63c')
                ? Http::response('', 404)
                : Http::response('jpeg-bytes', 200, ['Content-Type' => 'image/jpeg']);
        });

        $this->artisan('images:localize')->assertFailed();   // one broken URL is reported, the rest stored

        $dish = Dish::where('slug', 'avocado-egg-toast')->first();
        $this->assertSame('dishes/avocado-egg-toast.jpg', $dish->image);
        Storage::disk('public')->assertExists($dish->image);
        $this->assertStringEndsWith('/storage/dishes/avocado-egg-toast.jpg', $dish->imageUrl());
        $this->assertNotNull($dish->image_url);   // source kept

        $this->assertSame('diets/keto.jpg', Diet::where('slug', 'keto')->value('image'));
        $this->assertNull(Dish::where('slug', 'salmon-quinoa-bowl')->value('image'));   // 404 -> still hotlinked

        // Second run only retries what is missing.
        $broken = false;
        $this->artisan('images:localize')->assertSuccessful();
        $this->assertSame('dishes/salmon-quinoa-bowl.jpg', Dish::where('slug', 'salmon-quinoa-bowl')->value('image'));
    }

    public function test_ai_plan_validation(): void
    {
        $this->from('/ru/ai-plan')->post('/ru/ai-plan', ['age' => 5])
            ->assertRedirect('/ru/ai-plan')
            ->assertSessionHasErrors(['gender', 'age', 'height_cm', 'weight_kg', 'goal', 'activity']);
    }

    public function test_admin_pages_load(): void
    {
        $this->actingAs(\App\Models\User::first());

        $plan = DietPlanRequest::create([
            'locale' => 'en', 'gender' => 'male', 'age' => 40, 'height_cm' => 180, 'weight_kg' => 90,
            'activity' => 'light', 'goal' => 'lose', 'status' => 'completed', 'result' => $this->fakePlan(),
        ]);

        $urls = ['/admin', '/admin/diets', '/admin/dishes', '/admin/dish-categories', '/admin/diet-plan-requests', '/admin/language-lines',
            \App\Filament\Resources\Diets\DietResource::getUrl('edit', ['record' => Diet::first()]),
            \App\Filament\Resources\Dishes\DishResource::getUrl('edit', ['record' => Dish::first()]),
            \App\Filament\Resources\DietPlanRequests\DietPlanRequestResource::getUrl('view', ['record' => $plan]),
            '/admin/language-lines/1/edit', '/admin/dishes/create'];

        foreach ($urls as $url) {
            $this->assertSame(200, $this->get($url)->status(), "Admin page {$url}");
        }
    }

    public function test_admin_saves_translations_per_locale(): void
    {
        $this->actingAs(\App\Models\User::first());
        $dish = Dish::where('slug', 'salmon-quinoa-bowl')->first();
        $edit = fn () => \Livewire\Livewire::test(\App\Filament\Resources\Dishes\Pages\EditDish::class, ['record' => $dish->getRouteKey()]);

        $edit()->assertFormSet(['ru.name' => 'Боул с лососем и киноа', 'en.name' => 'Salmon & Quinoa Bowl'])
            ->fillForm(['en.name' => 'Salmon Power Bowl', 'uk.excerpt' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $dish = $dish->fresh();
        $this->assertSame('Salmon Power Bowl', $dish->translate('en')->name);
        $this->assertSame('Боул с лососем и киноа', $dish->translate('ru')->name);
        $this->assertNull($dish->translate('uk')->excerpt);

        app()->setLocale('uk');
        $this->assertSame($dish->translate('en')->excerpt, $dish->excerpt);   // empty field falls back to "en"
        app()->setLocale('ru');

        // The default language (uk) is required.
        $edit()->fillForm(['uk.name' => ''])->call('save')->assertHasFormErrors(['uk.name' => 'required']);

        // Clearing every field of another locale removes that translation.
        $edit()->fillForm(['ru' => ['name' => '', 'excerpt' => '', 'ingredients' => '', 'instructions' => '']])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertFalse($dish->fresh()->hasTranslation('ru'));
    }

    public function test_translation_migration_round_trips_data(): void
    {
        $before = Dish::where('slug', 'pumpkin-cream-soup')->first()->translations
            ->mapWithKeys(fn ($t) => [$t->locale => $t->only(['name', 'excerpt', 'ingredients', 'instructions'])])->all();

        // down(): back to JSON columns; up(): into translation tables again.
        $path = 'database/migrations/2026_10_18_120000_move_translations_to_translation_tables.php';
        $this->artisan('migrate:rollback', ['--path' => $path])->assertSuccessful();
        $json = json_decode(\DB::table('dishes')->where('slug', 'pumpkin-cream-soup')->value('name'), true);
        $this->assertSame($before['ru']['name'], $json['ru']);

        $this->artisan('migrate', ['--path' => $path])->assertSuccessful();
        $after = Dish::where('slug', 'pumpkin-cream-soup')->first()->translations
            ->mapWithKeys(fn ($t) => [$t->locale => $t->only(['name', 'excerpt', 'ingredients', 'instructions'])])->all();

        $this->assertEquals($before, $after);
        $this->assertSame(\App\Models\Diet::count() * 3, \App\Models\DietTranslation::count());
    }

    private function fakeRecipe(string $title): array
    {
        $tr = fn (string $text) => ['ru' => "{$text} AI", 'en' => $text, 'uk' => "{$text} UA"];
        $map = ['Oats' => 'Овсянка'];

        return [
            'name' => ['ru' => ($map[$title] ?? $title).' AI', 'en' => "AI {$title}", 'uk' => "{$title} UA"],
            'excerpt' => $tr('Tasty'),
            'ingredients' => ['ru' => ['Овсянка — 60 г'], 'en' => ['Oats — 60 g', 'Milk — 200 ml'], 'uk' => ['Вівсянка — 60 г']],
            'instructions' => ['ru' => ['Сварить'], 'en' => ['Cook', 'Serve'], 'uk' => ['Зварити']],
            'category' => 'breakfast',
            'diets' => ['mediterranean', 'unknown-diet'],
            'calories' => 400, 'protein' => 20, 'fat' => 10, 'carbs' => 55,
            'prep_minutes' => 5, 'cook_minutes' => 10, 'servings' => 1,
            'image_prompt' => "A bowl of {$title}",
        ];
    }

    private function fakePlan(): array
    {
        $meal = fn ($type, $title, $kcal, $slug = '') => [
            'type' => $type, 'title' => $title, 'description' => 'Test', 'calories' => $kcal,
            'protein' => 20, 'fat' => 10, 'carbs' => 40, 'recipe_slug' => $slug,
        ];

        return [
            'summary' => 'A moderate deficit Mediterranean plan.',
            'daily_calories' => 1650, 'protein_g' => 110, 'fat_g' => 55, 'carbs_g' => 175, 'water_l' => 2.2,
            'days' => collect(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])
                ->map(fn ($day) => ['day' => $day, 'meals' => [
                    $meal('breakfast', 'Oats', 400),
                    $meal('lunch', 'Salmon bowl', 560, 'salmon-quinoa-bowl'),
                    $meal('snack', 'Yoghurt', 220),
                    $meal('dinner', 'Chicken', 470),
                ]])->all(),
            'new_dishes' => collect(['Oats' => 'breakfast', 'Yoghurt' => 'snack', 'Chicken' => 'dinner'])
                ->map(fn ($type, $title) => ['type' => $type, 'title' => $title, 'description' => 'New', 'calories' => 400, 'protein' => 20, 'fat' => 10, 'carbs' => 40])
                ->values()->all(),
            'shopping_list' => ['Salmon', 'Quinoa'],
            'tips' => ['Drink water'],
            'warning' => '',
        ];
    }
}
