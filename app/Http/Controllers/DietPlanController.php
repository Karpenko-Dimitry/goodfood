<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateDietPlan;
use App\Models\Diet;
use App\Models\DietPlanRequest;
use App\Models\Dish;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DietPlanController extends Controller
{
    public function create(Request $request): View
    {
        return view('plan.create', [
            'diets' => Diet::published()->get(),
            'selectedDiet' => $request->query('diet'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'age' => ['required', 'integer', 'between:14,99'],
            'height_cm' => ['required', 'integer', 'between:120,230'],
            'weight_kg' => ['required', 'numeric', 'between:30,300'],
            'target_weight_kg' => ['nullable', 'numeric', 'between:30,300'],
            'activity' => ['required', Rule::in(DietPlanRequest::ACTIVITIES)],
            'goal' => ['required', Rule::in(DietPlanRequest::GOALS)],
            'preferred_diet_id' => ['nullable', 'exists:diets,id'],
            'meals_per_day' => ['required', 'integer', 'between:3,6'],
            'preferences' => ['nullable', 'string', 'max:1000'],
            'allergies' => ['nullable', 'string', 'max:1000'],
        ]);

        $planRequest = DietPlanRequest::create($data + [
            'locale' => app()->getLocale(),
            'ip' => $request->ip(),
        ]);

        GenerateDietPlan::dispatch($planRequest);

        return redirect()->route('plan.show', $planRequest);
    }

    public function show(DietPlanRequest $planRequest): View
    {
        $slugs = collect($planRequest->result['days'] ?? [])
            ->flatMap(fn ($day) => array_column($day['meals'] ?? [], 'recipe_slug'))
            ->filter()->unique();

        return view('plan.show', [
            'plan' => $planRequest,
            'recipes' => Dish::published()->whereIn('slug', $slugs)->get()->keyBy('slug'),
            // Dishes generated for (or matched to) this plan, keyed by normalized meal title.
            'planDishes' => $planRequest->dishes()->published()->get()
                ->keyBy(fn (Dish $dish) => Dish::normalizeTitle($dish->pivot->meal_title)),
        ]);
    }

    public function status(DietPlanRequest $planRequest): JsonResponse
    {
        return response()->json([
            'status' => $planRequest->status,
            'dishes_pending' => $planRequest->dishes_pending,
        ]);
    }
}
