<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define Granular Permissions
        $permissions = [
            // Admin Permissions
            'manage-employees',
            'manage-positions',
            'manage-offices',
            'manage-rules',
            'manage-contracts',
            'close-periods',
            'export-reports',
            'view-audit-logs',

            // Supervisor Permissions
            'view-team-attendances',
            'approve-leaves-level-1',
            'record-violations',
            'view-team-violations',
            'request-attendance-corrections',
            'view-team-reports',

            // Atasan Permissions
            'approve-leaves-level-2',
            'view-sanctions',
            'view-contract-dashboard',
            'view-executive-reports',

            // Karyawan Permissions
            'record-attendance',
            'view-own-attendances',
            'submit-leave-requests',
            'view-own-violations',
            'view-own-deductions',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // 2. Define Roles and Assign Corresponding Permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions([
            'manage-employees',
            'manage-positions',
            'manage-offices',
            'manage-rules',
            'manage-contracts',
            'close-periods',
            'export-reports',
            'view-audit-logs',
            'view-sanctions',
            'view-contract-dashboard',
            'view-executive-reports',
        ]);

        $supervisorRole = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        $supervisorRole->syncPermissions([
            'view-team-attendances',
            'approve-leaves-level-1',
            'record-violations',
            'view-team-violations',
            'request-attendance-corrections',
            'view-team-reports',
        ]);

        $atasanRole = Role::firstOrCreate(['name' => 'atasan', 'guard_name' => 'web']);
        $atasanRole->syncPermissions([
            'approve-leaves-level-2',
            'view-sanctions',
            'view-contract-dashboard',
            'view-executive-reports',
        ]);

        $karyawanRole = Role::firstOrCreate(['name' => 'karyawan', 'guard_name' => 'web']);
        $karyawanRole->syncPermissions([
            'record-attendance',
            'view-own-attendances',
            'submit-leave-requests',
            'view-own-violations',
            'view-own-deductions',
        ]);
    }
}
