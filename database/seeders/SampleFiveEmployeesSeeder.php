<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\BiometricVerificationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleFiveEmployeesSeeder extends Seeder
{
    /**
     * Seed 5 representative PPNPN employees (OB, Driver, Receptionist, Technician, Security)
     * with registered encrypted face templates for demo and review.
     */
    public function run(): void
    {
        $admin = User::where('username', 'admin')->first();
        $supervisor = User::where('username', 'supervisor')->first();
        $atasan = User::where('username', 'atasan')->first();

        $sampleEmployees = [
            [
                'username' => 'ppnpn01',
                'name' => 'Ahmad Subari',
                'email' => 'ahmad.subari@djpb.kemenkeu.go.id',
                'employee_code' => 'PPNPN-2025-001',
                'gender' => 'L',
                'phone' => '081234567890',
                'position_code' => 'OB',
                'salary' => 3200000.00,
                'seed' => 0.15,
            ],
            [
                'username' => 'ppnpn02',
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@djpb.kemenkeu.go.id',
                'employee_code' => 'PPNPN-2025-002',
                'gender' => 'L',
                'phone' => '081234567891',
                'position_code' => 'DRIVER',
                'salary' => 3200000.00,
                'seed' => 0.25,
            ],
            [
                'username' => 'ppnpn03',
                'name' => 'Siti Rahmawati',
                'email' => 'siti.rahmawati@djpb.kemenkeu.go.id',
                'employee_code' => 'PPNPN-2025-003',
                'gender' => 'P',
                'phone' => '081234567892',
                'position_code' => 'RECEPTIONIST',
                'salary' => 3200000.00,
                'seed' => 0.35,
            ],
            [
                'username' => 'ppnpn04',
                'name' => 'Dedi Gunawan',
                'email' => 'dedi.gunawan@djpb.kemenkeu.go.id',
                'employee_code' => 'PPNPN-2025-004',
                'gender' => 'L',
                'phone' => '081234567893',
                'position_code' => 'TECHNICIAN',
                'salary' => 3400000.00,
                'seed' => 0.45,
            ],
            [
                'username' => 'ppnpn05',
                'name' => 'Agus Hendra',
                'email' => 'agus.hendra@djpb.kemenkeu.go.id',
                'employee_code' => 'PPNPN-2025-005',
                'gender' => 'L',
                'phone' => '081234567894',
                'position_code' => 'SECURITY_DAY',
                'salary' => 3300000.00,
                'seed' => 0.55,
            ],
        ];

        $biometricService = app(BiometricVerificationService::class);

        foreach ($sampleEmployees as $empData) {
            $user = User::updateOrCreate(
                ['username' => $empData['username']],
                [
                    'name' => $empData['name'],
                    'email' => $empData['email'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'must_change_password' => false,
                ]
            );
            $user->syncRoles(['karyawan']);

            $pos = Position::where('code', $empData['position_code'])->first();
            if (! $pos) {
                $pos = Position::first();
            }

            $employee = Employee::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_code' => $empData['employee_code'],
                    'full_name' => $empData['name'],
                    'gender' => $empData['gender'],
                    'phone_number' => $empData['phone'],
                    'position_id' => $pos->id,
                    'supervisor_id' => $supervisor?->id,
                    'atasan_id' => $atasan?->id,
                    'base_salary' => $empData['salary'],
                    'leave_quota_balance' => 12,
                    'spk_number' => 'SPK-20/WPB.08/PPK/2025',
                    'contract_start_date' => '2026-01-01',
                    'contract_end_date' => '2027-12-31',
                    'contract_value' => $empData['salary'] * 12,
                    'is_active' => true,
                ]
            );

            // Enroll realistic encrypted face template
            $vector = $biometricService->generateMockDescriptor($empData['seed']);
            $biometricService->enrollEmployee($employee->id, $vector, $admin?->id);
        }
    }
}
