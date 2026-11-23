<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (User $record): bool => $record->id === Auth::id()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        /** @var User $record */
        $record = $this->record;

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'USER_UPDATED',
            'entity_type' => User::class,
            'entity_id' => $record->id,
            'new_values' => [
                'name' => $record->name,
                'username' => $record->username,
                'email' => $record->email,
                'roles' => $record->roles->pluck('name')->toArray(),
                'is_active' => $record->is_active,
                'must_change_password' => $record->must_change_password,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
