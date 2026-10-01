<?php

namespace App\Filament\Resources\DishCategories\Pages;

use App\Filament\Resources\DishCategories\DishCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDishCategories extends ListRecords
{
    protected static string $resource = DishCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
