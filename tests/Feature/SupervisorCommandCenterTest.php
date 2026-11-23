<?php

namespace Tests\Feature;

use App\Filament\Pages\SupervisorCommandCenter;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use App\Models\ViolationCatalog;
use App\Models\ViolationDraft;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SupervisorCommandCenterTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $supervisorUser;

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

        $this->supervisorUser = User::where('username', 'supervisor')->first() ?? User::whereHas('roles', fn ($q) => $q->where('name', 'supervisor'))->firstOrFail();
        $this->supervisorUser->update(['must_change_password' => false]);

        $this->karyawanUser = User::where('username', 'ppnpn01')->firstOrFail();
        $this->karyawanUser->update(['must_change_password' => false]);

        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->firstOrFail();
        $this->employee->update([
            'supervisor_id' => $this->supervisorUser->id,
            'is_active' => true,
        ]);

        $this->office = OfficeLocation::firstOrFail();
        $this->obPosition = Position::where('code', 'OB')->firstOrFail();
    }

    /**
     * Test FR-702: Supervisor can access command center.
     */
    public function test_supervisor_can_access_command_center(): void
    {
        $response = $this->actingAs($this->supervisorUser)->get('/admin/supervisor-command-center');
        $response->assertOk();
        $response->assertSee('Pusat Kendali Operasional Supervisor');
    }

    /**
     * Test FR-702: Admin can access command center.
     */
    public function test_admin_can_access_command_center(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/supervisor-command-center');
        $response->assertOk();
        $response->assertSee('Pusat Kendali Operasional Supervisor');
    }

    /**
     * Test FR-702: Karyawan cannot access command center (forbidden).
     */
    public function test_karyawan_cannot_access_command_center(): void
    {
        $response = $this->actingAs($this->karyawanUser)->get('/admin/supervisor-command-center');
        $response->assertForbidden();
    }

    /**
     * Test FR-702: Live metrics calculation and team roster rendering.
     */
    public function test_command_center_renders_live_kpi_metrics_and_roster(): void
    {
        $today = Carbon::parse('2026-11-23', 'Asia/Jakarta');
        Carbon::setTestNow($today);

        // Record on-time attendance for subordinate
        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->obPosition->id,
            'office_location_id' => $this->office->id,
            'date' => '2026-11-23',
            'check_in_at' => Carbon::parse('2026-11-23 07:20:00', 'Asia/Jakarta'),
            'status' => Attendance::STATUS_ON_TIME,
            'shift_type' => Attendance::SHIFT_NORMAL,
            'check_in_distance_meters' => 15.0,
        ]);

        Livewire::actingAs($this->supervisorUser)
            ->test(SupervisorCommandCenter::class, ['date' => '2026-11-23'])
            ->assertSee($this->employee->full_name)
            ->assertSee('Tepat Waktu')
            ->assertSee('07:20 WIB');
    }

    /**
     * Test FR-702: Highlighting late personnel with danger badge.
     */
    public function test_command_center_highlights_late_personnel_with_danger_badge(): void
    {
        $today = Carbon::parse('2026-11-23', 'Asia/Jakarta');
        Carbon::setTestNow($today);

        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->obPosition->id,
            'office_location_id' => $this->office->id,
            'date' => '2026-11-23',
            'check_in_at' => Carbon::parse('2026-11-23 08:15:00', 'Asia/Jakarta'),
            'status' => Attendance::STATUS_LATE_TL,
            'shift_type' => Attendance::SHIFT_NORMAL,
            'late_minutes' => 45,
            'tl_category' => Attendance::TL_2,
        ]);

        Livewire::actingAs($this->supervisorUser)
            ->test(SupervisorCommandCenter::class, [
                'date' => '2026-11-23',
                'tab' => 'late',
            ])
            ->set('activeTab', 'late')
            ->assertSee($this->employee->full_name)
            ->assertSee('45 Menit')
            ->assertSee('TL 2 (31-60 Menit)')
            ->assertSee('TERLAMBAT TL');
    }

    /**
     * Test FR-702 / FR-209: Supervisor approves fallback biometric check-in.
     */
    public function test_supervisor_can_approve_fallback_biometric_via_quick_queue(): void
    {
        $attendance = Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->obPosition->id,
            'office_location_id' => $this->office->id,
            'date' => '2026-11-23',
            'check_in_at' => Carbon::parse('2026-11-23 07:15:00', 'Asia/Jakarta'),
            'status' => Attendance::STATUS_ON_TIME,
            'shift_type' => Attendance::SHIFT_NORMAL,
            'is_fallback' => true,
            'fallback_reason' => 'Pencahayaan silau lapangan',
            'fallback_status' => Attendance::FALLBACK_PENDING,
        ]);

        Livewire::actingAs($this->supervisorUser)
            ->test(SupervisorCommandCenter::class)
            ->call('approveFallback', $attendance->id)
            ->assertNotified();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'fallback_status' => Attendance::FALLBACK_APPROVED,
            'fallback_verified_by' => $this->supervisorUser->id,
        ]);
    }

    /**
     * Test FR-702 / FR-209: Supervisor rejects fallback biometric check-in (converted to ALPHA).
     */
    public function test_supervisor_can_reject_fallback_biometric_via_quick_queue(): void
    {
        $attendance = Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->obPosition->id,
            'office_location_id' => $this->office->id,
            'date' => '2026-11-23',
            'check_in_at' => Carbon::parse('2026-11-23 07:15:00', 'Asia/Jakarta'),
            'status' => Attendance::STATUS_ON_TIME,
            'shift_type' => Attendance::SHIFT_NORMAL,
            'is_fallback' => true,
            'fallback_reason' => 'Foto gelap',
            'fallback_status' => Attendance::FALLBACK_PENDING,
        ]);

        Livewire::actingAs($this->supervisorUser)
            ->test(SupervisorCommandCenter::class)
            ->call('rejectFallback', $attendance->id, 'Wajah tidak teridentifikasi')
            ->assertNotified();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'fallback_status' => Attendance::FALLBACK_REJECTED,
            'status' => Attendance::STATUS_ALPHA,
            'fallback_verified_by' => $this->supervisorUser->id,
        ]);
    }

    /**
     * Test FR-702 / FR-504: Supervisor verifies leave request tier 1.
     */
    public function test_supervisor_can_verify_leave_request_tier1_via_quick_queue(): void
    {
        $leaveRequest = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => '2026-11-25',
            'end_date' => '2026-11-26',
            'total_days' => 2,
            'reason' => 'Keperluan keluarga',
            'supervisor_id' => $this->supervisorUser->id,
            'supervisor_status' => LeaveRequest::SUPERVISOR_STATUS_PENDING,
            'status' => LeaveRequest::STATUS_PENDING_SUPERVISOR,
        ]);

        Livewire::actingAs($this->supervisorUser)
            ->test(SupervisorCommandCenter::class)
            ->call('verifyLeave', $leaveRequest->id, true)
            ->assertNotified();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'supervisor_status' => LeaveRequest::SUPERVISOR_STATUS_VERIFIED,
            'status' => LeaveRequest::STATUS_PENDING_ATASAN,
        ]);
    }

    /**
     * Test FR-702 / FR-406: Supervisor confirms violation draft into active violation.
     */
    public function test_supervisor_can_confirm_violation_draft_via_quick_queue(): void
    {
        $catalog = ViolationCatalog::firstOrFail();

        $draft = ViolationDraft::create([
            'employee_id' => $this->employee->id,
            'violation_catalog_id' => $catalog->id,
            'trigger_type' => ViolationDraft::TRIGGER_CONSECUTIVE_ALPHA,
            'incident_date' => '2026-11-20',
            'auto_generated_notes' => 'Alpa 2 hari berturut-turut',
            'status' => ViolationDraft::STATUS_PENDING,
        ]);

        Livewire::actingAs($this->supervisorUser)
            ->test(SupervisorCommandCenter::class)
            ->call('confirmViolationDraft', $draft->id)
            ->assertNotified();

        $this->assertDatabaseHas('violation_drafts', [
            'id' => $draft->id,
            'status' => ViolationDraft::STATUS_CONFIRMED,
            'confirmed_by_id' => $this->supervisorUser->id,
        ]);

        $this->assertDatabaseHas('employee_violations', [
            'employee_id' => $this->employee->id,
            'violation_catalog_id' => $catalog->id,
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Test FR-702 / FR-406: Supervisor dismisses violation draft.
     */
    public function test_supervisor_can_dismiss_violation_draft_via_quick_queue(): void
    {
        $catalog = ViolationCatalog::firstOrFail();

        $draft = ViolationDraft::create([
            'employee_id' => $this->employee->id,
            'violation_catalog_id' => $catalog->id,
            'trigger_type' => ViolationDraft::TRIGGER_REPEATED_PSW,
            'incident_date' => '2026-11-20',
            'auto_generated_notes' => 'PSW berulang',
            'status' => ViolationDraft::STATUS_PENDING,
        ]);

        Livewire::actingAs($this->supervisorUser)
            ->test(SupervisorCommandCenter::class)
            ->call('dismissViolationDraft', $draft->id, 'Tugas dinas luar kota mendesak')
            ->assertNotified();

        $this->assertDatabaseHas('violation_drafts', [
            'id' => $draft->id,
            'status' => ViolationDraft::STATUS_DISMISSED,
            'dismissed_by_id' => $this->supervisorUser->id,
        ]);
    }
}
