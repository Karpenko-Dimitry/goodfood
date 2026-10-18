<?php

namespace App\Filament\Support;

/**
 * Create/Edit pages of models using astrotomic/laravel-translatable.
 * Form state is per locale: ['ru' => ['name' => ...], 'en' => [...]] (fields named "{locale}.{attribute}").
 */
trait CleansTranslations
{
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        foreach ($record->translations as $translation) {
            $data[$translation->locale] = $translation->only($record->translatedAttributes);
        }

        return $data;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->withoutEmptyLocales($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = $this->withoutEmptyLocales($data);
        $record = $this->getRecord();

        // A locale the editor emptied completely is removed, so the site falls back to the fallback locale.
        foreach (array_keys(config('app.locales')) as $locale) {
            if (! isset($data[$locale]) && $record->hasTranslation($locale)) {
                $record->deleteTranslations($locale);
            }
        }

        return $data;
    }

    private function withoutEmptyLocales(array $data): array
    {
        foreach (array_keys(config('app.locales')) as $locale) {
            if (isset($data[$locale]) && ! array_filter($data[$locale], fn ($value) => filled($value))) {
                unset($data[$locale]);
            }
        }

        return $data;
    }
}
