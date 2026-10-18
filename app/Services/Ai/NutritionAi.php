<?php

namespace App\Services\Ai;

use App\Ai\Agents\DietPlanner;
use App\Ai\Agents\DishChef;
use App\Ai\Contracts\ImageModel;
use App\Ai\Contracts\TextModel;
use App\Models\DietPlanRequest;
use Illuminate\Support\Facades\Storage;

/**
 * Single entry point to the AI providers (text: Claude, images: OpenAI). Knows prompts and models;
 * knows nothing about persistence of dishes — callers decide what to do with the results.
 */
class NutritionAi
{
    public function __construct(private TextModel $text, private ImageModel $images, private array $config) {}

    /**
     * A 7-day meal plan (structure defined by DietPlanner::schema()).
     */
    public function mealPlan(DietPlanRequest $request): array
    {
        return $this->text->generate(
            new DietPlanner($request, max(1, (int) $this->config['new_dishes_per_plan'])),
            $this->config['plan_model'],
        );
    }

    /**
     * A complete recipe in every site locale (structure defined by DishChef::schema()).
     *
     * @param  list<string>  $takenNames  names the model must not use (already in the database)
     */
    public function dishRecipe(DishIdea $idea, array $takenNames = []): array
    {
        return $this->text->generate(new DishChef($idea, $takenNames), $this->config['dish_model']);
    }

    public function imagesEnabled(): bool
    {
        return (bool) $this->config['images_enabled'] && $this->images->available();
    }

    /**
     * Generate a photo and store it on the public disk (storage/app/public). Returns the stored path.
     */
    public function dishImage(string $subject, string $directory = 'dishes'): string
    {
        [$content, $extension] = $this->images->generate($this->photoPrompt($subject), $this->config['image_model']);

        $path = $directory.'/'.uniqid('ai-', true).'.'.$extension;
        Storage::disk('public')->put($path, $content);

        return $path;
    }

    public function photoPrompt(string $subject): string
    {
        return "Professional overhead food photography of {$subject}. Natural daylight, rustic wooden table, "
            .'fresh herbs, shallow depth of field, appetizing, realistic, no text, no people.';
    }
}
