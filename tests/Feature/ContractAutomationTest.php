<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeViolation;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use App\Models\ViolationCatalog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $karyawanUser;

    protected Employee $employee;

    protected OfficeLocation $office;

    protected Position $obPosition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Storage::fake('public');

        $this->adminUser = User::where('username', 'admin')->first() ?? User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->firstOrFail();
        $this->adminUser->update(['must_change_password' => false]);

        $this->karyawanUser = User::where('username', 'ppnpn01')->firstOrFail();
        $this->karyawanUser->update(['must_change_password' => false]);

        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->firstOrFail();
        $this->employee->update([
            'is_active' => true,
            'spk_number' => 'SPK/KUM/2025/001',
            'contract_start_date' => '2025-11-21',
            'contract_end_date' => '2026-11-20',
        ]);

        $this->office = OfficeLocation::firstOrFail();
        $this->obPosition = Position::where('code', 'OB')->firstOrFail();
    }

    /**
     * Test FR-604: Inactivate expired contracts command inactivates employees whose contract ended yesterday or earlier.
     */
    public function test_inactivate_expired_contracts_command_inactivates_expired_employee(): void
    {
        // Evaluation date is 2026-11-21 (T+1), contract ended 2026-11-20 (T)
        $this->artisan('hris:inactivate-expired-contracts', ['--date' => '2026-11-21'])
            ->expectsOutputToContain('Berhasil menonaktifkan 1 pegawai yang kontraknya telah berakhir.')
            ->assertSuccessful();

        $this->employee->refresh();
        $this->karyawanUser->refresh();

        $this->assertFalse($this->employee->is_active);
        $this->assertFalse($this->karyawanUser->is_active);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => Employee::class,
            'entity_id' => $this->employee->id,
            'action' => 'CONTRACT_AUTO_INACTIVATED',
        ]);
    }

    /**
     * Test FR-604: Active contracts in the future are NOT inactivated.
     */
    public function test_active_contracts_are_not_inactivated(): void
    {
        // Contract valid until end of year
        $this->employee->update([
            'contract_end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $this->artisan('hris:inactivate-expired-contracts', ['--date' => '2026-11-21'])
            ->expectsOutputToContain('Nihil: Tidak ada pegawai aktif dengan kontrak kedaluwarsa')
            ->assertSuccessful();

        $this->employee->refresh();
        $this->assertTrue($this->employee->is_active);
    }

    /**
     * Test FR-604: Inactivated employee is blocked from login.
     */
    public function test_inactivated_employee_is_blocked_from_login(): void
    {
        // Deactivate user
        $this->karyawanUser->update(['is_active' => false]);
        $this->employee->update(['is_active' => false]);

        $response = $this->post('/login', [
            'login' => $this->karyawanUser->username,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * Test FR-604: Inactivated employee is blocked from attendance scanner.
     */
    public function test_inactivated_employee_is_blocked_from_attendance_scanner(): void
    {
        $this->employee->update(['is_active' => false]);

        $response = $this->actingAs($this->karyawanUser)->get('/mobile/attendance');

        $response->assertRedirect('/mobile/home');
        $response->assertSessionHas('error');
    }

    /**
     * Test FR-604: Inactivated employee is blocked from check-in and fallback APIs.
     */
    public function test_inactivated_employee_is_blocked_from_check_in_apis(): void
    {
        $this->employee->update(['is_active' => false]);

        // Regular check-in
        $responseCheckIn = $this->actingAs($this->karyawanUser)->postJson('/mobile/attendance/check-in', [
            'position_id' => $this->obPosition->id,
            'latitude' => -5.4295,
            'longitude' => 105.2625,
            'liveness_verified' => true,
            'photo' => 'data:image/jpeg;base64,...',
        ]);

        $responseCheckIn->assertStatus(403);
        $responseCheckIn->assertJson([
            'success' => false,
        ]);

        // Fallback check-in
        $responseFallback = $this->actingAs($this->karyawanUser)->postJson('/mobile/attendance/fallback-check-in', [
            'position_id' => $this->obPosition->id,
            'latitude' => -5.4295,
            'longitude' => 105.2625,
            'fallback_reason' => 'Kamera silau',
            'photo' => 'data:image/jpeg;base64,...',
        ]);

        $responseFallback->assertStatus(403);
        $responseFallback->assertJson([
            'success' => false,
        ]);
    }

    /**
     * Test FR-604 / FR-603: Historical attendance and violation records are strictly preserved after inactivation.
     */
    public function test_historical_records_are_strictly_preserved_after_inactivation(): void
    {
        // 1. Create past attendance record
        $pastAttendance = Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->obPosition->id,
            'office_location_id' => $this->office->id,
            'date' => '2026-11-10',
            'check_in_at' => Carbon::parse('2026-11-10 07:20:00', 'Asia/Jakarta'),
            'status' => Attendance::STATUS_ON_TIME,
            'shift_type' => Attendance::SHIFT_NORMAL,
        ]);

        // 2. Create past violation record
        $catalog = ViolationCatalog::firstOrFail();
        $supervisor = User::where('username', 'supervisor')->first() ?? $this->adminUser;
        $pastViolation = EmployeeViolation::create([
            'employee_id' => $this->employee->id,
            'supervisor_id' => $supervisor->id,
            'violation_catalog_id' => $catalog->id,
            'incident_date' => '2026-11-05',
            'notes' => 'Catatan historis sebelum kontrak habis',
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        // 3. Run inactivation command
        $this->artisan('hris:inactivate-expired-contracts', ['--date' => '2026-11-21'])
            ->assertSuccessful();

        $this->employee->refresh();
        $this->assertFalse($this->employee->is_active);

        // 4. Verify historical data is intact
        $this->assertDatabaseHas('attendances', [
            'id' => $pastAttendance->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->assertDatabaseHas('employee_violations', [
            'id' => $pastViolation->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->assertEquals(1, $this->employee->attendances()->count());
        $this->assertEquals(1, $this->employee->violations()->count());
    }

    /**
     * Test FR-603: Contract extension reactivates employee and preserves historical data.
     */
    public function test_contract_extension_reactivates_inactivated_employee(): void
    {
        // First inactivate
        $this->employee->update(['is_active' => false]);
        $this->karyawanUser->update(['is_active' => false]);

        // Extend contract
        $this->employee->extendContract(
            newSpkNumber: 'SPK/KUM/2026/099',
            newStartDate: '2026-11-21',
            newEndDate: '2027-11-20',
            newSalary: 3100000.00,
            notes: 'Perpanjangan kontrak setelah sempat inaktif',
            actor: $this->adminUser
        );

        // Also reactivate user
        $this->employee->user?->update(['is_active' => true]);

        $this->employee->refresh();
        $this->karyawanUser->refresh();

        $this->assertTrue($this->employee->is_active);
        $this->assertTrue($this->karyawanUser->is_active);
        $this->assertEquals('SPK/KUM/2026/099', $this->employee->spk_number);
        $this->assertEquals('2027-11-20', $this->employee->contract_end_date->toDateString());
    }
}
