<?php

namespace App\Filament\Resources\DietPlanRequests;

use App\Filament\Resources\DietPlanRequests\Pages\ListDietPlanRequests;
use App\Filament\Resources\DietPlanRequests\Pages\ViewDietPlanRequest;
use App\Models\DietPlanRequest;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DietPlanRequestResource extends Resource
{
    protected static ?string $model = DietPlanRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'AI';

    protected static ?string $modelLabel = 'AI-план';

    protected static ?string $pluralModelLabel = 'AI-планы питания';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) DietPlanRequest::whereDate('created_at', today())->count() ?: null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Анкета')->columnSpan(1)->schema([
                TextEntry::make('name')->label('Имя')->placeholder('—'),
                TextEntry::make('email')->placeholder('—'),
                TextEntry::make('locale')->label('Язык')->badge(),
                TextEntry::make('gender')->label('Пол'),
                TextEntry::make('age')->label('Возраст'),
                TextEntry::make('height_cm')->label('Рост')->suffix(' см'),
                TextEntry::make('weight_kg')->label('Вес')->suffix(' кг'),
                TextEntry::make('target_weight_kg')->label('Цель по весу')->suffix(' кг')->placeholder('—'),
                TextEntry::make('goal')->label('Цель')->badge(),
                TextEntry::make('activity')->label('Активность'),
                TextEntry::make('preferredDiet.name')->label('Диета')->placeholder('—'),
                TextEntry::make('allergies')->label('Аллергии')->placeholder('—'),
                TextEntry::make('preferences')->label('Предпочтения')->placeholder('—'),
            ]),
            Section::make('Результат')->columnSpan(2)->schema([
                TextEntry::make('status')->label('Статус')->badge()->color(fn (string $state) => self::statusColor($state)),
                TextEntry::make('bmi')->label('ИМТ')->state(fn (DietPlanRequest $record) => $record->bmi()),
                TextEntry::make('calc')->label('Расчётная норма')->state(fn (DietPlanRequest $record) => $record->targetCalories().' ккал'),
                TextEntry::make('result.daily_calories')->label('Норма от AI')->suffix(' ккал')->placeholder('—'),
                TextEntry::make('macros')->label('БЖУ, г')->placeholder('—')
                    ->state(fn (DietPlanRequest $record) => $record->result ? "{$record->result['protein_g']} / {$record->result['fat_g']} / {$record->result['carbs_g']}" : null),
                TextEntry::make('result.summary')->label('Резюме')->placeholder('—')->columnSpanFull(),
                TextEntry::make('result.warning')->label('Предупреждение')->placeholder('—')->columnSpanFull(),
                TextEntry::make('error')->label('Ошибка')->color('danger')->placeholder('—')->columnSpanFull(),
                TextEntry::make('result_json')->label('JSON-ответ модели')->columnSpanFull()
                    ->state(fn (DietPlanRequest $record) => $record->result ? json_encode($record->result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null)
                    ->fontFamily('mono')->size('xs')->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => new \Illuminate\Support\HtmlString('<pre style="white-space:pre-wrap;max-height:28rem;overflow:auto">'.e($state).'</pre>')),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->poll('10s')
            ->columns([
                TextColumn::make('created_at')->label('Дата')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('name')->label('Имя')->placeholder('—')->searchable(),
                TextColumn::make('email')->placeholder('—')->searchable(),
                TextColumn::make('locale')->label('Язык')->badge(),
                TextColumn::make('goal')->label('Цель')->badge(),
                TextColumn::make('weight_kg')->label('Вес'),
                TextColumn::make('result.daily_calories')->label('Ккал')->placeholder('—'),
                TextColumn::make('status')->label('Статус')->badge()->color(fn (string $state) => self::statusColor($state)),
            ])
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(['pending' => 'pending', 'completed' => 'completed', 'failed' => 'failed']),
                SelectFilter::make('goal')->label('Цель')->options(array_combine(DietPlanRequest::GOALS, DietPlanRequest::GOALS)),
            ])
            ->recordActions([ViewAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function statusColor(string $status): string
    {
        return ['pending' => 'warning', 'completed' => 'success', 'failed' => 'danger'][$status] ?? 'gray';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDietPlanRequests::route('/'),
            'view' => ViewDietPlanRequest::route('/{record}'),
        ];
    }
}
