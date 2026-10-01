<?php

namespace Tests\Feature;

use App\Ai\Agents\DietPlanner;
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

    public function test_ai_plan_is_generated_with_structured_output(): void
    {
        DietPlanner::fake([$this->fakePlan()]);

        $response = $this->post('/en/ai-plan', [
            'gender' => 'female', 'age' => 32, 'height_cm' => 168, 'weight_kg' => 72,
            'activity' => 'moderate', 'goal' => 'lose', 'meals_per_day' => 4,
            'preferred_diet_id' => Diet::where('slug', 'mediterranean')->value('id'),
            'allergies' => 'peanuts',
        ]);

        $plan = DietPlanRequest::sole();
        $response->assertRedirect("/en/ai-plan/{$plan->uuid}");

        // QUEUE_CONNECTION=sync in phpunit.xml, so the job has already run.
        $this->assertSame('completed', $plan->fresh()->status);

        DietPlanner::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'peanuts') && str_contains($prompt->prompt, 'Mediterranean'));

        $this->get("/en/ai-plan/{$plan->uuid}")
            ->assertOk()
            ->assertSee('Monday')
            ->assertSee('Salmon &amp; Quinoa Bowl', false)   // linked site recipe
            ->assertSee('1650');
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
