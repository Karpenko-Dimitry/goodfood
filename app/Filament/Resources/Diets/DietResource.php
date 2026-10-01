<?php

namespace App\Filament\Resources\Diets;

use App\Filament\Resources\Diets\Pages\CreateDiet;
use App\Filament\Resources\Diets\Pages\EditDiet;
use App\Filament\Resources\Diets\Pages\ListDiets;
use App\Filament\Support\Translatable;
use App\Models\Diet;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class DietResource extends Resource
{
    protected static ?string $model = Diet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|\UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $modelLabel = 'диета';

    protected static ?string $pluralModelLabel = 'Диеты';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::tabs(fn (string $locale, bool $default) => [
                TextInput::make("name.{$locale}")
                    ->label('Название')
                    ->required($default)
                    ->maxLength(150)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Set $set, ?Diet $record) use ($locale) {
                        if ($locale === 'en' && ! $record?->exists) {
                            $set('slug', Str::slug($state));
                        }
                    }),
                Textarea::make("excerpt.{$locale}")->label('Краткое описание')->rows(2),
                RichEditor::make("description.{$locale}")->label('Описание'),
                Grid::make(2)->schema([
                    Textarea::make("pros.{$locale}")->label('Плюсы')->helperText('По одному на строку')->rows(4),
                    Textarea::make("cons.{$locale}")->label('Минусы')->helperText('По одному на строку')->rows(4),
                    Textarea::make("allowed_foods.{$locale}")->label('Разрешённые продукты')->helperText('По одному на строку')->rows(5),
                    Textarea::make("forbidden_foods.{$locale}")->label('Запрещённые продукты')->helperText('По одному на строку')->rows(5),
                ]),
            ]),

            Section::make('Параметры')->columns(4)->schema([
                TextInput::make('slug')->required()->unique(ignoreRecord: true)->columnSpan(2),
                TextInput::make('icon')->label('Иконка (emoji)')->maxLength(8),
                Select::make('difficulty')->label('Сложность')->options([1 => 'Легко', 2 => 'Средне', 3 => 'Сложно'])->default(2)->required(),
                TextInput::make('calories_min')->label('Ккал от')->numeric(),
                TextInput::make('calories_max')->label('Ккал до')->numeric(),
                TextInput::make('duration_days')->label('Длительность, дней')->numeric(),
                TextInput::make('sort')->label('Порядок')->numeric()->default(0),
                TextInput::make('protein_pct')->label('Белки, %')->numeric()->maxValue(100),
                TextInput::make('fat_pct')->label('Жиры, %')->numeric()->maxValue(100),
                TextInput::make('carbs_pct')->label('Углеводы, %')->numeric()->maxValue(100),
                Grid::make(1)->schema([
                    Toggle::make('is_featured')->label('На главной'),
                    Toggle::make('is_published')->label('Опубликована')->default(true),
                ])->columnSpan(1),
            ]),

            Section::make('Изображение')->columns(2)->schema([
                FileUpload::make('image')->label('Загрузить')->image()->disk('public')->directory('diets')->imageEditor(),
                TextInput::make('image_url')->label('или внешний URL')->url()->helperText('Используется, если файл не загружен.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ImageColumn::make('preview')->label('')->state(fn (Diet $record) => $record->imageUrl())->square(),
                TextColumn::make('name')->label('Название')
                    ->description(fn (Diet $record) => $record->icon.' '.$record->slug)
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->where('name->ru', 'like', "%{$search}%")
                        ->orWhere('name->en', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")),
                TextColumn::make('difficulty')->label('Сложность')->badge()
                    ->formatStateUsing(fn (int $state) => [1 => 'Легко', 2 => 'Средне', 3 => 'Сложно'][$state])
                    ->color(fn (int $state) => [1 => 'success', 2 => 'warning', 3 => 'danger'][$state]),
                TextColumn::make('dishes_count')->label('Блюд')->counts('dishes')->sortable(),
                ToggleColumn::make('is_featured')->label('Главная'),
                ToggleColumn::make('is_published')->label('Опубл.'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiets::route('/'),
            'create' => CreateDiet::route('/create'),
            'edit' => EditDiet::route('/{record}/edit'),
        ];
    }
}
