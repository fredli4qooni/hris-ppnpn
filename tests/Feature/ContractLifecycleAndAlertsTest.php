<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeViolation;
use App\Models\User;
use App\Models\ViolationCatalog;
use Carbon\Carbon;
use Database\Seeders\SampleFiveEmployeesSeeder;
use Database\Seeders\ViolationCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ContractLifecycleAndAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $supervisorUser;

    protected User $karyawanUser;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->seed(ViolationCatalogSeeder::class);
        $this->seed(SampleFiveEmployeesSeeder::class);

        $this->adminUser = User::where('username', 'admin')->first();
        $this->supervisorUser = User::where('username', 'supervisor')->first();
        $this->karyawanUser = User::where('username', 'ppnpn01')->first();
        $this->karyawanUser->update(['must_change_password' => false]);

        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->first();
    }

    public function test_employee_calculates_correct_days_until_contract_expiry_and_status(): void
    {
        $refDate = Carbon::parse('2026-09-01 00:00:00', 'Asia/Jakarta');

        // Contract ends 2026-09-30 (29 days remaining from Sept 1)
        $this->employee->update([
            'contract_end_date' => '2026-09-30',
        ]);

        $days = $this->employee->getDaysUntilContractExpiry($refDate);
        $this->assertEquals(29, $days);
        $this->assertEquals('WARNING_30', $this->employee->getContractExpiryStatus($refDate));
        $this->assertEquals('warning', $this->employee->getContractStatusBadgeColor($refDate));
        $this->assertStringContainsString('H-29', $this->employee->getContractStatusLabel($refDate));

        // Test H-7 (Critical)
        $this->employee->update(['contract_end_date' => '2026-09-08']);
        $days7 = $this->employee->getDaysUntilContractExpiry($refDate);
        $this->assertEquals(7, $days7);
        $this->assertEquals('CRITICAL_7', $this->employee->getContractExpiryStatus($refDate));
        $this->assertEquals('danger', $this->employee->getContractStatusBadgeColor($refDate));

        // Test H-60 (Warning)
        $this->employee->update(['contract_end_date' => '2026-10-31']);
        $days60 = $this->employee->getDaysUntilContractExpiry($refDate);
        $this->assertEquals(60, $days60);
        $this->assertEquals('WARNING_60', $this->employee->getContractExpiryStatus($refDate));
        $this->assertEquals('warning', $this->employee->getContractStatusBadgeColor($refDate));

        // Test H-90 (Early Warning)
        $this->employee->update(['contract_end_date' => '2026-11-30']);
        $days90 = $this->employee->getDaysUntilContractExpiry($refDate);
        $this->assertEquals(90, $days90);
        $this->assertEquals('WARNING_90', $this->employee->getContractExpiryStatus($refDate));
        $this->assertEquals('info', $this->employee->getContractStatusBadgeColor($refDate));

        // Test Expired
        $this->employee->update(['contract_end_date' => '2026-08-25']);
        $daysExpired = $this->employee->getDaysUntilContractExpiry($refDate);
        $this->assertLessThan(0, $daysExpired);
        $this->assertEquals('EXPIRED', $this->employee->getContractExpiryStatus($refDate));
        $this->assertEquals('danger', $this->employee->getContractStatusBadgeColor($refDate));
    }

    public function test_artisan_command_detects_contract_expiry_and_generates_notifications_and_audit(): void
    {
        // Set contract expiry to 7 days from now (critical threshold)
        $targetEndDate = Carbon::now('Asia/Jakarta')->addDays(7)->toDateString();
        $this->employee->update(['contract_end_date' => $targetEndDate]);

        $exitCode = Artisan::call('hris:check-contract-expiry', ['--force' => true]);
        $this->assertEquals(0, $exitCode);

        // Check AuditLog entry
        $audit = AuditLog::where('action', 'CONTRACT_EXPIRY_ALERT')
            ->where('entity_id', $this->employee->id)
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals('PPNPN-2025-001', $audit->new_values['employee_code']);
        $this->assertNotNull($audit->new_values['days_remaining']);

        // Check notification in database
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->adminUser->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_alias_command_contracts_check_expiry_works_identically(): void
    {
        $exitCode = Artisan::call('contracts:check-expiry');
        $this->assertEquals(0, $exitCode);
    }

    public function test_extend_contract_updates_dates_and_logs_audit_preserving_all_history(): void
    {
        // 1. Create historical attendance
        $attendance = Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->employee->position_id,
            'office_location_id' => 1,
            'date' => '2026-05-15',
            'check_in_at' => '2026-05-15 07:30:00',
            'check_out_at' => '2026-05-15 17:00:00',
            'status' => Attendance::STATUS_ON_TIME,
            'check_out_status' => Attendance::CHECK_OUT_ON_TIME,
            'late_minutes' => 0,
            'deduction_percentage' => 0.00,
        ]);

        // 2. Create historical violation
        $catalog = ViolationCatalog::first();
        $violation = EmployeeViolation::create([
            'employee_id' => $this->employee->id,
            'violation_catalog_id' => $catalog->id,
            'supervisor_id' => $this->supervisorUser->id,
            'incident_date' => '2026-05-10',
            'points' => $catalog->points,
            'status' => EmployeeViolation::STATUS_ACTIVE,
            'notes' => 'Catatan disiplin historis',
        ]);

        // 3. Perform contract extension / addendum
        $this->employee->extendContract(
            'SPK-21/WPB.08/PPK/2026',
            '2026-10-01',
            '2027-09-30',
            3500000.00,
            42000000.00,
            'Adendum perpanjangan masa kontrak tahun anggaran 2026/2027',
            $this->adminUser
        );

        $this->employee->refresh();

        // Validate updated metadata
        $this->assertEquals('SPK-21/WPB.08/PPK/2026', $this->employee->spk_number);
        $this->assertEquals('2026-10-01', $this->employee->contract_start_date->toDateString());
        $this->assertEquals('2027-09-30', $this->employee->contract_end_date->toDateString());
        $this->assertEquals(3500000.00, (float) $this->employee->base_salary);
        $this->assertEquals(42000000.00, (float) $this->employee->contract_value);

        // Check AuditLog
        $audit = AuditLog::where('action', 'CONTRACT_EXTENDED')
            ->where('entity_id', $this->employee->id)
            ->first();
        $this->assertNotNull($audit);
        $this->assertEquals($this->adminUser->id, $audit->user_id);
        $this->assertEquals('SPK-21/WPB.08/PPK/2026', $audit->new_values['spk_number']);

        // Verify that historical records are strictly PRESERVED
        $this->assertDatabaseHas('attendances', ['id' => $attendance->id]);
        $this->assertDatabaseHas('employee_violations', ['id' => $violation->id]);
    }

    public function test_mobile_home_and_profile_views_render_contract_expiry_alerts(): void
    {
        // Set contract ending in 15 days (H-15 warning)
        $this->employee->update([
            'contract_end_date' => Carbon::now('Asia/Jakarta')->addDays(15)->toDateString(),
            'contract_value' => 38400000.00,
        ]);

        $this->actingAs($this->karyawanUser);

        // Check mobile home page
        $responseHome = $this->get(route('mobile.home'));
        $responseHome->assertOk();
        $responseHome->assertSee('Pemberitahuan Masa Kontrak SPK');
        $responseHome->assertSee('Masa Kontrak Berakhir dalam 15 Hari');

        // Check mobile profile page
        $responseProfile = $this->get(route('mobile.profile'));
        $responseProfile->assertOk();
        $responseProfile->assertSee('Status Masa Kontrak');
        $responseProfile->assertSee('Perlu Perpanjangan (H-15)');
        $responseProfile->assertSee('Nilai Kontrak Total SPK');
        $responseProfile->assertSee('38.400.000');
    }
}
