<?php

namespace App\Filament\Resources\Diets\Pages;

use App\Filament\Actions\GenerateImageAction;
use App\Filament\Resources\Diets\DietResource;
use App\Filament\Support\CleansTranslations;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDiet extends EditRecord
{
    use CleansTranslations;

    protected static string $resource = DietResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GenerateImageAction::make('diets'),
            DeleteAction::make(),
        ];
    }
}
