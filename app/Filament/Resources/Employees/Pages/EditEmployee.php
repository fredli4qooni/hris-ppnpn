<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\AuditLog;
use App\Models\Employee;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * Update employee and keep user account synchronized.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $oldValues = $record->only(['full_name', 'position_id', 'supervisor_id', 'atasan_id', 'is_active', 'base_salary']);

            $record->update($data);

            if ($record->user) {
                $record->user->update([
                    'name' => $data['full_name'] ?? $record->user->name,
                    'is_active' => $data['is_active'] ?? $record->user->is_active,
                ]);
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'EMPLOYEE_UPDATED',
                'entity_type' => Employee::class,
                'entity_id' => $record->id,
                'old_values' => $oldValues,
                'new_values' => $record->only(['full_name', 'position_id', 'supervisor_id', 'atasan_id', 'is_active', 'base_salary']),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $record;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
