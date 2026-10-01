<?php

namespace App\Filament\Resources\DishCategories\Pages;

use App\Filament\Resources\DishCategories\DishCategoryResource;
use App\Filament\Support\CleansTranslations;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDishCategory extends EditRecord
{
    use CleansTranslations;

    protected static string $resource = DishCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
