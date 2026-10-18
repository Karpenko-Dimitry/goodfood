<?php

namespace App\Support;

/**
 * Helpers for astrotomic/laravel-translatable payloads.
 */
class Translations
{
    /**
     * ['name' => ['ru' => 'A', 'en' => 'B'], 'slug' => 'x'] -> ['ru' => ['name' => 'A'], 'en' => ['name' => 'B'], 'slug' => 'x']
     * (the per-locale shape Translatable::fill() accepts). Non-translated keys are kept as is.
     *
     * @param  list<string>  $attributes
     */
    public static function byLocale(array $data, array $attributes): array
    {
        foreach ($attributes as $attribute) {
            if (! is_array($data[$attribute] ?? null)) {
                continue;
            }

            foreach ($data[$attribute] as $locale => $value) {
                $data[$locale][$attribute] = $value;
            }

            unset($data[$attribute]);
        }

        return $data;
    }
}
