<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\EmployeeViolation;
use App\Models\Position;
use App\Models\User;
use App\Models\ViolationCatalog;
use App\Models\ViolationDraft;
use App\Services\ViolationDraftService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViolationDraftTest extends TestCase
{
    use RefreshDatabase;

    protected User $supervisorUser;

    protected Employee $employee;

    protected Position $obPosition;

    protected ViolationCatalog $clauseAlpha;

    protected ViolationCatalog $clauseLupaAbsen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->supervisorUser = User::where('username', 'supervisor')->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'supervisor'))->firstOrFail();

        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->firstOrFail();
        $this->obPosition = Position::where('code', 'OB')->firstOrFail();

        $this->clauseAlpha = ViolationCatalog::where('clause_number', 'A12')->firstOrFail();
        $this->clauseLupaAbsen = ViolationCatalog::where('clause_number', 'A14')->firstOrFail();
    }

    /**
     * Test FR-406: System detects >= 2 consecutive Alpa and creates draft.
     */
    public function test_system_generates_draft_for_consecutive_alpa(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 23:00:00', 'Asia/Jakarta'));

        // Monday 23 Nov and Tuesday 24 Nov Alpa
        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->obPosition->id,
            'date' => '2026-11-23',
            'status' => Attendance::STATUS_ALPHA,
            'shift_type' => Attendance::SHIFT_NORMAL,
        ]);

        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->obPosition->id,
            'date' => '2026-11-24',
            'status' => Attendance::STATUS_ALPHA,
            'shift_type' => Attendance::SHIFT_NORMAL,
        ]);

        /** @var ViolationDraftService $service */
        $service = app(ViolationDraftService::class);
        $count = $service->scanAndGenerateDrafts(now());

        $this->assertGreaterThanOrEqual(1, $count);

        $this->assertDatabaseHas('violation_drafts', [
            'employee_id' => $this->employee->id,
            'trigger_type' => ViolationDraft::TRIGGER_CONSECUTIVE_ALPHA,
            'status' => ViolationDraft::STATUS_PENDING,
            'violation_catalog_id' => $this->clauseAlpha->id,
        ]);
    }

    /**
     * Test FR-406: System generates draft for rejected attendance correction.
     */
    public function test_system_generates_draft_for_rejected_attendance_correction(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-25 23:00:00', 'Asia/Jakarta'));

        AttendanceCorrection::create([
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-11-20',
            'correction_type' => AttendanceCorrection::TYPE_BOTH,
            'work_summary' => 'Lupa absen dinas luar kota.',
            'status' => AttendanceCorrection::STATUS_REJECTED,
            'atasan_notes' => 'Tidak ada bukti surat tugas resmi yang sah.',
        ]);

        /** @var ViolationDraftService $service */
        $service = app(ViolationDraftService::class);
        $service->scanAndGenerateDrafts(now());

        $this->assertDatabaseHas('violation_drafts', [
            'employee_id' => $this->employee->id,
            'trigger_type' => ViolationDraft::TRIGGER_REJECTED_ATTENDANCE_CORRECTION,
            'status' => ViolationDraft::STATUS_PENDING,
        ]);
    }

    /**
     * Test FR-406: System generates draft for rejected fallback check-in.
     */
    public function test_system_generates_draft_for_rejected_fallback(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-25 23:00:00', 'Asia/Jakarta'));

        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->obPosition->id,
            'date' => '2026-11-21',
            'status' => Attendance::STATUS_ALPHA,
            'shift_type' => Attendance::SHIFT_NORMAL,
            'is_fallback' => true,
            'fallback_reason' => 'Kamera rusak',
            'fallback_status' => Attendance::FALLBACK_REJECTED,
            'fallback_notes' => 'Foto swafoto gelap gulita tidak dapat diidentifikasi.',
        ]);

        /** @var ViolationDraftService $service */
        $service = app(ViolationDraftService::class);
        $service->scanAndGenerateDrafts(now());

        $this->assertDatabaseHas('violation_drafts', [
            'employee_id' => $this->employee->id,
            'trigger_type' => ViolationDraft::TRIGGER_REJECTED_FALLBACK,
            'status' => ViolationDraft::STATUS_PENDING,
        ]);
    }

    /**
     * Test FR-406: System generates draft for repeated PSW (>= 3 times in month).
     */
    public function test_system_generates_draft_for_repeated_psw(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-25 23:00:00', 'Asia/Jakarta'));

        foreach (['2026-11-10', '2026-11-12', '2026-11-16'] as $date) {
            Attendance::create([
                'employee_id' => $this->employee->id,
                'position_id' => $this->obPosition->id,
                'date' => $date,
                'check_in_at' => Carbon::parse("{$date} 07:15:00"),
                'check_out_at' => Carbon::parse("{$date} 15:30:00"),
                'check_out_status' => Attendance::CHECK_OUT_PSW,
                'early_leave_minutes' => 90,
                'status' => Attendance::STATUS_EARLY_LEAVE,
                'shift_type' => Attendance::SHIFT_NORMAL,
            ]);
        }

        /** @var ViolationDraftService $service */
        $service = app(ViolationDraftService::class);
        $service->scanAndGenerateDrafts(now());

        $this->assertDatabaseHas('violation_drafts', [
            'employee_id' => $this->employee->id,
            'trigger_type' => ViolationDraft::TRIGGER_REPEATED_PSW,
            'status' => ViolationDraft::STATUS_PENDING,
        ]);
    }

    /**
     * Test FR-406: Scanning is idempotent and does not create duplicate drafts.
     */
    public function test_system_avoids_duplicate_drafts(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 23:00:00', 'Asia/Jakarta'));

        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->obPosition->id,
            'date' => '2026-11-23',
            'status' => Attendance::STATUS_ALPHA,
            'shift_type' => Attendance::SHIFT_NORMAL,
        ]);

        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->obPosition->id,
            'date' => '2026-11-24',
            'status' => Attendance::STATUS_ALPHA,
            'shift_type' => Attendance::SHIFT_NORMAL,
        ]);

        /** @var ViolationDraftService $service */
        $service = app(ViolationDraftService::class);
        $firstScan = $service->scanAndGenerateDrafts(now());
        $this->assertEquals(1, $firstScan);

        // Second scan should create 0 new drafts
        $secondScan = $service->scanAndGenerateDrafts(now());
        $this->assertEquals(0, $secondScan);
    }

    /**
     * Test FR-406: Supervisor confirms draft, creating active EmployeeViolation.
     */
    public function test_supervisor_can_confirm_draft_into_employee_violation(): void
    {
        $draft = ViolationDraft::create([
            'employee_id' => $this->employee->id,
            'violation_catalog_id' => $this->clauseAlpha->id,
            'trigger_type' => ViolationDraft::TRIGGER_CONSECUTIVE_ALPHA,
            'incident_date' => '2026-11-24',
            'auto_generated_notes' => 'Alpa 2 hari berturut-turut tanpa izin.',
            'status' => ViolationDraft::STATUS_PENDING,
        ]);

        /** @var ViolationDraftService $service */
        $service = app(ViolationDraftService::class);
        $violation = $service->confirmDraft($draft, $this->supervisorUser, [
            'notes' => 'Telah dikonfirmasi tidak hadir tanpa keterangan.',
        ]);

        $draft->refresh();
        $this->assertEquals(ViolationDraft::STATUS_CONFIRMED, $draft->status);
        $this->assertEquals($this->supervisorUser->id, $draft->confirmed_by_id);
        $this->assertEquals($violation->id, $draft->employee_violation_id);

        $this->assertDatabaseHas('employee_violations', [
            'id' => $violation->id,
            'employee_id' => $this->employee->id,
            'status' => EmployeeViolation::STATUS_ACTIVE,
            'points' => $this->clauseAlpha->points,
        ]);
    }

    /**
     * Test FR-406: Supervisor dismisses draft with official reason.
     */
    public function test_supervisor_can_dismiss_draft_with_reason(): void
    {
        $draft = ViolationDraft::create([
            'employee_id' => $this->employee->id,
            'violation_catalog_id' => $this->clauseAlpha->id,
            'trigger_type' => ViolationDraft::TRIGGER_CONSECUTIVE_ALPHA,
            'incident_date' => '2026-11-24',
            'auto_generated_notes' => 'Alpa 2 hari berturut-turut.',
            'status' => ViolationDraft::STATUS_PENDING,
        ]);

        /** @var ViolationDraftService $service */
        $service = app(ViolationDraftService::class);
        $service->dismissDraft($draft, $this->supervisorUser, 'Pegawai telah menyerahkan surat tugas susulan dari Kasubbag.');

        $draft->refresh();
        $this->assertEquals(ViolationDraft::STATUS_DISMISSED, $draft->status);
        $this->assertEquals($this->supervisorUser->id, $draft->dismissed_by_id);
        $this->assertStringContainsString('surat tugas susulan', $draft->dismissal_reason);

        $this->assertDatabaseMissing('employee_violations', [
            'employee_id' => $this->employee->id,
            'notes' => $draft->auto_generated_notes,
        ]);
    }

    /**
     * Test FR-406: Scheduled Artisan command runs successfully.
     */
    public function test_artisan_command_generates_violation_drafts(): void
    {
        $this->artisan('hris:generate-violation-drafts', ['--date' => '2026-11-24'])
            ->assertSuccessful();
    }
}
