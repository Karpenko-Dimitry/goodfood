<?php

namespace App\Filament\Support;

use Closure;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class Translatable
{
    /**
     * One tab per site locale. The closure receives ($locale, $isDefault) and returns
     * fields named "{attribute}.{locale}" — spatie/laravel-translatable stores them as JSON.
     */
    public static function tabs(Closure $fields, string $label = 'Переводы'): Tabs
    {
        $default = config('app.locale');

        return Tabs::make($label)
            ->tabs(collect(config('app.locales'))
                ->map(fn (string $name, string $locale) => Tab::make($name)
                    ->badge(strtoupper($locale))
                    ->schema($fields($locale, $locale === $default)))
                ->values()
                ->all())
            ->columnSpanFull();
    }

    /**
     * Drop empty locales so the model falls back to the fallback locale instead of "".
     */
    public static function clean(array $data, array $attributes): array
    {
        foreach ($attributes as $attribute) {
            if (is_array($data[$attribute] ?? null)) {
                $data[$attribute] = array_filter($data[$attribute], fn ($v) => filled($v));
            }
        }

        return $data;
    }
}
