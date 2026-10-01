<?php

namespace App\Filament\Resources\Dishes;

use App\Filament\Resources\Dishes\Pages\CreateDish;
use App\Filament\Resources\Dishes\Pages\EditDish;
use App\Filament\Resources\Dishes\Pages\ListDishes;
use App\Filament\Support\Translatable;
use App\Models\Dish;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class DishResource extends Resource
{
    protected static ?string $model = Dish::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCake;

    protected static string|\UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $modelLabel = 'блюдо';

    protected static ?string $pluralModelLabel = 'Блюда';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::tabs(fn (string $locale, bool $default) => [
                TextInput::make("name.{$locale}")
                    ->label('Название')
                    ->required($default)
                    ->maxLength(150)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Set $set, ?Dish $record) use ($locale) {
                        if ($locale === 'en' && ! $record?->exists) {
                            $set('slug', Str::slug($state));
                        }
                    }),
                Textarea::make("excerpt.{$locale}")->label('Краткое описание')->rows(2),
                Grid::make(2)->schema([
                    Textarea::make("ingredients.{$locale}")->label('Ингредиенты')->helperText('Один ингредиент на строку, напр. «Яйца — 2 шт.»')->rows(8),
                    Textarea::make("instructions.{$locale}")->label('Приготовление')->helperText('Один шаг на строку')->rows(8),
                ]),
            ]),

            Section::make('КБЖУ на порцию')
                ->description('Калории можно пересчитать из БЖУ (4/9/4 ккал на грамм).')
                ->columns(4)
                ->schema([
                    TextInput::make('protein')->label('Белки, г')->numeric()->step(0.1)->default(0)->required(),
                    TextInput::make('fat')->label('Жиры, г')->numeric()->step(0.1)->default(0)->required(),
                    TextInput::make('carbs')->label('Углеводы, г')->numeric()->step(0.1)->default(0)->required(),
                    TextInput::make('calories')->label('Ккал')->numeric()->default(0)->required()
                        ->suffixAction(
                            \Filament\Actions\Action::make('calc')
                                ->icon(Heroicon::OutlinedCalculator)
                                ->tooltip('Рассчитать из БЖУ')
                                ->action(fn (Get $get, Set $set) => $set('calories', (int) round($get('protein') * 4 + $get('fat') * 9 + $get('carbs') * 4)))
                        ),
                    TextInput::make('prep_minutes')->label('Подготовка, мин')->numeric(),
                    TextInput::make('cook_minutes')->label('Готовка, мин')->numeric(),
                    TextInput::make('servings')->label('Порций')->numeric()->default(1)->minValue(1)->required(),
                ]),

            Section::make('Публикация')->columns(2)->schema([
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Select::make('dish_category_id')->label('Категория')->relationship('category', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)->preload(),
                Select::make('diets')->label('Подходит для диет')->relationship('diets', 'slug')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->icon.' '.$record->name)
                    ->multiple()->preload()->columnSpanFull(),
                Toggle::make('is_featured')->label('На главной'),
                Toggle::make('is_published')->label('Опубликовано')->default(true),
            ]),

            Section::make('Фото')->columns(2)->schema([
                FileUpload::make('image')->label('Загрузить')->image()->disk('public')->directory('dishes')->imageEditor(),
                TextInput::make('image_url')->label('или внешний URL')->url()->helperText('Используется, если файл не загружен. На странице редактирования есть кнопка «Фото через AI».'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('preview')->label('')->state(fn (Dish $record) => $record->imageUrl())->square(),
                TextColumn::make('name')->label('Название')->wrap()
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->where('name->ru', 'like', "%{$search}%")
                        ->orWhere('name->en', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")),
                TextColumn::make('category.name')->label('Категория')->badge(),
                TextColumn::make('calories')->label('Ккал')->sortable(),
                TextColumn::make('protein')->label('Б')->sortable(),
                TextColumn::make('fat')->label('Ж')->sortable(),
                TextColumn::make('carbs')->label('У')->sortable(),
                ToggleColumn::make('is_featured')->label('Главная'),
                ToggleColumn::make('is_published')->label('Опубл.'),
            ])
            ->filters([
                SelectFilter::make('dish_category_id')->label('Категория')->relationship('category', 'slug')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name),
                SelectFilter::make('diets')->label('Диета')->relationship('diets', 'slug')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDishes::route('/'),
            'create' => CreateDish::route('/create'),
            'edit' => EditDish::route('/{record}/edit'),
        ];
    }
}
