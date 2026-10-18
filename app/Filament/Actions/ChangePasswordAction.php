<?php

namespace App\Filament\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Admin sets a new password for a user and signs them out everywhere.
 */
class ChangePasswordAction
{
    public static function make(): Action
    {
        return Action::make('changePassword')
            ->label('Сменить пароль')
            ->icon(Heroicon::OutlinedKey)
            ->color('gray')
            ->modalHeading(fn (User $record) => "Новый пароль: {$record->email}")
            ->modalDescription(fn (User $record) => $record->is(Auth::user())
                ? 'Это ваш аккаунт: текущая сессия сохранится, остальные устройства будут разлогинены.'
                : 'Пользователь будет разлогинен на всех устройствах.'.($record->google_id ? ' Вход через Google продолжит работать.' : ''))
            ->modalSubmitActionLabel('Сохранить пароль')
            ->schema([
                TextInput::make('password')->label('Новый пароль')
                    ->password()->revealable()->required()
                    ->rule(Password::min(8))->confirmed()->autocomplete('new-password'),
                TextInput::make('password_confirmation')->label('Повторите пароль')
                    ->password()->revealable()->required()->autocomplete('new-password'),
            ])
            ->action(function (User $record, array $data) {
                $record->forceFill([
                    'password' => $data['password'],            // hashed by the model cast
                    'remember_token' => Str::random(60),        // invalidates "remember me" cookies
                ])->save();

                $self = $record->is(Auth::user());

                // Sessions are stored in the database (SESSION_DRIVER=database): drop the user's other sessions.
                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))
                        ->where('user_id', $record->getKey())
                        ->when($self, fn ($q) => $q->where('id', '!=', session()->getId()))
                        ->delete();
                }

                // Keep the admin's own session valid: AuthenticateSession compares an HMAC of the password hash.
                if ($self) {
                    // The guard still holds the user loaded at the start of this request (old password);
                    // AuthenticateSession would write that hash back after the response.
                    Auth::guard()->setUser($record);
                    session()->put(
                        'password_hash_'.Auth::getDefaultDriver(),
                        Auth::guard()->hashPasswordForCookie($record->getAuthPassword()),
                    );
                }

                Notification::make()->success()->title('Пароль изменён')->send();
            });
    }
}
