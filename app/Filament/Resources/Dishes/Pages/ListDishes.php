<?php

namespace App\Filament\Resources\Dishes\Pages;

use App\Filament\Resources\Dishes\DishResource;
use App\Jobs\CreateAiDish;
use App\Models\Diet;
use App\Models\Dish;
use App\Services\Ai\DishIdea;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListDishes extends ListRecords
{
    protected static string $resource = DishResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiDish')
                ->label('Создать блюдо через AI')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('gray')
                ->modalDescription('ИИ напишет рецепт на всех языках с КБЖУ, сохранит блюдо и сгенерирует фото. Обычно это занимает до минуты — блюдо появится в списке.')
                ->schema([
                    TextInput::make('title')->label('Название или идея')->required()->maxLength(150)
                        ->placeholder('Например: тёплый салат с киноа и тыквой'),
                    Textarea::make('description')->label('Пожелания')->rows(2)
                        ->placeholder('Например: на ужин, до 450 ккал, без молочных продуктов'),
                    Select::make('meal_type')->label('Приём пищи')
                        ->options(['breakfast' => 'Завтрак', 'lunch' => 'Обед', 'dinner' => 'Ужин', 'snack' => 'Перекус']),
                    Select::make('diet')->label('Диета')
                        ->options(fn () => Diet::published()->get()->mapWithKeys(fn (Diet $d) => [$d->slug => $d->name])),
                ])
                ->action(function (array $data) {
                    if ($existing = Dish::findByTitle($data['title'])) {
                        Notification::make()->warning()->title("Такое блюдо уже есть: {$existing->name}")->send();

                        return;
                    }

                    CreateAiDish::dispatch(new DishIdea(
                        title: $data['title'],
                        description: (string) $data['description'],
                        mealType: $data['meal_type'],
                        dietSlug: $data['diet'],
                    ));

                    Notification::make()->success()->title('Генерация запущена')->body('Блюдо появится в списке, когда будет готово.')->send();
                }),
            CreateAction::make(),
        ];
    }
}
