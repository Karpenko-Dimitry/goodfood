<?php

namespace App\Filament\Resources\DishCategories\Pages;

use App\Filament\Resources\DishCategories\DishCategoryResource;
use App\Filament\Support\CleansTranslations;
use Filament\Resources\Pages\CreateRecord;

class CreateDishCategory extends CreateRecord
{
    use CleansTranslations;

    protected static string $resource = DishCategoryResource::class;
}
