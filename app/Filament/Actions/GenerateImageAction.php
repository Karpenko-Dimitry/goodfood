<?php

namespace App\Filament\Actions;

use App\Services\Ai\NutritionAi;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Generates a food photo for a Diet/Dish through NutritionAi.
 */
class GenerateImageAction
{
    public static function make(string $directory): Action
    {
        return Action::make('generateImage')
            ->label('Фото через AI')
            ->icon(Heroicon::OutlinedSparkles)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('Изображение будет сгенерировано по описанию блюда и заменит загруженное фото.')
            ->action(function (Model $record, EditRecord $livewire, NutritionAi $ai) use ($directory) {
                $subject = $record->image_prompt
                    ?? trim($record->getTranslation('name', 'en').'. '.$record->getTranslation('excerpt', 'en'));

                try {
                    $path = $ai->dishImage($subject, $directory);
                } catch (Throwable $e) {
                    Notification::make()->danger()->title('Не удалось сгенерировать')->body($e->getMessage())->send();

                    return;
                }

                $record->update(['image' => $path]);
                $livewire->refreshFormData(['image']);

                Notification::make()->success()->title('Фото сгенерировано')->send();
            });
    }
}
