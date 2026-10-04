<?php

namespace App\Services\Dishes;

use App\Jobs\GenerateDishImage;
use App\Models\Diet;
use App\Models\Dish;
use App\Models\DishCategory;
use App\Services\Ai\DishIdea;
use App\Services\Ai\NutritionAi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns a DishIdea into a stored Dish: skips duplicates, asks NutritionAi for the recipe,
 * saves it in every locale and queues the photo.
 */
class AiDishCreator
{
    public function __construct(private NutritionAi $ai) {}

    /**
     * @return array{0: Dish, 1: bool} the dish and whether it was newly created
     */
    public function create(DishIdea $idea): array
    {
        if ($existing = Dish::findByTitle($idea->title)) {
            return [$existing, false];
        }

        $recipe = $this->ai->dishRecipe($idea);

        // The model may still return a name that already exists in another locale.
        foreach ($recipe['name'] as $name) {
            if ($existing = Dish::findByTitle($name)) {
                return [$existing, false];
            }
        }

        $dish = DB::transaction(function () use ($recipe) {
            $dish = Dish::create([
                'slug' => $this->uniqueSlug($recipe['name']['en'] ?? reset($recipe['name'])),
                'dish_category_id' => DishCategory::where('slug', $recipe['category'])->value('id'),
                'name' => $recipe['name'],
                'excerpt' => $recipe['excerpt'],
                'ingredients' => $this->joinLines($recipe['ingredients']),
                'instructions' => $this->joinLines($recipe['instructions']),
                'calories' => (int) $recipe['calories'],
                'protein' => round((float) $recipe['protein'], 1),
                'fat' => round((float) $recipe['fat'], 1),
                'carbs' => round((float) $recipe['carbs'], 1),
                'prep_minutes' => (int) $recipe['prep_minutes'],
                'cook_minutes' => (int) $recipe['cook_minutes'],
                'servings' => max(1, (int) $recipe['servings']),
                'image_prompt' => $recipe['image_prompt'],
                'is_published' => (bool) config('services.ai.publish_new_dishes'),
                'source' => 'ai',
            ]);

            $dish->diets()->sync(Diet::whereIn('slug', $recipe['diets'])->pluck('id'));

            return $dish;
        });

        if ($this->ai->imagesEnabled()) {
            GenerateDishImage::dispatch($dish);
        }

        return [$dish, true];
    }

    /**
     * ['ru' => ['a', 'b'], ...] -> ['ru' => "a\nb", ...] ("one item per line" storage format).
     */
    private function joinLines(array $byLocale): array
    {
        return array_map(fn (array $lines) => implode("\n", array_map('trim', $lines)), $byLocale);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'dish';
        $slug = $base;

        for ($i = 2; Dish::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
