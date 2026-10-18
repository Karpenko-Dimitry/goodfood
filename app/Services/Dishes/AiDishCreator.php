<?php

namespace App\Services\Dishes;

use App\Jobs\GenerateDishImage;
use App\Models\Diet;
use App\Models\Dish;
use App\Models\DishCategory;
use App\Services\Ai\DishIdea;
use App\Services\Ai\NutritionAi;
use App\Support\Translations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns a DishIdea into a NEW stored Dish: asks NutritionAi for the recipe (retrying while the
 * name is taken), saves it in every locale and queues the photo.
 */
class AiDishCreator
{
    public function __construct(private NutritionAi $ai) {}

    private const ATTEMPTS = 3;

    /**
     * Always stores a NEW dish. If the idea or the generated name already exists,
     * the model is asked again with the taken names until the dish is unique.
     *
     * @throws DuplicateDishException when no unique dish was produced after several attempts
     */
    public function create(DishIdea $idea): Dish
    {
        $taken = Dish::findByTitle($idea->title) ? [$idea->title] : [];

        for ($attempt = 1; $attempt <= self::ATTEMPTS; $attempt++) {
            $recipe = $this->ai->dishRecipe($idea, $taken);

            // Final check + insert under a lock so parallel jobs can't store the same dish twice.
            $dish = Cache::lock('ai-dish-creator', 30)->block(30, fn () => $this->hasTakenName($recipe)
                ? null
                : $this->store($recipe));

            if ($dish) {
                if ($this->ai->imagesEnabled()) {
                    GenerateDishImage::dispatch($dish);
                }

                return $dish;
            }

            $taken = array_values(array_unique([...$taken, ...array_values($recipe['name'])]));
        }

        throw new DuplicateDishException("Could not generate a unique dish for \"{$idea->title}\".");
    }

    private function hasTakenName(array $recipe): bool
    {
        return collect($recipe['name'])->contains(fn ($name) => Dish::findByTitle((string) $name) !== null);
    }

    private function store(array $recipe): Dish
    {
        return DB::transaction(function () use ($recipe) {
            $dish = Dish::create(Translations::byLocale([
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
            ], (new Dish)->translatedAttributes));

            $dish->diets()->sync(Diet::whereIn('slug', $recipe['diets'])->pluck('id'));

            return $dish;
        });
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
