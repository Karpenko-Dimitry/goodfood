# {!! config('app.name') !!}

> {!! config('app.name') !!} is a nutrition website with a collection of popular diets (principles, pros and cons, allowed and forbidden foods, calorie and macro ranges), healthy recipes with exact calories, protein, fat and carbs per serving, and a free AI tool that builds a personal 7-day meal plan.

Content is available in Ukrainian (default), Russian and English: replace `/en/` in any URL with `/uk/` or `/ru/`. Nutrition values are per serving; calories are computed with 4/9/4 kcal per gram of protein/fat/carbs. Content is informational and not medical advice.

## Tools

- [Personal AI meal plan]({!! route('plan.create', ['locale' => 'en']) !!}): enter sex, age, height, weight, activity, goal and allergies; get calorie and macro targets (Mifflin–St Jeor), a 7-day menu with recipes and a shopping list.

## Diets

@foreach ($diets as $diet)
@php
    $line = "- [{$diet->name}](".route('diets.show', ['locale' => 'en', 'diet' => $diet])."): {$diet->excerpt}";
    if ($diet->calories_min) {
        $line .= " Typical intake {$diet->calories_min}–{$diet->calories_max} kcal/day.";
    }
    if ($diet->protein_pct) {
        $line .= " Macros: protein {$diet->protein_pct}%, fat {$diet->fat_pct}%, carbs {$diet->carbs_pct}%.";
    }
@endphp
{!! $line !!}
@endforeach

## Recipes

@foreach ($dishes as $dish)
@php
    $parts = array_filter([
        $dish->category?->name,
        "{$dish->calories} kcal, protein {$dish->protein} g, fat {$dish->fat} g, carbs {$dish->carbs} g per serving",
        $dish->totalMinutes() ? $dish->totalMinutes().' min' : null,
        $dish->diets->isNotEmpty() ? 'suits: '.$dish->diets->pluck('name')->implode(', ') : null,
    ]);
@endphp
{!! "- [{$dish->name}](".route('dishes.show', ['locale' => 'en', 'dish' => $dish])."): ".implode('; ', $parts).'.' !!}
@endforeach

## Optional

- [All diets]({{ route('diets.index', ['locale' => 'en']) }})
- [All recipes]({{ route('dishes.index', ['locale' => 'en']) }})
- [About]({{ route('about', ['locale' => 'en']) }})
- [Sitemap]({{ url('/sitemap.xml') }})
