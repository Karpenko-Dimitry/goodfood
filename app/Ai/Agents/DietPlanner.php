<?php

namespace App\Ai\Agents;

use App\Models\DietPlanRequest;
use App\Models\Dish;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

// No temperature: Claude Opus 5.5 / Sonnet 5.5 reject sampling parameters.
#[MaxTokens(12000)]
#[Timeout(300)]
class DietPlanner implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(public DietPlanRequest $request, public int $newDishes = 6) {}

    public function instructions(): Stringable|string
    {
        $language = config('app.locales.'.$this->request->locale, 'English');

        $catalog = Dish::published()->with('category')->get()
            ->map(fn (Dish $d) => sprintf(
                '- %s | %s | %s | %d kcal, P%s F%s C%s',
                $d->slug,
                $d->getTranslation('name', 'en'),
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
        - Use recipes from the site catalogue where they fit and put their slug into "recipe_slug".
        - Also invent up to {$this->newDishes} NEW original dishes that are NOT in the catalogue (different names and concepts);
          for them set "recipe_slug" to an empty string. The site will turn them into full recipes, so give each new dish
          a specific, appetizing title and reuse the exact same title if it repeats during the week.
        - Never give medical diagnoses; add a short note recommending a doctor visit for chronic conditions, pregnancy or BMI < 18.5 / > 35.

        Site recipe catalogue (slug | name | category | nutrition per serving):
        {$catalog}
        PROMPT;
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

        return [
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
            'Preferred diet style' => $r->preferredDiet?->getTranslation('name', 'en') ?? 'no preference',
            'Meals per day' => $r->meals_per_day,
            'Food preferences' => $r->preferences ?: '—',
            'Allergies / intolerances / dislikes' => $r->allergies ?: 'none',
        ])->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n");
    }
}
