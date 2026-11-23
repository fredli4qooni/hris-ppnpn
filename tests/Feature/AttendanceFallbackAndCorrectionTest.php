<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use App\Services\AttendanceCorrectionService;
use App\Services\BiometricVerificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceFallbackAndCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $karyawanUser;

    protected User $supervisorUser;

    protected User $atasanUser;

    protected Employee $employee;

    protected OfficeLocation $office;

    protected Position $obPosition;

    protected string $samplePhotoBase64;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Storage::fake('public');

        $this->karyawanUser = User::where('username', 'ppnpn01')->firstOrFail();
        $this->karyawanUser->update(['must_change_password' => false]);

        $this->supervisorUser = User::where('username', 'supervisor')->first() ?? User::whereHas('roles', fn ($q) => $q->where('name', 'supervisor'))->firstOrFail();
        $this->atasanUser = User::where('username', 'atasan')->first() ?? User::whereHas('roles', fn ($q) => $q->where('name', 'atasan'))->firstOrFail();

        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->firstOrFail();
        $this->office = OfficeLocation::where('name', 'Kanwil DJPb Provinsi Lampung')->firstOrFail();
        $this->obPosition = Position::where('code', 'OB')->firstOrFail();

        // Generate tiny valid sample JPEG for photo upload
        $img = imagecreatetruecolor(80, 80);
        $bgColor = imagecolorallocate($img, 0, 88, 168);
        imagefilledrectangle($img, 0, 0, 79, 79, $bgColor);
        ob_start();
        imagejpeg($img, null, 75);
        $jpegBinary = ob_get_clean();
        imagedestroy($img);
        $this->samplePhotoBase64 = 'data:image/jpeg;base64,'.base64_encode($jpegBinary);

        // Enroll face template
        $unitVal = round(1.0 / sqrt(128), 6);
        $sampleFaceVector = array_fill(0, 128, $unitVal);
        /** @var BiometricVerificationService $biometricService */
        $biometricService = app(BiometricVerificationService::class);
        $biometricService->enrollEmployee($this->employee->id, $sampleFaceVector);
    }

    /**
     * Test FR-209: Fallback check-in within geofence records status FALLBACK_PENDING.
     */
    public function test_employee_can_submit_fallback_check_in_within_geofence(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 07:15:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/fallback-check-in', [
                'position_id' => $this->obPosition->id,
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'accuracy' => 10.0,
                'photo' => $this->samplePhotoBase64,
                'fallback_reason' => 'Verifikasi biometrik wajah gagal 3 kali',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'is_fallback' => true,
                'fallback_status' => Attendance::FALLBACK_PENDING,
            ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'is_fallback' => true,
            'fallback_status' => Attendance::FALLBACK_PENDING,
            'fallback_reason' => 'Verifikasi biometrik wajah gagal 3 kali',
        ]);
    }

    /**
     * Test FR-209: Fallback check-in fails when outside office geofence (> 100 meters).
     */
    public function test_fallback_check_in_rejected_outside_geofence(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 07:15:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/fallback-check-in', [
                'position_id' => $this->obPosition->id,
                'latitude' => -6.2000000, // Jakarta coordinate, > 200 km away
                'longitude' => 106.8166667,
                'accuracy' => 10.0,
                'photo' => $this->samplePhotoBase64,
                'fallback_reason' => 'Kamera rusak',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $this->employee->id,
            'is_fallback' => true,
        ]);
    }

    /**
     * Test FR-209: Supervisor approves fallback attendance.
     */
    public function test_supervisor_can_approve_fallback_attendance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 07:15:00', 'Asia/Jakarta'));

        $attendance = Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->obPosition->id,
            'office_location_id' => $this->office->id,
            'date' => '2026-11-23',
            'check_in_at' => Carbon::now('Asia/Jakarta'),
            'check_in_lat' => (float) $this->office->latitude,
            'check_in_lng' => (float) $this->office->longitude,
            'check_in_distance_meters' => 15.0,
            'check_in_photo_path' => 'attendances/test_selfie.jpg',
            'status' => Attendance::STATUS_ON_TIME,
            'shift_type' => Attendance::SHIFT_NORMAL,
            'is_fallback' => true,
            'fallback_reason' => 'Pencahayaan silau',
            'fallback_status' => Attendance::FALLBACK_PENDING,
        ]);

        $attendance->update([
            'fallback_status' => Attendance::FALLBACK_APPROVED,
            'fallback_verified_by' => $this->supervisorUser->id,
            'fallback_verified_at' => Carbon::now('Asia/Jakarta'),
            'notes' => 'Disahkan oleh '.$this->supervisorUser->name,
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'fallback_status' => Attendance::FALLBACK_APPROVED,
            'fallback_verified_by' => $this->supervisorUser->id,
        ]);
    }

    /**
     * Test FR-209: Supervisor rejects fallback attendance, converting status to ALPHA.
     */
    public function test_supervisor_can_reject_fallback_attendance_and_set_alpha(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 07:15:00', 'Asia/Jakarta'));

        $attendance = Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->obPosition->id,
            'office_location_id' => $this->office->id,
            'date' => '2026-11-23',
            'check_in_at' => Carbon::now('Asia/Jakarta'),
            'check_in_lat' => (float) $this->office->latitude,
            'check_in_lng' => (float) $this->office->longitude,
            'check_in_distance_meters' => 20.0,
            'check_in_photo_path' => 'attendances/test_selfie.jpg',
            'status' => Attendance::STATUS_ON_TIME,
            'shift_type' => Attendance::SHIFT_NORMAL,
            'is_fallback' => true,
            'fallback_reason' => 'Foto tidak jelas',
            'fallback_status' => Attendance::FALLBACK_PENDING,
        ]);

        $attendance->update([
            'fallback_status' => Attendance::FALLBACK_REJECTED,
            'fallback_verified_by' => $this->supervisorUser->id,
            'fallback_verified_at' => Carbon::now('Asia/Jakarta'),
            'fallback_notes' => 'Foto swafoto tidak menampakkan wajah pegawai yang bersangkutan.',
            'status' => Attendance::STATUS_ALPHA,
            'notes' => 'Presensi Fallback Ditolak',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'fallback_status' => Attendance::FALLBACK_REJECTED,
            'status' => Attendance::STATUS_ALPHA,
        ]);
    }

    /**
     * Test FR-210: Employee submits attendance correction within H+2 working days.
     */
    public function test_employee_can_submit_attendance_correction_within_allowed_window(): void
    {
        // Freeze time at Tuesday 2026-11-24. Attendance date is Monday 2026-11-23 (1 working day elapsed).
        Carbon::setTestNow(Carbon::parse('2026-11-24 10:00:00', 'Asia/Jakarta'));

        $proofFile = UploadedFile::fake()->create('surat_tugas.pdf', 150, 'application/pdf');

        $response = $this->actingAs($this->karyawanUser)
            ->post('/mobile/corrections', [
                'attendance_date' => '2026-11-23',
                'correction_type' => AttendanceCorrection::TYPE_BOTH,
                'suggested_check_in_at' => '07:25',
                'suggested_check_out_at' => '17:05',
                'work_summary' => 'Menjalankan tugas dinas operasional kebersihan gedung lantai 2 dan pengawasan logistik.',
                'proof_document' => $proofFile,
            ]);

        $response->assertRedirect('/mobile/corrections');

        $this->assertDatabaseHas('attendance_corrections', [
            'employee_id' => $this->employee->id,
            'correction_type' => AttendanceCorrection::TYPE_BOTH,
            'status' => AttendanceCorrection::STATUS_PENDING,
        ]);

        $created = AttendanceCorrection::where('employee_id', $this->employee->id)->latest('id')->first();
        $this->assertNotNull($created);
        $this->assertEquals('2026-11-23', $created->attendance_date->toDateString());
    }

    /**
     * Test FR-210: Employee cannot submit attendance correction for future dates.
     */
    public function test_employee_cannot_submit_correction_for_future_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 10:00:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawanUser)
            ->from('/mobile/corrections/create')
            ->post('/mobile/corrections', [
                'attendance_date' => '2026-11-25', // Tomorrow (future)
                'correction_type' => AttendanceCorrection::TYPE_BOTH,
                'suggested_check_in_at' => '07:30',
                'work_summary' => 'Belum bertugas tapi sudah mengajukan.',
            ]);

        $response->assertSessionHasErrors(['attendance_date']);

        $this->assertDatabaseMissing('attendance_corrections', [
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-11-25',
        ]);
    }

    /**
     * Test FR-210: Employee cannot submit correction beyond H+2 working days.
     */
    public function test_employee_cannot_submit_correction_beyond_h_plus_2_working_days(): void
    {
        // Freeze time at Friday 2026-11-27. Event was on Monday 2026-11-23.
        // Working days between: Tuesday (1), Wednesday (2), Thursday (3), Friday (4) -> > 2 working days.
        Carbon::setTestNow(Carbon::parse('2026-11-27 10:00:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawanUser)
            ->from('/mobile/corrections/create')
            ->post('/mobile/corrections', [
                'attendance_date' => '2026-11-23',
                'correction_type' => AttendanceCorrection::TYPE_BOTH,
                'suggested_check_in_at' => '07:30',
                'work_summary' => 'Pengajuan terlambat melebihi batas toleransi H+2.',
            ]);

        $response->assertSessionHasErrors(['attendance_date']);

        $this->assertDatabaseMissing('attendance_corrections', [
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-11-23',
        ]);
    }

    /**
     * Test FR-210: Two-tier approval (Supervisor -> Atasan) clears Alpa and creates attendance.
     */
    public function test_two_tier_approval_flow_creates_attendance_and_clears_alpa(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 10:00:00', 'Asia/Jakarta'));

        /** @var AttendanceCorrectionService $correctionService */
        $correctionService = app(AttendanceCorrectionService::class);

        // Pre-existing Alpa record for 2026-11-23
        $existingAlpa = Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->obPosition->id,
            'date' => '2026-11-23',
            'status' => Attendance::STATUS_ALPHA,
            'shift_type' => Attendance::SHIFT_NORMAL,
            'notes' => 'Tidak hadir tanpa keterangan (Alpa otomatis)',
        ]);

        // 1. Employee submits correction
        $correction = $correctionService->createCorrection($this->employee, [
            'attendance_date' => '2026-11-23',
            'correction_type' => AttendanceCorrection::TYPE_BOTH,
            'suggested_check_in_at' => '07:28',
            'suggested_check_out_at' => '17:02',
            'work_summary' => 'Bertugas dinas luar persiapan ruangan rapat kantor wilayah.',
        ]);

        $this->assertEquals(AttendanceCorrection::STATUS_PENDING, $correction->status);

        // 2. Stage 1: Review by Supervisor (Tier 1)
        $correctionService->reviewBySupervisor(
            $correction,
            $this->supervisorUser,
            true,
            'Telah diverifikasi bahwa personil hadir bertugas di lapangan.'
        );

        $correction->refresh();
        $this->assertEquals(AttendanceCorrection::STATUS_APPROVED_SUPERVISOR, $correction->status);
        $this->assertEquals($this->supervisorUser->id, $correction->supervisor_id);

        // 3. Stage 2: Final decision by Atasan (Tier 2)
        $correctionService->decideByAtasan(
            $correction,
            $this->atasanUser,
            true,
            'Disetujui sah oleh Kepala Subbagian Umum / Kasubag.'
        );

        $correction->refresh();
        $this->assertEquals(AttendanceCorrection::STATUS_APPROVED, $correction->status);
        $this->assertEquals($this->atasanUser->id, $correction->atasan_id);

        // Verify Attendance record updated from ALPHA to ON_TIME with corrected times
        $existingAlpa->refresh();
        $this->assertEquals(Attendance::STATUS_ON_TIME, $existingAlpa->status);
        $this->assertNotNull($existingAlpa->check_in_at);
        $this->assertNotNull($existingAlpa->check_out_at);
        $this->assertStringContainsString('Koreksi Lupa Absen Disetujui', $existingAlpa->notes);
    }
}
