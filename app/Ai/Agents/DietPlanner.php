<?php

namespace App\Ai\Agents;

use App\Ai\Contracts\StructuredTask;
use App\Models\DietPlanRequest;
use App\Models\Dish;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class DietPlanner implements StructuredTask
{
    public function __construct(public DietPlanRequest $request, public int $newDishes = 6) {}

    public function maxTokens(): int
    {
        return 16000;
    }

    public function input(): string
    {
        return $this->brief();
    }

    public function instructions(): string
    {
        $language = config('app.locales.'.$this->request->locale, 'English');

        $catalog = Dish::published()->with('category')->get()
            ->map(fn (Dish $d) => sprintf(
                '- %s | %s | %s | %d kcal, P%s F%s C%s',
                $d->slug,
                $d->translate('en', true)?->name,
                $d->category?->slug ?? '-',
                $d->calories, $d->protein, $d->fat, $d->carbs,
            ))->implode("\n");

        return <<<PROMPT
        You are a certified dietitian and nutrition coach working for the DietCoach website.
        Build a safe, realistic and tasty personalised 7-day meal plan for the client.

        Rules:
        - Write ALL human-readable text strictly in {$language}.
        - Respect allergies and dislikes absolutely. Never include allergens.
        - Keep daily calories within ±5% of the target and hit the macro targets you state.
        - Use common, affordable foods. Give gram amounts where useful.
        - Put EXACTLY {$this->newDishes} brand-new original dishes into "new_dishes". Each must be a dish the site does not have yet:
          its title and concept must differ from every catalogue recipe and every name in "Existing dish names" below,
          and from each other. Make titles specific and appetizing.
        - Use every new dish in at least one meal with exactly the same title and "recipe_slug" = "".
        - For the remaining meals use catalogue recipes where they fit (put their slug into "recipe_slug"),
          otherwise simple meals with "recipe_slug" = "".
        - Never give medical diagnoses; add a short note recommending a doctor visit for chronic conditions, pregnancy or BMI < 18.5 / > 35.

        Site recipe catalogue (slug | name | category | nutrition per serving):
        {$catalog}

        Existing dish names (do not reuse for new dishes):
        {$this->existingNames()}
        PROMPT;
    }

    /**
     * Names of all dishes (incl. unpublished AI drafts), newest first, capped to keep the prompt small.
     * AiDishCreator re-checks uniqueness against the whole table anyway.
     */
    private function existingNames(): string
    {
        return Dish::recentNames()->implode('; ');
    }

    public function schema(JsonSchema $schema): array
    {
        $meal = $schema->object([
            'type' => $schema->string()->enum(['breakfast', 'snack', 'lunch', 'dinner'])->required(),
            'title' => $schema->string()->required(),
            'description' => $schema->string()->description('Ingredients with grams and a one-line preparation hint.')->required(),
            'calories' => $schema->integer()->required(),
            'protein' => $schema->number()->required(),
            'fat' => $schema->number()->required(),
            'carbs' => $schema->number()->required(),
            'recipe_slug' => $schema->string()->required(),
        ])->withoutAdditionalProperties();

        $newDish = $schema->object([
            'type' => $schema->string()->enum(['breakfast', 'snack', 'lunch', 'dinner'])->required(),
            'title' => $schema->string()->required(),
            'description' => $schema->string()->description('Concept, main ingredients and cooking method in one or two sentences.')->required(),
            'calories' => $schema->integer()->required(),
            'protein' => $schema->number()->required(),
            'fat' => $schema->number()->required(),
            'carbs' => $schema->number()->required(),
        ])->withoutAdditionalProperties();

        return [
            'new_dishes' => $schema->array()->items($newDish)
                ->description("Exactly {$this->newDishes} brand-new dishes.")->required(),
            'summary' => $schema->string()->description('2–4 sentence overview of the strategy.')->required(),
            'daily_calories' => $schema->integer()->required(),
            'protein_g' => $schema->integer()->required(),
            'fat_g' => $schema->integer()->required(),
            'carbs_g' => $schema->integer()->required(),
            'water_l' => $schema->number()->required(),
            'days' => $schema->array()->min(7)->max(7)->items(
                $schema->object([
                    'day' => $schema->string()->description('Day name, e.g. Monday.')->required(),
                    'meals' => $schema->array()->items($meal)->required(),
                ])->withoutAdditionalProperties()
            )->required(),
            'shopping_list' => $schema->array()->items($schema->string())->required(),
            'tips' => $schema->array()->items($schema->string())->required(),
            'warning' => $schema->string()->description('Medical caution or empty string.')->required(),
        ];
    }

    /**
     * The user prompt describing the client.
     */
    public function brief(): string
    {
        $r = $this->request;

        return collect([
            'Gender' => $r->gender,
            'Age' => $r->age,
            'Height (cm)' => $r->height_cm,
            'Weight (kg)' => $r->weight_kg,
            'Target weight (kg)' => $r->target_weight_kg ?: 'not set',
            'BMI' => $r->bmi(),
            'Activity level' => $r->activity,
            'Goal' => $r->goal,
            'Calculated calorie target (Mifflin–St Jeor)' => $r->targetCalories().' kcal',
            'Preferred diet style' => $r->preferredDiet?->translate('en', true)?->name ?? 'no preference',
            'Meals per day' => $r->meals_per_day,
            'Food preferences' => $r->preferences ?: '—',
            'Allergies / intolerances / dislikes' => $r->allergies ?: 'none',
        ])->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n");
    }
}
