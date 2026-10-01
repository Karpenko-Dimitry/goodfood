<?php

namespace App\Filament\Resources\Dishes\Pages;

use App\Filament\Actions\GenerateImageAction;
use App\Filament\Resources\Dishes\DishResource;
use App\Filament\Support\CleansTranslations;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDish extends EditRecord
{
    use CleansTranslations;

    protected static string $resource = DishResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GenerateImageAction::make('dishes'),
            DeleteAction::make(),
        ];
    }
}
