<?php

namespace Tests\Feature;

use App\Filament\Pages\TeamAvailabilityCalendar;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Position;
use App\Models\User;
use App\Services\LeaveRequestService;
use Carbon\Carbon;
use Database\Seeders\SampleFiveEmployeesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeaveQuotaAndSlaGuardTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $supervisorUser;

    protected User $atasanUser;

    protected User $karyawanUser;

    protected Employee $employee;

    protected LeaveRequestService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->seed(SampleFiveEmployeesSeeder::class);

        $this->adminUser = User::where('username', 'admin')->first();
        $this->supervisorUser = User::where('username', 'supervisor')->first();
        $this->atasanUser = User::where('username', 'atasan')->first();
        $this->karyawanUser = User::where('username', 'ppnpn01')->first();
        $this->karyawanUser->update(['must_change_password' => false]);

        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->first();
        $this->employee->update([
            'supervisor_id' => $this->supervisorUser->id,
            'atasan_id' => $this->atasanUser->id,
            'leave_quota_balance' => 12,
        ]);

        $this->service = app(LeaveRequestService::class);
    }

    /**
     * Helper to get a stable upcoming Monday.
     */
    protected function getNextMonday(): Carbon
    {
        return Carbon::now()->next(Carbon::MONDAY);
    }

    public function test_quota_guard_strictly_prevents_annual_leave_exceeding_balance(): void
    {
        $this->employee->update(['leave_quota_balance' => 2]);

        $start = $this->getNextMonday();
        $end = $start->copy()->addDays(2); // 3 hari kerja > saldo 2

        $this->expectException(ValidationException::class);

        $this->service->submit($this->employee, [
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'reason' => 'Pengajuan melebihi kuota cuti tahunan.',
        ]);
    }

    public function test_three_consecutive_days_guard_strictly_rejects_longer_periods(): void
    {
        $start = $this->getNextMonday();
        $end = $start->copy()->addDays(3); // 4 hari kerja berturut-turut

        $this->expectException(ValidationException::class);

        $this->service->submit($this->employee, [
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'reason' => 'Pengajuan 4 hari kerja sekaligus.',
        ]);
    }

    public function test_sla_calculation_within_2_working_days_is_not_overdue(): void
    {
        // Simulasi pengajuan dibuat 1 hari kerja yang lalu (misal Selasa, dicek Rabu)
        $tuesday = Carbon::parse('2026-11-24 08:00:00'); // Tuesday
        $wednesday = Carbon::parse('2026-11-25 15:00:00'); // Wednesday

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'total_days' => 2,
            'reason' => 'Keperluan keluarga.',
            'status' => LeaveRequest::STATUS_PENDING_SUPERVISOR,
        ]);
        $leaveRequest->timestamps = false;
        $leaveRequest->created_at = $tuesday;
        $leaveRequest->save();
        $leaveRequest->timestamps = true;

        $pendingDays = $leaveRequest->getPendingWorkingDays($wednesday);
        $this->assertEquals(1, $pendingDays);
        $this->assertFalse($leaveRequest->isOverdueSla($wednesday));
        $this->assertEquals('success', $leaveRequest->getSlaBadgeColor($wednesday));
        $this->assertStringContainsString('Sesuai SLA', $leaveRequest->getSlaStatusLabel($wednesday));
    }

    public function test_sla_calculation_exceeding_2_working_days_is_flagged_overdue(): void
    {
        // Simulasi pengajuan dibuat hari Senin, dicek hari Kamis (3 hari kerja berlalu > batas 2 hari)
        $monday = Carbon::parse('2026-11-23 08:00:00'); // Monday
        $thursday = Carbon::parse('2026-11-26 10:00:00'); // Thursday (Sel, Rab, Kam = 3 hari kerja)

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'total_days' => 2,
            'reason' => 'Keperluan keluarga.',
            'status' => LeaveRequest::STATUS_PENDING_SUPERVISOR,
        ]);
        $leaveRequest->timestamps = false;
        $leaveRequest->created_at = $monday;
        $leaveRequest->save();
        $leaveRequest->timestamps = true;

        $pendingDays = $leaveRequest->getPendingWorkingDays($thursday);
        $this->assertEquals(3, $pendingDays);
        $this->assertTrue($leaveRequest->isOverdueSla($thursday));
        $this->assertEquals('danger', $leaveRequest->getSlaBadgeColor($thursday));
        $this->assertStringContainsString('Overdue SLA', $leaveRequest->getSlaStatusLabel($thursday));
    }

    public function test_sla_resets_clock_for_atasan_stage(): void
    {
        // Tahap 1 selesai pada hari Rabu, tahap 2 dicek hari Jumat (2 hari kerja berlalu: Kam, Jum)
        $wednesday = Carbon::parse('2026-11-25 09:00:00');
        $friday = Carbon::parse('2026-11-27 16:00:00');

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'total_days' => 2,
            'reason' => 'Cuti.',
            'supervisor_id' => $this->supervisorUser->id,
            'supervisor_status' => LeaveRequest::SUPERVISOR_STATUS_VERIFIED,
            'supervisor_action_at' => $wednesday,
            'status' => LeaveRequest::STATUS_PENDING_ATASAN,
            'created_at' => Carbon::parse('2026-11-20'), // created long ago
        ]);

        // Pada hari Jumat, durasi di tahap atasan baru 2 hari kerja -> belum overdue
        $this->assertEquals(2, $leaveRequest->getPendingWorkingDays($friday));
        $this->assertFalse($leaveRequest->isOverdueSla($friday));
        $this->assertEquals('warning', $leaveRequest->getSlaBadgeColor($friday));

        // Pada hari Senin berikutnya (3 hari kerja: Kam, Jum, Sen) -> overdue
        $nextMonday = Carbon::parse('2026-11-30 09:00:00');
        $this->assertEquals(3, $leaveRequest->getPendingWorkingDays($nextMonday));
        $this->assertTrue($leaveRequest->isOverdueSla($nextMonday));
    }

    public function test_sla_is_completed_when_approved(): void
    {
        $leaveRequest = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'total_days' => 2,
            'reason' => 'Cuti.',
            'status' => LeaveRequest::STATUS_APPROVED,
            'approved_at' => now(),
            'created_at' => now()->subDays(10),
        ]);

        $this->assertFalse($leaveRequest->isOverdueSla());
        $this->assertEquals('Selesai', $leaveRequest->getSlaStatusLabel());
        $this->assertEquals('gray', $leaveRequest->getSlaBadgeColor());
    }

    public function test_team_availability_calendar_matrix_and_conflict_detection(): void
    {
        // Siapkan 2 pegawai pada posisi Pengemudi (DRIVER) di bawah supervisor yang sama
        $driverPosition = Position::where('code', 'DRIVER')->first();

        $driver1 = Employee::where('employee_code', 'PPNPN-2025-002')->first();
        $driver1->update([
            'supervisor_id' => $this->supervisorUser->id,
            'position_id' => $driverPosition->id,
        ]);

        $userDriver2 = User::create([
            'username' => 'driver2_test',
            'name' => 'Driver Dua',
            'email' => 'driver2@djpb.kemenkeu.go.id',
            'password' => bcrypt('password'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $userDriver2->syncRoles(['karyawan']);

        $driver2 = Employee::create([
            'user_id' => $userDriver2->id,
            'employee_code' => 'PPNPN-TEST-DRV2',
            'full_name' => 'Driver Dua Test',
            'gender' => 'L',
            'phone_number' => '081299990001',
            'position_id' => $driverPosition->id,
            'supervisor_id' => $this->supervisorUser->id,
            'base_salary' => 3000000,
            'leave_quota_balance' => 12,
            'is_active' => true,
        ]);

        // Buat pengajuan cuti yang disetujui untuk Driver 1 pada 2026-11-16
        LeaveRequest::create([
            'employee_id' => $driver1->id,
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => '2026-11-16',
            'end_date' => '2026-11-16',
            'total_days' => 1,
            'reason' => 'Cuti Driver 1',
            'status' => LeaveRequest::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        // Buat pengajuan cuti pending untuk Driver 2 pada tanggal yang sama: 2026-11-16
        LeaveRequest::create([
            'employee_id' => $driver2->id,
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => '2026-11-16',
            'end_date' => '2026-11-16',
            'total_days' => 1,
            'reason' => 'Cuti Driver 2',
            'status' => LeaveRequest::STATUS_PENDING_SUPERVISOR,
        ]);

        // Panggil service getTeamAvailability untuk November 2026 dan posisi DRIVER
        $availability = $this->service->getTeamAvailability(2026, 11, $this->supervisorUser->id, $driverPosition->id);

        $this->assertEquals(2026, $availability['year']);
        $this->assertEquals(11, $availability['month']);
        $this->assertEquals(2, $availability['total_employees']);

        // Cek bahwa matriks hari 2026-11-16 memuat status cuti untuk kedua driver
        $matrixDriver1 = collect($availability['matrix'])->firstWhere('id', $driver1->id);
        $matrixDriver2 = collect($availability['matrix'])->firstWhere('id', $driver2->id);

        $this->assertEquals('LEAVE_APPROVED', $matrixDriver1['days']['2026-11-16']['status']);
        $this->assertEquals('LEAVE_PENDING', $matrixDriver2['days']['2026-11-16']['status']);

        // Cek deteksi bentrok (karena 2 dari 2 driver absen pada tanggal tersebut: 100% >= 50%)
        $this->assertNotEmpty($availability['conflicts']);
        $conflictDate = collect($availability['conflicts'])->firstWhere('date', '2026-11-16');
        $this->assertNotNull($conflictDate);
        $this->assertEquals(2, $conflictDate['absent_count']);
        $this->assertEquals(2, $conflictDate['total_count']);
        $this->assertEquals(100, $conflictDate['percentage']);
    }

    public function test_supervisor_can_access_team_availability_calendar(): void
    {
        $this->actingAs($this->supervisorUser)
            ->get(TeamAvailabilityCalendar::getUrl())
            ->assertOk();
    }

    public function test_atasan_can_access_team_availability_calendar(): void
    {
        $this->actingAs($this->atasanUser)
            ->get(TeamAvailabilityCalendar::getUrl())
            ->assertOk();
    }

    public function test_admin_can_access_team_availability_calendar(): void
    {
        $this->actingAs($this->adminUser)
            ->get(TeamAvailabilityCalendar::getUrl())
            ->assertOk();
    }

    public function test_karyawan_cannot_access_team_availability_calendar(): void
    {
        $this->actingAs($this->karyawanUser)
            ->get(TeamAvailabilityCalendar::getUrl())
            ->assertForbidden();
    }
}
