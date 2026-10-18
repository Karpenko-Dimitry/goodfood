<?php

namespace App\Jobs;

use App\Models\DietPlanRequest;
use App\Models\Dish;
use App\Services\Ai\DishIdea;
use App\Services\Ai\NutritionAi;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateDietPlan implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 360;

    public function __construct(public DietPlanRequest $planRequest) {}

    public function handle(NutritionAi $ai): void
    {
        $plan = $this->planRequest;
        $result = $ai->mealPlan($plan);

        $meals = collect($result['days'] ?? [])->flatMap(fn ($day) => $day['meals'] ?? []);
        $known = Dish::whereIn('slug', $meals->pluck('recipe_slug')->filter())->pluck('id', 'slug');

        // Catalogue dishes used by the plan.
        $plan->dishes()->sync($meals
            ->filter(fn ($meal) => isset($known[$meal['recipe_slug'] ?? '']))
            ->mapWithKeys(fn ($meal) => [$known[$meal['recipe_slug']] => ['meal_title' => $meal['title'], 'created' => false]])
            ->all());

        // Every plan brings new dishes into the catalogue ("new_dishes" has a fixed size in the schema).
        $ideas = collect($result['new_dishes'] ?? [])
            ->unique(fn ($dish) => Dish::normalizeTitle($dish['title']))
            ->map(fn ($dish) => DishIdea::fromMeal($dish, $plan))
            ->values();

        $plan->update([
            'status' => 'completed',
            'result' => $result,
            'error' => null,
            'dishes_pending' => $ideas->count(),
        ]);

        $ideas->each(fn (DishIdea $idea) => CreateAiDish::dispatch($idea, $plan));
    }

    public function failed(Throwable $e): void
    {
        $this->planRequest->update(['status' => 'failed', 'error' => $e->getMessage()]);
    }
}
