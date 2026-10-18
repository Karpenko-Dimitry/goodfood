<?php

namespace App\Filament\Resources\Users;

use App\Filament\Actions\ChangePasswordAction;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\RelationManagers\DietPlansRelationManager;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Notifications\Notification;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Site users (registered by e-mail or Google). Admin rights are granted here.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Аналитика';

    protected static ?string $modelLabel = 'пользователь';

    protected static ?string $pluralModelLabel = 'Пользователи';

    public static function canCreate(): bool
    {
        return false;   // users sign up on the site
    }

    public static function form(Schema $schema): Schema
    {
        $isSelf = fn (?User $record) => $record?->is(auth()->user()) ?? false;

        return $schema->columns(3)->components([
            Section::make('Профиль')->columnSpan(2)->columns(2)->schema([
                TextInput::make('name')->label('Имя')->required()->maxLength(100),
                TextInput::make('email')->label('Email')->email()->required()->maxLength(150)
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (string $state) => mb_strtolower(trim($state)))
                    ->helperText('При смене email отметка о подтверждении сбрасывается.'),
                Toggle::make('is_admin')->label('Администратор')->columnSpanFull()
                    ->helperText(fn (?User $record) => $isSelf($record)
                        ? 'Нельзя снять права с самого себя.'
                        : 'Доступ в эту админ-панель.')
                    ->disabled($isSelf),
            ]),
            Section::make('Информация')->columnSpan(1)->schema([
                ImageEntry::make('avatar')->label('Фото Google')->circular()->imageHeight(64)
                    ->visible(fn (?User $record) => filled($record?->avatar)),
                TextEntry::make('sign_in')->label('Вход')
                    ->state(fn (User $record) => collect([
                        $record->hasPassword() ? 'email + пароль' : null,
                        $record->google_id ? 'Google' : null,
                    ])->filter()->implode(' · ') ?: '—'),
                TextEntry::make('email_verified_at')->label('Email подтверждён')->dateTime('d.m.Y H:i')->placeholder('нет'),
                TextEntry::make('created_at')->label('Регистрация')->dateTime('d.m.Y H:i'),
                TextEntry::make('lastVisit.last_seen_at')->label('Последний визит')->since()->placeholder('—'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $isSelf = fn (User $record) => $record->is(auth()->user());

        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('dietPlans'))
            ->columns([
                ImageColumn::make('avatar')->label('')->circular(),   // Google photo, if any
                TextColumn::make('name')->label('Имя')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->copyable(),
                IconColumn::make('google')->label('Google')
                    ->state(fn (User $record) => filled($record->google_id))->boolean(),
                TextColumn::make('diet_plans_count')->label('AI-планы')->sortable(),
                // Not a ToggleColumn: changing admin rights must be confirmed.
                IconColumn::make('is_admin')->label('Админ')->boolean()
                    ->tooltip(fn (User $record) => $isSelf($record)
                        ? 'Нельзя снять права с самого себя'
                        : ($record->is_admin ? 'Нажмите, чтобы снять права администратора' : 'Нажмите, чтобы сделать администратором'))
                    ->action(
                        Action::make('toggleAdmin')
                            ->requiresConfirmation()
                            ->hidden($isSelf)
                            ->modalIcon(Heroicon::OutlinedShieldCheck)
                            ->modalHeading(fn (User $record) => $record->is_admin ? 'Снять права администратора?' : 'Сделать администратором?')
                            ->modalDescription(fn (User $record) => $record->is_admin
                                ? "{$record->name} ({$record->email}) потеряет доступ к админ-панели."
                                : "{$record->name} ({$record->email}) получит полный доступ к админ-панели: контент, пользователи, пароли.")
                            ->modalSubmitActionLabel(fn (User $record) => $record->is_admin ? 'Снять права' : 'Сделать администратором')
                            ->color(fn (User $record) => $record->is_admin ? 'danger' : 'warning')
                            ->action(function (User $record) use ($isSelf) {
                                if ($isSelf($record)) {
                                    return;
                                }

                                $record->forceFill(['is_admin' => ! $record->is_admin])->save();   // not mass assignable

                                Notification::make()->success()
                                    ->title($record->is_admin ? 'Права администратора выданы' : 'Права администратора сняты')
                                    ->send();
                            }),
                    ),
                TextColumn::make('created_at')->label('Регистрация')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_admin')->label('Администраторы'),
                TernaryFilter::make('google')->label('Через Google')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('google_id'),
                        false: fn (Builder $q) => $q->whereNull('google_id'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                ChangePasswordAction::make(),
                DeleteAction::make()->hidden($isSelf),
            ]);
    }

    public static function getRelations(): array
    {
        return [DietPlansRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
