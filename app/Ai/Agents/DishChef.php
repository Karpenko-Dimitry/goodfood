<?php

namespace App\Ai\Agents;

use App\Models\Diet;
use App\Models\Dish;
use App\Models\DishCategory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Writes one complete, original recipe in every site locale.
 */
#[MaxTokens(6000)]
#[Timeout(180)]
class DishChef implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        $languages = collect(config('app.locales'))->map(fn ($name, $code) => "{$code} = {$name}")->implode(', ');
        $existing = Dish::query()->get(['name'])->map(fn (Dish $d) => $d->getTranslation('name', 'en'))->implode('; ');

        return <<<PROMPT
        You are a recipe developer and nutritionist for the DietCoach website.
        Write ONE original, realistic, home-cookable recipe based on the brief.

        Rules:
        - Provide every text field in all site languages: {$languages}. Translations must be natural, not literal.
        - The dish must be new: its name and concept must differ from these existing site dishes: {$existing}.
        - Ingredients: one item per array element, with amounts in grams/ml/pieces, e.g. "Chicken breast — 150 g".
        - Instructions: 3–7 clear steps, one step per array element, without numbering.
        - Nutrition is PER SERVING and must be consistent: calories ≈ protein×4 + fat×9 + carbs×4 (±5%).
        - Respect the target nutrition and any allergies in the brief strictly.
        - "image_prompt": one English sentence describing how the finished dish looks on the plate, for a photographer.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        $text = fn () => $schema->object(
            collect(config('app.locales'))->map(fn () => $schema->string()->required())->all()
        )->withoutAdditionalProperties()->required();

        $list = fn () => $schema->object(
            collect(config('app.locales'))->map(fn () => $schema->array()->items($schema->string())->required())->all()
        )->withoutAdditionalProperties()->required();

        return [
            'name' => $text(),
            'excerpt' => $text(),
            'ingredients' => $list(),
            'instructions' => $list(),
            'category' => $schema->string()->enum(DishCategory::pluck('slug')->all())->required(),
            'diets' => $schema->array()->items($schema->string()->enum(Diet::pluck('slug')->all()))->required(),
            'calories' => $schema->integer()->required(),
            'protein' => $schema->number()->required(),
            'fat' => $schema->number()->required(),
            'carbs' => $schema->number()->required(),
            'prep_minutes' => $schema->integer()->required(),
            'cook_minutes' => $schema->integer()->required(),
            'servings' => $schema->integer()->required(),
            'image_prompt' => $schema->string()->required(),
        ];
    }
}
