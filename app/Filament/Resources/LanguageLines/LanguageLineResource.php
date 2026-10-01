<?php

namespace App\Filament\Resources\LanguageLines;

use App\Filament\Resources\LanguageLines\Pages\CreateLanguageLine;
use App\Filament\Resources\LanguageLines\Pages\EditLanguageLine;
use App\Filament\Resources\LanguageLines\Pages\ListLanguageLines;
use App\Filament\Support\Translatable;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\TranslationLoader\LanguageLine;

/**
 * UI strings stored by spatie/laravel-translation-loader. A DB line overrides lang/{locale}/{group}.php.
 */
class LanguageLineResource extends Resource
{
    protected static ?string $model = LanguageLine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static string|\UnitEnum|null $navigationGroup = 'Локализация';

    protected static ?string $modelLabel = 'перевод';

    protected static ?string $pluralModelLabel = 'Переводы интерфейса';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('group')->label('Группа')->required()->default('site')
                ->helperText('Имя файла: __("group.key")'),
            TextInput::make('key')->label('Ключ')->required()
                ->helperText('Например: nav.home'),
            Translatable::tabs(fn (string $locale, bool $default) => [
                Textarea::make("text.{$locale}")->label('Текст')->rows(3)->required($default),
            ], 'Текст'),
        ]);
    }

    public static function table(Table $table): Table
    {
        $columns = [
            TextColumn::make('group')->label('Группа')->badge()->sortable(),
            TextColumn::make('key')->label('Ключ')->sortable()->copyable()
                ->searchable(query: fn (Builder $query, string $search) => $query
                    ->where('key', 'like', "%{$search}%")
                    ->orWhere('text', 'like', "%{$search}%")),
        ];

        foreach (array_keys(config('app.locales')) as $locale) {
            $columns[] = TextColumn::make("text.{$locale}")->label(strtoupper($locale))->limit(40)->wrap()->placeholder('—');
        }

        return $table
            ->defaultSort('key')
            ->columns($columns)
            ->filters([
                SelectFilter::make('group')->label('Группа')
                    ->options(fn () => LanguageLine::query()->distinct()->pluck('group', 'group')->all()),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLanguageLines::route('/'),
            'create' => CreateLanguageLine::route('/create'),
            'edit' => EditLanguageLine::route('/{record}/edit'),
        ];
    }
}
