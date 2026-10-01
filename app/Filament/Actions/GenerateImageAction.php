<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Laravel\Ai\Image;
use Throwable;

/**
 * Generates a food photo for a Diet/Dish with OpenAI images via laravel/ai.
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
            ->modalDescription('Изображение будет сгенерировано OpenAI по названию и описанию (EN) и заменит загруженное фото.')
            ->action(function (Model $record, EditRecord $livewire) use ($directory) {
                $prompt = sprintf(
                    'Professional overhead food photography of "%s". %s Natural daylight, rustic wooden table, fresh herbs, shallow depth of field, appetizing, no text, no people.',
                    $record->getTranslation('name', 'en'),
                    $record->getTranslation('excerpt', 'en'),
                );

                try {
                    $path = Image::of($prompt)
                        ->landscape()
                        ->generate(model: config('services.openai.image_model'))
                        ->storePublicly($directory, 'public');
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
