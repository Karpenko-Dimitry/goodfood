<?php

namespace App\Filament\Resources\DietPlanRequests\Pages;

use App\Filament\Resources\DietPlanRequests\DietPlanRequestResource;
use App\Jobs\GenerateDietPlan;
use App\Models\DietPlanRequest;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewDietPlanRequest extends ViewRecord
{
    protected static string $resource = DietPlanRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open')
                ->label('Открыть на сайте')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (DietPlanRequest $record) => route('plan.show', ['locale' => $record->locale, 'planRequest' => $record]), shouldOpenInNewTab: true),
            Action::make('regenerate')
                ->label('Сгенерировать заново')
                ->icon(Heroicon::OutlinedArrowPath)
                ->requiresConfirmation()
                ->action(function (DietPlanRequest $record) {
                    $record->update(['status' => 'pending', 'error' => null]);
                    GenerateDietPlan::dispatch($record);
                    Notification::make()->success()->title('Задача поставлена в очередь')->send();
                }),
            DeleteAction::make(),
        ];
    }
}
