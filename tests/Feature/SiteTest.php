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
        // The AI photo became presets (App\FileConfigs\DishImageFileConfig); the raw file is gone.
        $this->assertNull($dish->image);
        $this->assertEqualsCanonicalizing(['thumb', 'sm', 'md', 'lg', 'original'], $dish->imageObject->files->pluck('key')->all());
        $this->assertSame('webp', $dish->imageObject->findFile('sm')->ext);
        $this->assertSame([480, 360], $dish->imageDimensions('sm'));
        Storage::disk('public')->assertExists($dish->imageObject->findFile('md')->path);
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

    public function test_seed_images_are_stored_as_presets(): void
    {
        Http::preventStrayRequests();
        $broken = true;
        $jpeg = self::jpeg();
        Http::fake(function ($request) use (&$broken, $jpeg) {
            return $broken && str_contains($request->url(), 'photo-1546069901-ba9599a7e63c')
                ? Http::response('', 404)
                : Http::response($jpeg, 200, ['Content-Type' => 'image/jpeg']);
        });

        $this->artisan('images:localize')->assertFailed();   // one broken URL is reported, the rest stored

        $dish = Dish::where('slug', 'avocado-egg-toast')->first();
        $this->assertStringContainsString('/storage/assets/files/', $dish->imageUrl('md'));
        $this->assertStringEndsWith('.webp', $dish->imageUrl('md'));
        $this->assertMatchesRegularExpression('~\.webp 480w, .+\.webp 960w, .+\.webp 1440w$~', $dish->imageSrcset());
        $this->assertNotNull($dish->image_url);   // source kept

        $this->assertSame([1600, 1067], Diet::where('slug', 'keto')->first()->imageDimensions('lg'));

        // 404 -> no presets yet, still served from Unsplash at a reduced size.
        $broken404 = Dish::where('slug', 'salmon-quinoa-bowl')->first();
        $this->assertNull($broken404->imageObject);
        $this->assertStringContainsString('w=1280', $broken404->imageUrl());

        // Second run only processes what is missing.
        $broken = false;
        $this->artisan('images:localize')->assertSuccessful();
        $this->assertNotNull($broken404->fresh()->imageObject);
        $this->assertSame(Diet::count() + Dish::count(), \App\Services\FileService\Models\FileObject::count());
    }

    public function test_uploaded_image_is_converted_to_presets_and_replaced(): void
    {
        $dish = Dish::where('slug', 'pumpkin-cream-soup')->first();
        $disk = Storage::disk('public');

        $disk->put('uploads/dishes/first.jpg', self::jpeg());
        $dish->update(['image' => 'uploads/dishes/first.jpg']);   // what the admin FileUpload / AI photo does

        $first = $dish->fresh()->imageObject;
        $this->assertNotNull($first);
        $this->assertNull($dish->fresh()->image);
        $disk->assertMissing('uploads/dishes/first.jpg');
        $oldPaths = $first->files->pluck('path');

        $disk->put('uploads/dishes/second.jpg', self::jpeg());
        $dish->update(['image' => 'uploads/dishes/second.jpg']);

        $this->assertNotSame($first->id, $dish->fresh()->imageObject->id);
        $this->assertSame(1, $dish->fileObjects()->count());
        $oldPaths->each(fn ($path) => $disk->assertMissing($path));   // old presets deleted

        $html = $this->get('/uk/recipes/pumpkin-cream-soup')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('~<img src="[^"]+\.webp"\s+srcset="[^"]+ 480w, [^"]+ 960w, [^"]+ 1440w"\s+sizes="\(min-width: 1024px\) 50vw, 100vw"\s+width="960" height="720"~', $html);
        $this->assertStringContainsString('fetchpriority="high"', $html);

        $paths = $dish->fresh()->imageObject->files->pluck('path');
        $dish->delete();
        $this->assertSame(0, \App\Services\FileService\Models\FileObject::count());
        $paths->each(fn ($path) => $disk->assertMissing($path));
    }

    public function test_home_hero_is_responsive_and_prioritized(): void
    {
        $html = $this->get('/uk')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~<img src="https://images\.unsplash\.com/photo-1490645935967-10de6ba17061\?w=1280[^"]*"\s+srcset="[^"]*w=640[^"]* 640w,[^"]* 1920w"\s+sizes="100vw"[^>]*loading="eager" fetchpriority="high"~', $html);
    }

    private static function jpeg(int $width = 800, int $height = 600): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 120, 40));
        ob_start();
        imagejpeg($image, null, 80);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    public function test_ai_prompts_list_existing_dishes(): void
    {
        $plan = DietPlanRequest::create([
            'locale' => 'uk', 'gender' => 'female', 'age' => 30, 'height_cm' => 165, 'weight_kg' => 60,
            'activity' => 'light', 'goal' => 'maintain',
        ]);

        $planner = (new DietPlanner($plan))->instructions();
        $this->assertStringContainsString('Existing dish names (do not reuse for new dishes):', $planner);
        $this->assertStringContainsString('Salmon & Quinoa Bowl', $planner);
        $this->assertStringContainsString('salmon-quinoa-bowl | Salmon & Quinoa Bowl | lunch', $planner);

        $chef = (new DishChef(new DishIdea('Lentil salad')))->instructions();
        $this->assertStringContainsString('Baked Salmon with Asparagus', $chef);

        $this->assertCount(Dish::count(), Dish::recentNames());
    }

    public function test_claude_misconfiguration_gives_a_clear_error(): void
    {
        $task = new DishChef(new DishIdea('Lentil salad'));
        $claude = fn (?string $key) => new \App\Ai\Providers\ClaudeTextModel(new \Anthropic\Client(apiKey: $key ?? 'unused'), null, $key);

        try {
            $claude(null)->generate($task, 'claude-opus-5-5');
            $this->fail('Expected an exception');
        } catch (\App\Ai\AiResponseException $e) {
            $this->assertStringContainsString('ANTHROPIC_API_KEY is not set', $e->getMessage());
        }

        $this->expectExceptionMessage('"gpt-4.1-mini" is not a Claude model');
        $claude('sk-ant-test')->generate($task, 'gpt-4.1-mini');
    }

    public function test_openai_is_the_default_text_provider_with_strict_json_schema(): void
    {
        // setUp() swapped the providers for FakeAi: restore the real bindings to check the default.
        $this->app->offsetUnset(\App\Ai\Contracts\TextModel::class);
        (new \App\Providers\AppServiceProvider($this->app))->register();
        $this->assertSame('openai', config('services.ai.text_provider'));
        $this->assertInstanceOf(\App\Ai\Providers\OpenAiTextModel::class, $this->app->make(\App\Ai\Contracts\TextModel::class));

        $recipe = $this->fakeRecipe('Lentil salad');
        $client = new \OpenAI\Testing\ClientFake([
            \OpenAI\Responses\Chat\CreateResponse::fake(['choices' => [['index' => 0, 'finish_reason' => 'stop',
                'message' => ['role' => 'assistant', 'content' => json_encode($recipe)]]]]),
            \OpenAI\Responses\Chat\CreateResponse::fake(['choices' => [['index' => 0, 'finish_reason' => 'length',
                'message' => ['role' => 'assistant', 'content' => '{"name":']]]]),
        ]);
        $openai = new \App\Ai\Providers\OpenAiTextModel($client, 'sk-test');
        $task = new DishChef(new DishIdea('Lentil salad'));

        $this->assertSame($recipe, $openai->generate($task, 'gpt-4.1-mini'));

        $client->chat()->assertSent(function (string $method, array $params) {
            $schema = $params['response_format']['json_schema'];

            return $method === 'create'
                && $params['model'] === 'gpt-4.1-mini'
                && $params['messages'][0]['role'] === 'system'
                && str_contains($params['messages'][1]['content'], 'Dish title: Lentil salad')
                && $schema['strict'] === true
                && $schema['name'] === 'dish_chef'
                && $schema['schema']['additionalProperties'] === false
                && in_array('ingredients', $schema['schema']['required'], true);
        });

        $this->expectExceptionMessage('Output hit max_completion_tokens');
        $openai->generate($task, 'gpt-4.1-mini');
    }

    public function test_openai_misconfiguration_gives_a_clear_error(): void
    {
        $task = new DishChef(new DishIdea('Lentil salad'));

        try {
            (new \App\Ai\Providers\OpenAiTextModel(new \OpenAI\Testing\ClientFake, null))->generate($task, 'gpt-4.1-mini');
            $this->fail('Expected an exception');
        } catch (\App\Ai\AiResponseException $e) {
            $this->assertStringContainsString('OPENAI_API_KEY is not set', $e->getMessage());
        }

        $this->expectExceptionMessage('"claude-opus-5-5" is not an OpenAI model');
        (new \App\Ai\Providers\OpenAiTextModel(new \OpenAI\Testing\ClientFake, 'sk-test'))->generate($task, 'claude-opus-5-5');
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
