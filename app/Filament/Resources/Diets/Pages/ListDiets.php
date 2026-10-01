<?php

namespace App\Filament\Resources\Diets\Pages;

use App\Filament\Resources\Diets\DietResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDiets extends ListRecords
{
    protected static string $resource = DietResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
