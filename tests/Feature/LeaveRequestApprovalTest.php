<?php

namespace Tests\Feature;

use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveRequestService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeaveRequestApprovalTest extends TestCase
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
        Storage::fake('public');

        $this->adminUser = User::where('username', 'admin')->first();
        $this->supervisorUser = User::where('username', 'supervisor')->first();
        $this->atasanUser = User::where('username', 'atasan')->first();
        $this->karyawanUser = User::where('username', 'ppnpn01')->first();
        $this->karyawanUser->update(['must_change_password' => false]);
        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->first();

        // Hubungkan subordinat ke supervisor dan atasan
        $this->employee->update([
            'supervisor_id' => $this->supervisorUser->id,
            'atasan_id' => $this->atasanUser->id,
            'leave_quota_balance' => 12,
        ]);

        $this->service = app(LeaveRequestService::class);
    }

    /**
     * Helper untuk mendapatkan tanggal Senin berikutnya agar hari kerja terjamin.
     */
    protected function getNextMonday(): Carbon
    {
        return Carbon::now()->next(Carbon::MONDAY);
    }

    public function test_employee_can_view_leave_requests_index_and_balance(): void
    {
        $response = $this->actingAs($this->karyawanUser)
            ->get(route('mobile.leaves.index'));

        $response->assertOk()
            ->assertSee('Permohonan Izin & Cuti')
            ->assertSee('12')
            ->assertSee('Hari');
    }

    public function test_employee_can_submit_annual_leave_within_quota_and_3_days_limit(): void
    {
        $start = $this->getNextMonday();
        $end = $start->copy()->addDay(); // 2 hari kerja (Senin - Selasa)

        $response = $this->actingAs($this->karyawanUser)
            ->post(route('mobile.leaves.store'), [
                'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'reason' => 'Keperluan keluarga mendesak di luar kota.',
            ]);

        $response->assertRedirect(route('mobile.leaves.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $this->employee->id,
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'total_days' => 2,
            'supervisor_id' => $this->supervisorUser->id,
            'supervisor_status' => LeaveRequest::SUPERVISOR_STATUS_PENDING,
            'status' => LeaveRequest::STATUS_PENDING_SUPERVISOR,
        ]);
    }

    public function test_employee_cannot_submit_leave_exceeding_3_consecutive_days(): void
    {
        $start = $this->getNextMonday();
        $end = $start->copy()->addDays(3); // 4 hari kerja (Senin - Kamis)

        $this->expectException(ValidationException::class);

        $this->service->submit($this->employee, [
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'reason' => 'Pengajuan cuti 4 hari kerja sekaligus.',
        ]);
    }

    public function test_employee_cannot_submit_annual_leave_exceeding_quota_balance(): void
    {
        $this->employee->update(['leave_quota_balance' => 1]);

        $start = $this->getNextMonday();
        $end = $start->copy()->addDay(); // 2 hari kerja > saldo 1

        $this->expectException(ValidationException::class);

        $this->service->submit($this->employee, [
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'reason' => 'Pengajuan melebihi sisa kuota cuti tahunan.',
        ]);
    }

    public function test_employee_cannot_submit_sick_leave_without_doctor_attachment(): void
    {
        $start = $this->getNextMonday();
        $end = $start->copy()->addDay();

        $this->expectException(ValidationException::class);

        $this->service->submit($this->employee, [
            'leave_type' => LeaveRequest::TYPE_SICK_LEAVE,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'reason' => 'Demam tinggi.',
            'attachment_path' => null,
        ]);
    }

    public function test_employee_can_submit_sick_leave_with_doctor_attachment(): void
    {
        $start = $this->getNextMonday();
        $end = $start->copy()->addDay();
        $file = UploadedFile::fake()->create('surat_dokter.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->karyawanUser)
            ->post(route('mobile.leaves.store'), [
                'leave_type' => LeaveRequest::TYPE_SICK_LEAVE,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'reason' => 'Sakit demam berdarah dan rawat jalan.',
                'attachment' => $file,
            ]);

        $response->assertRedirect(route('mobile.leaves.index'))
            ->assertSessionHas('success');

        $request = LeaveRequest::where('employee_id', $this->employee->id)->latest('id')->first();
        $this->assertNotNull($request->attachment_path);

        /** @var FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');
        $publicDisk->assertExists($request->attachment_path);
    }

    public function test_supervisor_can_verify_pending_request_advancing_to_atasan(): void
    {
        $start = $this->getNextMonday();
        $leaveRequest = $this->service->submit($this->employee, [
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => $start->toDateString(),
            'end_date' => $start->toDateString(),
            'reason' => 'Urusan keluarga.',
        ]);

        $this->assertEquals(LeaveRequest::STATUS_PENDING_SUPERVISOR, $leaveRequest->status);

        $updated = $this->service->verifyBySupervisor(
            $leaveRequest,
            $this->supervisorUser,
            true,
            'Regu kerja aman, verifikasi operasional disetujui.'
        );

        $this->assertEquals(LeaveRequest::SUPERVISOR_STATUS_VERIFIED, $updated->supervisor_status);
        $this->assertEquals(LeaveRequest::STATUS_PENDING_ATASAN, $updated->status);
        $this->assertNotNull($updated->supervisor_action_at);
        $this->assertEquals('Regu kerja aman, verifikasi operasional disetujui.', $updated->supervisor_notes);
    }

    public function test_supervisor_can_reject_pending_request(): void
    {
        $start = $this->getNextMonday();
        $leaveRequest = $this->service->submit($this->employee, [
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => $start->toDateString(),
            'end_date' => $start->toDateString(),
            'reason' => 'Urusan pribadi.',
        ]);

        $updated = $this->service->verifyBySupervisor(
            $leaveRequest,
            $this->supervisorUser,
            false,
            'Tenaga operasional sangat terbatas hari tersebut.'
        );

        $this->assertEquals(LeaveRequest::SUPERVISOR_STATUS_REJECTED, $updated->supervisor_status);
        $this->assertEquals(LeaveRequest::STATUS_REJECTED, $updated->status);
        $this->assertNotNull($updated->rejected_at);
    }

    public function test_unauthorized_user_cannot_verify_supervisor_step(): void
    {
        $start = $this->getNextMonday();
        $leaveRequest = $this->service->submit($this->employee, [
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => $start->toDateString(),
            'end_date' => $start->toDateString(),
            'reason' => 'Cuti tahunan.',
        ]);

        $otherUser = User::create([
            'username' => 'other_supervisor',
            'name' => 'Supervisor Lain',
            'email' => 'other_sup@djpb.kemenkeu.go.id',
            'password' => bcrypt('password'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $otherUser->syncRoles(['supervisor']);

        $this->expectException(AuthorizationException::class);

        $this->service->verifyBySupervisor($leaveRequest, $otherUser, true, 'Mencoba verifikasi bukan subordinatnya.');
    }

    public function test_atasan_can_approve_request_which_syncs_attendance_and_decrements_quota(): void
    {
        $start = $this->getNextMonday();
        $end = $start->copy()->addDay(); // 2 hari kerja

        $leaveRequest = $this->service->submit($this->employee, [
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'reason' => 'Cuti tahunan disetujui bertahap.',
        ]);

        // Verifikasi supervisor
        $this->service->verifyBySupervisor($leaveRequest, $this->supervisorUser, true, 'Verifikasi OK.');

        // Saldo awal 12
        $this->assertEquals(12, $this->employee->fresh()->leave_quota_balance);

        // Putusan Atasan: APPROVED
        $approved = $this->service->approveByAtasan(
            $leaveRequest->fresh(),
            $this->atasanUser,
            true,
            'Disetujui sah kedinasan.'
        );

        $this->assertEquals(LeaveRequest::STATUS_APPROVED, $approved->status);
        $this->assertNotNull($approved->approved_at);

        // Saldo berkurang 2 hari -> sisa 10
        $this->assertEquals(10, $this->employee->fresh()->leave_quota_balance);

        // Cek sinkronisasi attendances: tidak ada Alpha, tercatat LEAVE dengan potongan 0%
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'status' => Attendance::STATUS_LEAVE,
            'tl_category' => Attendance::TL_NONE,
        ]);

        $attendanceCount = Attendance::where('employee_id', $this->employee->id)
            ->where('status', Attendance::STATUS_LEAVE)
            ->count();
        $this->assertEquals(2, $attendanceCount);
    }

    public function test_atasan_can_reject_request_without_decrementing_quota(): void
    {
        $start = $this->getNextMonday();
        $leaveRequest = $this->service->submit($this->employee, [
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => $start->toDateString(),
            'end_date' => $start->toDateString(),
            'reason' => 'Cuti tahunan.',
        ]);

        $this->service->verifyBySupervisor($leaveRequest, $this->supervisorUser, true, 'Verifikasi OK.');

        $rejected = $this->service->approveByAtasan(
            $leaveRequest->fresh(),
            $this->atasanUser,
            false,
            'Terdapat agenda kedinasan mendesak pada hari tersebut.'
        );

        $this->assertEquals(LeaveRequest::STATUS_REJECTED, $rejected->status);
        $this->assertNotNull($rejected->rejected_at);

        // Kuota tidak berkurang
        $this->assertEquals(12, $this->employee->fresh()->leave_quota_balance);

        // Tidak ada rekaman presensi LEAVE yang dibuat
        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $this->employee->id,
            'date' => $start->toDateString(),
            'status' => Attendance::STATUS_LEAVE,
        ]);
    }

    public function test_employee_can_cancel_their_own_pending_request(): void
    {
        $start = $this->getNextMonday();
        $leaveRequest = $this->service->submit($this->employee, [
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => $start->toDateString(),
            'end_date' => $start->toDateString(),
            'reason' => 'Batal ada keperluan.',
        ]);

        $response = $this->actingAs($this->karyawanUser)
            ->post(route('mobile.leaves.cancel', $leaveRequest));

        $response->assertRedirect(route('mobile.leaves.index'))
            ->assertSessionHas('success');

        $this->assertEquals(LeaveRequest::STATUS_CANCELLED, $leaveRequest->fresh()->status);
    }

    public function test_admin_can_access_leave_resource(): void
    {
        $this->actingAs($this->adminUser)
            ->get(LeaveRequestResource::getUrl('index'))
            ->assertOk();
    }

    public function test_supervisor_can_access_leave_resource(): void
    {
        $this->actingAs($this->supervisorUser)
            ->get(LeaveRequestResource::getUrl('index'))
            ->assertOk();
    }

    public function test_atasan_can_access_leave_resource(): void
    {
        $this->actingAs($this->atasanUser)
            ->get(LeaveRequestResource::getUrl('index'))
            ->assertOk();
    }

    public function test_karyawan_cannot_access_leave_resource(): void
    {
        $this->actingAs($this->karyawanUser)
            ->get(LeaveRequestResource::getUrl('index'))
            ->assertForbidden();
    }
}
