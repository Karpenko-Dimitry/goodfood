<?php

namespace App\Filament\Resources\Visitors;

use App\Filament\Resources\Visitors\Pages\ListVisitors;
use App\Models\Visitor;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only list of guests and users recorded by App\Services\Visitors\VisitorTracker.
 */
class VisitorResource extends Resource
{
    protected static ?string $model = Visitor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Аналитика';

    protected static ?string $modelLabel = 'посетитель';

    protected static ?string $pluralModelLabel = 'Посетители';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Visitor::online()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Сейчас онлайн';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_seen_at', 'desc')
            ->poll('30s')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                IconColumn::make('online')->label('Онлайн')
                    ->state(fn (Visitor $record) => $record->isOnline())
                    ->boolean()->trueColor('success')->falseIcon(Heroicon::OutlinedMinus)->falseColor('gray'),
                TextColumn::make('type')->label('Тип')->badge()
                    ->state(fn (Visitor $record) => $record->isGuest() ? 'Гость' : 'Пользователь')
                    ->color(fn (Visitor $record) => $record->isGuest() ? 'gray' : 'primary'),
                TextColumn::make('user.name')->label('Пользователь')->placeholder('—')
                    ->description(fn (Visitor $record) => $record->user?->email),
                TextColumn::make('first_seen_at')->label('Первый визит')->dateTime('d.m.Y H:i')->sortable()
                    ->description(fn (Visitor $record) => $record->first_seen_at->isToday() ? 'новый сегодня' : null),
                TextColumn::make('last_seen_at')->label('Был')->since()->sortable()
                    ->tooltip(fn (Visitor $record) => $record->last_seen_at->format('d.m.Y H:i:s')),
                TextColumn::make('page_views')->label('Просмотры')->numeric()->sortable(),
                TextColumn::make('last_path')->label('Последняя страница')->limit(40)->tooltip(fn (Visitor $record) => $record->last_path),
                TextColumn::make('landing_path')->label('Вход')->limit(30)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('referrer')->label('Источник')->placeholder('прямой')->toggleable(),
                TextColumn::make('locale')->label('Язык')->badge()->toggleable(),
                TextColumn::make('device')->label('Устройство')
                    ->formatStateUsing(fn (?string $state) => ['desktop' => 'Компьютер', 'mobile' => 'Телефон', 'tablet' => 'Планшет'][$state] ?? '—')
                    ->toggleable(),
                TextColumn::make('ip')->label('IP (анонимизирован)')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user_agent')->label('Браузер')->limit(40)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('online')->label('Сейчас онлайн')->query(fn (Builder $query) => $query->online()),
                Filter::make('new_today')->label('Новые сегодня')->query(fn (Builder $query) => $query->newSince(today())),
                SelectFilter::make('type')->label('Тип')
                    ->options(['guests' => 'Гости', 'users' => 'Пользователи'])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'guests' => $query->guests(),
                        'users' => $query->registered(),
                        default => $query,
                    }),
                SelectFilter::make('device')->label('Устройство')
                    ->options(['desktop' => 'Компьютер', 'mobile' => 'Телефон', 'tablet' => 'Планшет']),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVisitors::route('/'),
        ];
    }
}
