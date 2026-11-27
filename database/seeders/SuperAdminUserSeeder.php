<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\OvertimeSetting;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Super Admin
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator Sistem',
                'email' => 'admin@djpb.kemenkeu.go.id',
                'password' => Hash::make('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $admin->syncRoles(['admin']);

        // 2. Pejabat Penyetuju (Atasan - Kepala Bagian Umum / Kasubbag TU)
        $atasan = User::updateOrCreate(
            ['username' => 'atasan'],
            [
                'name' => 'Kepala Bagian Umum',
                'email' => 'atasan@djpb.kemenkeu.go.id',
                'password' => Hash::make('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $atasan->syncRoles(['atasan']);

        // 3. Supervisor Lapangan
        $supervisor = User::updateOrCreate(
            ['username' => 'supervisor'],
            [
                'name' => 'Supervisor Lapangan',
                'email' => 'supervisor@djpb.kemenkeu.go.id',
                'password' => Hash::make('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $supervisor->syncRoles(['supervisor']);

        // 3b. Akun Pengusul Lembur (Admin - Mas Yudha) & Penyetuju Lembur (Atasan - Pak Dedi)
        $masYudha = User::updateOrCreate(
            ['username' => 'masyudha'],
            [
                'name' => 'Mas Yudha',
                'email' => 'yudha@djpb.kemenkeu.go.id',
                'password' => Hash::make('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $masYudha->syncRoles(['admin']);

        $pakDedi = User::updateOrCreate(
            ['username' => 'pakdedi'],
            [
                'name' => 'Pak Dedi',
                'email' => 'dedi@djpb.kemenkeu.go.id',
                'password' => Hash::make('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $pakDedi->syncRoles(['atasan']);

        OvertimeSetting::current();

        // 4. Sample Karyawan (Pramubhakti / OB)
        $karyawanUser = User::updateOrCreate(
            ['username' => 'ppnpn01'],
            [
                'name' => 'Ahmad Subari',
                'email' => 'ahmad.subari@djpb.kemenkeu.go.id',
                'password' => Hash::make('password'),
                'is_active' => true,
                'must_change_password' => true, // Wajib ganti password saat login pertama (FR-101)
            ]
        );
        $karyawanUser->syncRoles(['karyawan']);

        $obPosition = Position::where('code', 'OB')->first();

        if ($obPosition) {
            Employee::updateOrCreate(
                ['user_id' => $karyawanUser->id],
                [
                    'employee_code' => 'PPNPN-2025-001',
                    'full_name' => 'Ahmad Subari',
                    'gender' => 'L',
                    'phone_number' => '081234567890',
                    'position_id' => $obPosition->id,
                    'supervisor_id' => $supervisor->id,
                    'atasan_id' => $atasan->id,
                    'base_salary' => 3200000.00,
                    'leave_quota_balance' => 12,
                    'spk_number' => 'SPK-20/WPB.08/PPK/2025',
                    'contract_start_date' => '2026-01-01',
                    'contract_end_date' => '2027-12-31',
                    'contract_value' => 38400000.00,
                    'is_active' => true,
                ]
            );
        }
    }
}
