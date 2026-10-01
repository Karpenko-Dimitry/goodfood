<?php

namespace App\Filament\Support;

/**
 * For Create/Edit pages of models using spatie/laravel-translatable.
 */
trait CleansTranslations
{
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return Translatable::clean($data, (new (static::getModel()))->getTranslatableAttributes());
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();
        $data = Translatable::clean($data, $record->getTranslatableAttributes());

        // setTranslations() only merges, so explicitly forget locales the editor cleared.
        foreach ($record->getTranslatableAttributes() as $attribute) {
            foreach (array_keys($record->getTranslations($attribute)) as $locale) {
                if (array_key_exists($attribute, $data) && ! array_key_exists($locale, $data[$attribute] ?? [])) {
                    $record->forgetTranslation($attribute, $locale);
                }
            }
        }

        return $data;
    }
}
