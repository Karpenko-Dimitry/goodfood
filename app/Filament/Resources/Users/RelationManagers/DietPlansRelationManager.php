<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\DietPlanRequests\DietPlanRequestResource;
use App\Models\DietPlanRequest;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * AI meal plans of the user (read-only; opens the plan in "AI-планы").
 */
class DietPlansRelationManager extends RelationManager
{
    protected static string $relationship = 'dietPlans';

    protected static ?string $title = 'AI-планы питания';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Дата')->dateTime('d.m.Y H:i'),
                TextColumn::make('goal')->label('Цель')->badge(),
                TextColumn::make('preferredDiet.name')->label('Диета')->placeholder('—'),
                TextColumn::make('result.daily_calories')->label('Ккал')->placeholder('—'),
                TextColumn::make('status')->label('Статус')->badge()
                    ->color(fn (string $state) => DietPlanRequestResource::statusColor($state)),
            ])
            ->recordActions([
                Action::make('open')->label('Открыть')->icon(Heroicon::OutlinedEye)
                    ->url(fn (DietPlanRequest $record) => DietPlanRequestResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
