<?php

namespace App\Filament\Resources\LanguageLines\Pages;

use App\Filament\Resources\LanguageLines\LanguageLineResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

class ListLanguageLines extends ListRecords
{
    protected static string $resource = LanguageLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Импорт из lang-файлов')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->schema([
                    Toggle::make('force')->label('Перезаписать отредактированные тексты'),
                ])
                ->action(function (array $data) {
                    Artisan::call('translations:import', ['--force' => (bool) $data['force']]);

                    Notification::make()->success()->title(trim(Artisan::output()))->send();
                }),
            CreateAction::make(),
        ];
    }
}
