<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\EmployeeViolation;
use App\Models\LateException;
use App\Models\LeaveRequest;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use App\Models\ViolationCatalog;
use App\Notifications\AttendanceCorrectionFinalDecisionNotification;
use App\Notifications\AttendanceCorrectionSubmittedNotification;
use App\Notifications\ContractExpiringAlertNotification;
use App\Notifications\LateExceptionFinalDecisionNotification;
use App\Notifications\LateExceptionSubmittedNotification;
use App\Notifications\LeaveRequestApprovedTier1Notification;
use App\Notifications\LeaveRequestFinalDecisionNotification;
use App\Notifications\LeaveRequestSubmittedNotification;
use App\Notifications\ViolationRecordedNotification;
use App\Services\AttendanceCorrectionService;
use App\Services\InAppNotificationService;
use App\Services\LateExceptionService;
use App\Services\LeaveRequestService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $karyawanUser;

    protected User $supervisorUser;

    protected User $atasanUser;

    protected User $adminUser;

    protected Employee $employee;

    protected OfficeLocation $office;

    protected Position $obPosition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Storage::fake('public');

        $this->karyawanUser = User::where('username', 'ppnpn01')->firstOrFail();
        $this->karyawanUser->update(['must_change_password' => false]);

        $this->supervisorUser = User::where('username', 'supervisor')->first() ?? User::whereHas('roles', fn ($q) => $q->where('name', 'supervisor'))->firstOrFail();
        $this->atasanUser = User::where('username', 'atasan')->first() ?? User::whereHas('roles', fn ($q) => $q->where('name', 'atasan'))->firstOrFail();
        $this->adminUser = User::where('username', 'admin')->first() ?? User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->firstOrFail();

        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->firstOrFail();
        $this->employee->update([
            'supervisor_id' => $this->supervisorUser->id,
            'atasan_id' => $this->atasanUser->id,
            'leave_quota_balance' => 12,
        ]);

        $this->office = OfficeLocation::firstOrFail();
        $this->obPosition = Position::where('code', 'OB')->firstOrFail();
    }

    /**
     * Test FR-701: Leave request submission triggers notification to supervisor.
     */
    public function test_leave_request_submission_dispatches_notification_to_supervisor(): void
    {
        Notification::fake();

        $start = Carbon::now()->next(Carbon::MONDAY);
        $end = $start->copy()->addDay();

        $leaveRequestService = app(LeaveRequestService::class);
        $leaveRequest = $leaveRequestService->submit($this->employee, [
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'reason' => 'Keperluan keluarga di luar kota',
        ]);

        Notification::assertSentTo(
            $this->supervisorUser,
            LeaveRequestSubmittedNotification::class,
            function (LeaveRequestSubmittedNotification $notification) use ($leaveRequest) {
                return $notification->leaveRequest->id === $leaveRequest->id;
            }
        );
    }

    /**
     * Test FR-701: Leave request tier 1 verification dispatches notification to atasan.
     */
    public function test_leave_request_tier1_approval_dispatches_notification_to_atasan(): void
    {
        Notification::fake();

        $start = Carbon::now()->next(Carbon::MONDAY);
        $end = $start->copy()->addDay();

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'total_days' => 2,
            'reason' => 'Keperluan keluarga',
            'supervisor_id' => $this->supervisorUser->id,
            'supervisor_status' => LeaveRequest::SUPERVISOR_STATUS_PENDING,
            'status' => LeaveRequest::STATUS_PENDING_SUPERVISOR,
        ]);

        $leaveRequestService = app(LeaveRequestService::class);
        $leaveRequestService->verifyBySupervisor($leaveRequest, $this->supervisorUser, 'Disetujui untuk diteruskan');

        Notification::assertSentTo(
            $this->atasanUser,
            LeaveRequestApprovedTier1Notification::class,
            function (LeaveRequestApprovedTier1Notification $notification) use ($leaveRequest) {
                return $notification->leaveRequest->id === $leaveRequest->id;
            }
        );
    }

    /**
     * Test FR-701: Final decision on leave request dispatches notification to employee.
     */
    public function test_leave_request_final_decision_dispatches_notification_to_employee(): void
    {
        Notification::fake();

        $start = Carbon::now()->next(Carbon::MONDAY);
        $end = $start->copy()->addDay();

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'total_days' => 2,
            'reason' => 'Keperluan keluarga',
            'supervisor_id' => $this->supervisorUser->id,
            'supervisor_status' => LeaveRequest::SUPERVISOR_STATUS_VERIFIED,
            'status' => LeaveRequest::STATUS_PENDING_ATASAN,
        ]);

        $leaveRequestService = app(LeaveRequestService::class);
        $leaveRequestService->approveByAtasan($leaveRequest, $this->atasanUser, true, 'Disetujui penuh');

        Notification::assertSentTo(
            $this->karyawanUser,
            LeaveRequestFinalDecisionNotification::class,
            function (LeaveRequestFinalDecisionNotification $notification) use ($leaveRequest) {
                return $notification->leaveRequest->id === $leaveRequest->id && $notification->leaveRequest->isApproved();
            }
        );
    }

    /**
     * Test FR-701 / FR-406: Violation recording dispatches notification to employee.
     */
    public function test_violation_recording_dispatches_notification_to_employee(): void
    {
        Notification::fake();

        $catalog = ViolationCatalog::firstOrFail();

        $violation = EmployeeViolation::create([
            'employee_id' => $this->employee->id,
            'supervisor_id' => $this->supervisorUser->id,
            'violation_catalog_id' => $catalog->id,
            'incident_date' => now()->toDateString(),
            'notes' => 'Pencatatan sanksi disiplin pengujian notifikasi',
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        Notification::assertSentTo(
            $this->karyawanUser,
            ViolationRecordedNotification::class,
            function (ViolationRecordedNotification $notification) use ($violation) {
                return $notification->violation->id === $violation->id;
            }
        );
    }

    /**
     * Test FR-701 / FR-601: Contract expiring alert dispatches notification to employee and admin.
     */
    public function test_contract_expiring_alert_dispatches_notification_to_employee_and_admin(): void
    {
        Notification::fake();

        $service = app(InAppNotificationService::class);
        $service->notifyContractExpiring($this->employee, 30);

        Notification::assertSentTo(
            $this->karyawanUser,
            ContractExpiringAlertNotification::class,
            function (ContractExpiringAlertNotification $notification) {
                return $notification->employee->id === $this->employee->id && $notification->daysRemaining === 30;
            }
        );

        Notification::assertSentTo(
            $this->adminUser,
            ContractExpiringAlertNotification::class
        );
    }

    /**
     * Test FR-701 / FR-210: Attendance correction submission and decision dispatches notifications.
     */
    public function test_attendance_correction_notifications(): void
    {
        Notification::fake();

        Carbon::setTestNow(Carbon::parse('2026-11-24 10:00:00', 'Asia/Jakarta'));
        $file = UploadedFile::fake()->create('surat_tugas.pdf', 100, 'application/pdf');

        $correctionService = app(AttendanceCorrectionService::class);
        $correction = $correctionService->createCorrection(
            $this->employee,
            [
                'attendance_date' => '2026-11-23',
                'correction_type' => AttendanceCorrection::TYPE_BOTH,
                'suggested_check_in_at' => '07:25',
                'suggested_check_out_at' => '17:05',
                'work_summary' => 'Menjalankan tugas dinas operasional',
            ],
            $file
        );

        Notification::assertSentTo(
            $this->supervisorUser,
            AttendanceCorrectionSubmittedNotification::class,
            function (AttendanceCorrectionSubmittedNotification $notification) use ($correction) {
                return $notification->correction->id === $correction->id;
            }
        );

        // Atasan decision
        $correctionService->reviewBySupervisor($correction, $this->supervisorUser, true, 'Terverifikasi');
        $correctionService->decideByAtasan($correction, $this->atasanUser, true, 'Disetujui');

        Notification::assertSentTo(
            $this->karyawanUser,
            AttendanceCorrectionFinalDecisionNotification::class,
            function (AttendanceCorrectionFinalDecisionNotification $notification) use ($correction) {
                return $notification->correction->id === $correction->id && $notification->correction->isApproved();
            }
        );
    }

    /**
     * Test FR-701 / FR-303: Late exception submission and decision dispatches notifications.
     */
    public function test_late_exception_notifications(): void
    {
        Notification::fake();

        Carbon::setTestNow(Carbon::parse('2026-11-23 10:00:00', 'Asia/Jakarta'));

        $attendance = Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->obPosition->id,
            'office_location_id' => $this->office->id,
            'date' => '2026-11-23',
            'check_in_at' => Carbon::parse('2026-11-23 08:15:00', 'Asia/Jakarta'),
            'status' => Attendance::STATUS_LATE_TL,
            'shift_type' => Attendance::SHIFT_NORMAL,
            'late_minutes' => 45,
        ]);

        $file = UploadedFile::fake()->create('laka.pdf', 100, 'application/pdf');

        $lateService = app(LateExceptionService::class);
        $lateException = $lateService->createException(
            $this->employee,
            [
                'incident_date' => '2026-11-23',
                'category' => LateException::CATEGORY_ACCIDENT,
                'incident_time' => '06:50',
                'description' => 'Tertahan kecelakaan lalu lintas parah',
            ],
            $file
        );

        Notification::assertSentTo(
            $this->supervisorUser,
            LateExceptionSubmittedNotification::class,
            function (LateExceptionSubmittedNotification $notification) use ($lateException) {
                return $notification->lateException->id === $lateException->id;
            }
        );

        // Review & Decision
        $lateService->reviewBySupervisor($lateException, $this->supervisorUser, true, 'Divalidasi');
        $lateService->decideByAtasan($lateException, $this->atasanUser, true, 'Disetujui pembebasan');

        Notification::assertSentTo(
            $this->karyawanUser,
            LateExceptionFinalDecisionNotification::class,
            function (LateExceptionFinalDecisionNotification $notification) use ($lateException) {
                return $notification->lateException->id === $lateException->id && $notification->lateException->isApproved();
            }
        );
    }

    /**
     * Test FR-701: Mobile notification drawer displays unread count, list, and filters.
     */
    public function test_mobile_notification_drawer_renders_unread_count_and_list(): void
    {
        $start = Carbon::now()->next(Carbon::MONDAY);
        $end = $start->copy()->addDay();

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'total_days' => 2,
            'reason' => 'Keperluan keluarga',
            'supervisor_id' => $this->supervisorUser->id,
            'supervisor_status' => LeaveRequest::SUPERVISOR_STATUS_VERIFIED,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $this->karyawanUser->notify(new LeaveRequestFinalDecisionNotification($leaveRequest));

        $response = $this->actingAs($this->karyawanUser)->get('/mobile/notifications');

        $response->assertStatus(200);
        $response->assertSee('Pusat Notifikasi Terpadu');
        $response->assertSee('Putusan Izin/Cuti: DISETUJUI');
        $response->assertSee('1 Baru');
    }

    /**
     * Test FR-701: Mobile header displays bell icon with unread badge.
     */
    public function test_mobile_header_displays_bell_icon_with_unread_badge(): void
    {
        $start = Carbon::now()->next(Carbon::MONDAY);
        $end = $start->copy()->addDay();

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'total_days' => 2,
            'reason' => 'Keperluan keluarga',
            'supervisor_id' => $this->supervisorUser->id,
            'supervisor_status' => LeaveRequest::SUPERVISOR_STATUS_VERIFIED,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $this->karyawanUser->notify(new LeaveRequestFinalDecisionNotification($leaveRequest));

        $response = $this->actingAs($this->karyawanUser)->get('/mobile/home');

        $response->assertStatus(200);
        $response->assertSee(route('mobile.notifications.index'));
        $response->assertSee('animate-pulse');
    }

    /**
     * Test FR-701: Employee can mark a single notification as read.
     */
    public function test_employee_can_mark_single_notification_as_read(): void
    {
        $start = Carbon::now()->next(Carbon::MONDAY);
        $end = $start->copy()->addDay();

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'total_days' => 2,
            'reason' => 'Keperluan keluarga',
            'supervisor_id' => $this->supervisorUser->id,
            'supervisor_status' => LeaveRequest::SUPERVISOR_STATUS_VERIFIED,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $this->karyawanUser->notify(new LeaveRequestFinalDecisionNotification($leaveRequest));
        $notification = $this->karyawanUser->unreadNotifications()->firstOrFail();

        $this->assertNull($notification->read_at);

        $response = $this->actingAs($this->karyawanUser)
            ->post("/mobile/notifications/{$notification->id}/mark-read");

        $response->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertEquals(0, $this->karyawanUser->unreadNotifications()->count());
    }

    /**
     * Test FR-701: Employee can mark all notifications as read.
     */
    public function test_employee_can_mark_all_notifications_as_read(): void
    {
        $start = Carbon::now()->next(Carbon::MONDAY);
        $end = $start->copy()->addDay();

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'total_days' => 2,
            'reason' => 'Keperluan keluarga',
            'supervisor_id' => $this->supervisorUser->id,
            'supervisor_status' => LeaveRequest::SUPERVISOR_STATUS_VERIFIED,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $this->karyawanUser->notify(new LeaveRequestFinalDecisionNotification($leaveRequest));
        $this->karyawanUser->notify(new ContractExpiringAlertNotification($this->employee, 30));

        $this->assertEquals(2, $this->karyawanUser->unreadNotifications()->count());

        $response = $this->actingAs($this->karyawanUser)
            ->post('/mobile/notifications/mark-all-read');

        $response->assertRedirect();
        $this->assertEquals(0, $this->karyawanUser->unreadNotifications()->count());
    }
}
