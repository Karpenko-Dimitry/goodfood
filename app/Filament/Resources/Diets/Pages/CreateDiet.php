<?php

namespace App\Filament\Resources\Diets\Pages;

use App\Filament\Resources\Diets\DietResource;
use App\Filament\Support\CleansTranslations;
use Filament\Resources\Pages\CreateRecord;

class CreateDiet extends CreateRecord
{
    use CleansTranslations;

    protected static string $resource = DietResource::class;
}
