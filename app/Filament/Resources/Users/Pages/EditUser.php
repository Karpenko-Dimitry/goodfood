<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Actions\ChangePasswordAction;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ChangePasswordAction::make(),
            DeleteAction::make()->hidden(fn (User $record) => $record->is(auth()->user())),
        ];
    }

    /**
     * is_admin is deliberately not mass assignable (public sign-up), so it is set explicitly here;
     * a changed e-mail is no longer verified.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $isAdmin = $data['is_admin'] ?? null;
        unset($data['is_admin']);

        $record->fill($data);

        if ($record->isDirty('email')) {
            $record->email_verified_at = null;
        }

        // The toggle is disabled (not submitted) on the admin's own account: never demote yourself.
        if ($isAdmin !== null && ! $record->is(auth()->user())) {
            $record->is_admin = (bool) $isAdmin;
        }

        $record->save();

        return $record;
    }
}
