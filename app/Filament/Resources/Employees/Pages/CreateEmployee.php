<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    /**
     * Create Employee and automatically provision its user account.
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // 1. Create User account for employee if not exists
            $user = User::create([
                'name' => $data['full_name'],
                'username' => $data['employee_code'],
                'email' => Str::lower($data['employee_code']).'@djpb.local',
                'password' => Hash::make('password'),
                'is_active' => $data['is_active'] ?? true,
                'must_change_password' => true,
            ]);
            $user->syncRoles(['karyawan']);

            // 2. Set user_id and create Employee
            $data['user_id'] = $user->id;
            /** @var Employee $employee */
            $employee = static::getModel()::create($data);

            // 3. Record Audit Log
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'EMPLOYEE_CREATED',
                'entity_type' => Employee::class,
                'entity_id' => $employee->id,
                'new_values' => [
                    'employee_code' => $employee->employee_code,
                    'full_name' => $employee->full_name,
                    'position_id' => $employee->position_id,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $employee;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
