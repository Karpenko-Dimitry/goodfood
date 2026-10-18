<?php

namespace App\Support;

use App\Models\Diet;
use App\Models\Dish;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;

/**
 * schema.org JSON-LD for search engines and AI assistants (rendered by <x-json-ld>).
 * Validate with https://search.google.com/test/rich-results.
 */
class StructuredData
{
    /** Site-wide graph: the organization and the website with recipe search. */
    public static function site(): array
    {
        $home = route('home');

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => url('/#organization'),
                    'name' => config('app.name'),
                    'url' => $home,
                    'email' => 'hello@dietcoach.test',
                    'description' => __('site.meta.description'),
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => url('/#website'),
                    'name' => config('app.name'),
                    'url' => $home,
                    'inLanguage' => app()->getLocale(),
                    'publisher' => ['@id' => url('/#organization')],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('dishes.index').'?q={search_term_string}'],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ];
    }

    public static function recipe(Dish $dish): array
    {
        $minutes = fn (?int $m) => $m ? 'PT'.$m.'M' : null;

        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'Recipe',
            'name' => $dish->name,
            'description' => $dish->excerpt,
            'image' => self::images($dish),
            'url' => route('dishes.show', $dish),
            'inLanguage' => app()->getLocale(),
            'author' => ['@id' => url('/#organization')],
            'publisher' => ['@id' => url('/#organization')],
            'datePublished' => $dish->created_at?->toDateString(),
            'dateModified' => $dish->updated_at?->toDateString(),
            'prepTime' => $minutes($dish->prep_minutes),
            'cookTime' => $minutes($dish->cook_minutes),
            'totalTime' => $minutes($dish->totalMinutes() ?: null),
            'recipeYield' => (string) $dish->servings,
            'recipeCategory' => $dish->category?->name,
            'keywords' => $dish->diets->pluck('name')->implode(', '),
            'suitableForDiet' => self::restrictedDiets($dish->diets),
            'recipeIngredient' => $dish->lines('ingredients'),
            'recipeInstructions' => collect($dish->lines('instructions'))
                ->values()
                ->map(fn (string $step, int $i) => [
                    '@type' => 'HowToStep',
                    'position' => $i + 1,
                    'text' => $step,
                ])->all(),
            'nutrition' => [
                '@type' => 'NutritionInformation',
                'servingSize' => '1 '.__('site.recipes.serving'),
                'calories' => $dish->calories.' kcal',
                'proteinContent' => $dish->protein.' g',
                'fatContent' => $dish->fat.' g',
                'carbohydrateContent' => $dish->carbs.' g',
            ],
        ]);
    }

    public static function dietArticle(Diet $diet): array
    {
        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $diet->name,
            'description' => $diet->excerpt,
            'image' => self::images($diet),
            'url' => route('diets.show', $diet),
            'mainEntityOfPage' => route('diets.show', $diet),
            'inLanguage' => app()->getLocale(),
            'author' => ['@id' => url('/#organization')],
            'publisher' => ['@id' => url('/#organization')],
            'datePublished' => $diet->created_at?->toIso8601String(),
            'dateModified' => $diet->updated_at?->toIso8601String(),
            'about' => ['@type' => 'Thing', 'name' => $diet->name],
        ]);
    }

    /**
     * @param  array<string, string>  $crumbs  label => url, the last one is the current page
     */
    public static function breadcrumbs(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)->keys()->values()->map(fn (string $name, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $name,
                'item' => $crumbs[$name],
            ])->all(),
        ];
    }

    /**
     * @param  iterable<Diet|Dish>  $items
     */
    public static function itemList(iterable $items, string $route): array
    {
        // Paginators: list the current page, numbered from its first item (page 2 starts at 13, …).
        $offset = $items instanceof AbstractPaginator ? max(0, ($items->firstItem() ?? 1) - 1) : 0;
        $items = $items instanceof AbstractPaginator ? $items->items() : $items;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => collect($items)->values()->map(fn ($item, int $i) => [
                '@type' => 'ListItem',
                'position' => $offset + $i + 1,
                'url' => route($route, $item),
                'name' => $item->name,
            ])->all(),
        ];
    }

    /** Several sizes help Google pick the best one for rich results. */
    private static function images(Diet|Dish $model): array
    {
        return collect(['lg', 'md', 'sm'])
            ->map(fn (string $preset) => $model->imageUrl($preset))
            ->filter()->unique()->values()->all();
    }

    /** schema.org RestrictedDiet values that correspond to our diets. */
    private static function restrictedDiets(Collection $diets): array
    {
        $map = ['vegan' => 'VeganDiet', 'dash' => 'LowSaltDiet'];

        return $diets->pluck('slug')
            ->map(fn (string $slug) => isset($map[$slug]) ? 'https://schema.org/'.$map[$slug] : null)
            ->filter()->unique()->values()->all();
    }

    /** Drop empty values so validators do not complain about blank properties. */
    private static function clean(array $data): array
    {
        return array_filter($data, fn ($value) => $value !== null && $value !== '' && $value !== []);
    }
}
