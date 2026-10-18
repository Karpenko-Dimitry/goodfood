<?php

namespace App\Filament\Support;

use Closure;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class Translatable
{
    /**
     * One tab per site locale. The closure receives ($locale, $isDefault) and returns the fields of that tab,
     * e.g. "{locale}.{attribute}" for astrotomic/laravel-translatable models.
     */
    public static function tabs(Closure $fields, string $label = 'Переводы'): Tabs
    {
        // The main content language (first site locale) is the one whose fields are required.
        $default = array_key_first(config('app.locales'));

        return Tabs::make($label)
            ->tabs(collect(config('app.locales'))
                ->map(fn (string $name, string $locale) => Tab::make($name)
                    ->badge(strtoupper($locale))
                    ->schema($fields($locale, $locale === $default)))
                ->values()
                ->all())
            ->columnSpanFull();
    }
}
