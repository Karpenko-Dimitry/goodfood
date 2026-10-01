<?php

namespace App\Filament\Resources\DishCategories;

use App\Filament\Resources\DishCategories\Pages\CreateDishCategory;
use App\Filament\Resources\DishCategories\Pages\EditDishCategory;
use App\Filament\Resources\DishCategories\Pages\ListDishCategories;
use App\Filament\Support\Translatable;
use App\Models\DishCategory;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DishCategoryResource extends Resource
{
    protected static ?string $model = DishCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $modelLabel = 'категория';

    protected static ?string $pluralModelLabel = 'Категории блюд';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::tabs(fn (string $locale, bool $default) => [
                TextInput::make("name.{$locale}")->label('Название')->required($default),
            ]),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            TextInput::make('sort')->label('Порядок')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')->label('Название'),
                TextColumn::make('slug'),
                TextColumn::make('dishes_count')->label('Блюд')->counts('dishes'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDishCategories::route('/'),
            'create' => CreateDishCategory::route('/create'),
            'edit' => EditDishCategory::route('/{record}/edit'),
        ];
    }
}
