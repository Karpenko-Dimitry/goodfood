<?php

namespace Tests\Feature;

use App\Ai\Agents\DietPlanner;
use App\Ai\Agents\DishChef;
use App\Jobs\CreateAiDish;
use App\Services\Ai\DishIdea;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;
use App\Models\Diet;
use App\Models\DietPlanRequest;
use App\Models\Dish;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_root_redirects_to_a_locale(): void
    {
        $this->get('/', ['Accept-Language' => 'uk-UA,uk;q=0.9'])->assertRedirect('/uk');
        $this->get('/', ['Accept-Language' => 'de-DE'])->assertRedirect('/ru');
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

            $this->get("/{$locale}/recipes/{$dish->slug}")->assertSee($dish->getTranslation('name', $locale));
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
        Storage::fake('public');
        Image::fake();
        DietPlanner::fake([$this->fakePlan()]);
        DishChef::fake(fn (string $prompt) => $this->fakeRecipe(str($prompt)->after('Dish title: ')->before("\n")->toString()));

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

        DietPlanner::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'peanuts') && str_contains($prompt->prompt, 'Mediterranean'));
        DishChef::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'peanuts'));

        // 3 distinct meals without a catalogue recipe -> 3 new dishes, the catalogue dish is only linked.
        $created = Dish::where('source', 'ai')->get();
        $this->assertCount(3, $created);
        $this->assertSame(3, $plan->dishes()->wherePivot('created', true)->count());
        $this->assertTrue($plan->dishes()->where('slug', 'salmon-quinoa-bowl')->exists());

        $dish = $created->firstWhere('slug', 'ai-oats');
        $this->assertSame('Овсянка AI', $dish->getTranslation('name', 'ru'));
        $this->assertSame("Oats — 60 g\nMilk — 200 ml", $dish->getTranslation('ingredients', 'en'));
        $this->assertTrue($dish->diets()->where('slug', 'mediterranean')->exists());
        Storage::disk('public')->assertExists($dish->image);
        Image::assertGenerated(fn ($prompt) => str_contains($prompt->prompt, 'A bowl of Oats'));

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
        Image::fake(fn () => throw new \RuntimeException('You have not started a billing plan yet'));
        DietPlanner::fake([$this->fakePlan()]);
        DishChef::fake(fn (string $prompt) => str_contains($prompt, 'Dish title: Yoghurt')
            ? throw new \RuntimeException('Overloaded')
            : $this->fakeRecipe(str($prompt)->after('Dish title: ')->before("\n")->toString()));

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

    public function test_existing_dish_is_not_generated_twice(): void
    {
        DishChef::fake()->preventStrayPrompts();

        CreateAiDish::dispatch(new DishIdea('  baked SALMON with asparagus! '));

        DishChef::assertNotPrompted(fn () => true);
        $this->assertSame(0, Dish::where('source', 'ai')->count());
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

        \Livewire\Livewire::test(\App\Filament\Resources\Dishes\Pages\EditDish::class, ['record' => $dish->getRouteKey()])
            ->fillForm(['name.en' => 'Salmon Power Bowl', 'excerpt.uk' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $dish->refresh();
        $this->assertSame('Salmon Power Bowl', $dish->getTranslation('name', 'en'));
        $this->assertSame('Боул с лососем и киноа', $dish->getTranslation('name', 'ru'));
        $this->assertArrayNotHasKey('uk', $dish->getTranslations('excerpt'));   // empty locale dropped -> falls back
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
            'shopping_list' => ['Salmon', 'Quinoa'],
            'tips' => ['Drink water'],
            'warning' => '',
        ];
    }
}
