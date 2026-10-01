<?php

namespace App\Filament\Resources\Dishes\Pages;

use App\Filament\Resources\Dishes\DishResource;
use App\Filament\Support\CleansTranslations;
use Filament\Resources\Pages\CreateRecord;

class CreateDish extends CreateRecord
{
    use CleansTranslations;

    protected static string $resource = DishResource::class;
}
