<?php

namespace App\Services\Ai;

use App\Ai\Agents\DietPlanner;
use App\Ai\Agents\DishChef;
use App\Models\DietPlanRequest;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Image;

/**
 * Single entry point to the AI provider. Knows prompts, models and providers;
 * knows nothing about persistence — callers decide what to do with the results.
 */
class NutritionAi
{
    public function __construct(private array $config) {}

    /**
     * A 7-day meal plan (structure defined by DietPlanner::schema()).
     */
    public function mealPlan(DietPlanRequest $request): array
    {
        $agent = new DietPlanner($request, (int) $this->config['new_dishes_per_plan']);

        return $this->structured($agent, $agent->brief(), $this->config['plan_model']);
    }

    /**
     * A complete recipe in every site locale (structure defined by DishChef::schema()).
     */
    public function dishRecipe(DishIdea $idea): array
    {
        return $this->structured(new DishChef, $idea->toPrompt(), $this->config['dish_model']);
    }

    public function imagesEnabled(): bool
    {
        return (bool) $this->config['images_enabled'];
    }

    /**
     * Generate a photo and store it on the public disk. Returns the stored path.
     */
    public function dishImage(string $prompt, string $directory = 'dishes'): string
    {
        return Image::of($this->photoPrompt($prompt))
            ->landscape()
            ->generate(provider: $this->config['image_provider'], model: $this->config['image_model'])
            ->storePublicly($directory, 'public');
    }

    public function photoPrompt(string $subject): string
    {
        return "Professional overhead food photography of {$subject}. Natural daylight, rustic wooden table, "
            .'fresh herbs, shallow depth of field, appetizing, realistic, no text, no people.';
    }

    private function structured(Agent $agent, string $prompt, ?string $model): array
    {
        return $agent->prompt($prompt, provider: $this->config['text_provider'], model: $model)->toArray();
    }
}
