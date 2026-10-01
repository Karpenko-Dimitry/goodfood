<?php

namespace App\Models\Concerns;

trait SplitsLines
{
    /**
     * Translatable list fields are stored as "one item per line" text.
     *
     * @return list<string>
     */
    public function lines(string $attribute): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R/u', (string) $this->getTranslation($attribute, app()->getLocale()))),
            fn (string $line) => $line !== '',
        ));
    }
}
