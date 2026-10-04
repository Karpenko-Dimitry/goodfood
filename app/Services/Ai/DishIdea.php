<?php

namespace App\Services\Ai;

use App\Models\DietPlanRequest;

/**
 * A short description of a dish that does not exist yet; NutritionAi turns it into a full recipe.
 */
final readonly class DishIdea
{
    public function __construct(
        public string $title,
        public string $description = '',
        public ?string $mealType = null,
        public ?int $calories = null,
        public ?float $protein = null,
        public ?float $fat = null,
        public ?float $carbs = null,
        public ?string $dietSlug = null,
        public ?string $avoid = null,
    ) {}

    /**
     * Build from a meal of a generated plan.
     */
    public static function fromMeal(array $meal, DietPlanRequest $plan): self
    {
        return new self(
            title: $meal['title'],
            description: $meal['description'] ?? '',
            mealType: $meal['type'] ?? null,
            calories: isset($meal['calories']) ? (int) $meal['calories'] : null,
            protein: isset($meal['protein']) ? (float) $meal['protein'] : null,
            fat: isset($meal['fat']) ? (float) $meal['fat'] : null,
            carbs: isset($meal['carbs']) ? (float) $meal['carbs'] : null,
            dietSlug: $plan->preferredDiet?->slug,
            avoid: $plan->allergies,
        );
    }

    public function toPrompt(): string
    {
        return collect([
            'Dish title' => $this->title,
            'Idea' => $this->description ?: null,
            'Meal type' => $this->mealType,
            'Target per serving' => $this->calories
                ? sprintf('%d kcal, protein %sg, fat %sg, carbs %sg', $this->calories, $this->protein ?? '?', $this->fat ?? '?', $this->carbs ?? '?')
                : null,
            'Diet style' => $this->dietSlug,
            'Must NOT contain (allergies/dislikes)' => $this->avoid,
        ])->filter()->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n");
    }
}
