<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use App\Services\AttendanceCalculationService;
use App\Services\BiometricVerificationService;
use Carbon\Carbon;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceCheckInTest extends TestCase
{
    use RefreshDatabase;

    protected User $karyawanUser;

    protected Employee $employee;

    protected OfficeLocation $office;

    protected Position $obPosition;

    protected Position $driverPosition;

    protected Position $securityNightPosition;

    protected string $samplePhotoBase64;

    /**
     * @var list<float>
     */
    protected array $sampleFaceVector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Storage::fake('public');

        $this->karyawanUser = User::where('username', 'ppnpn01')->first();
        $this->karyawanUser->update(['must_change_password' => false]);

        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->first();
        $this->office = OfficeLocation::where('name', 'Kanwil DJPb Provinsi Lampung')->first();

        $this->obPosition = Position::where('code', 'OB')->first();
        $this->driverPosition = Position::where('code', 'DRIVER')->first();
        $this->securityNightPosition = Position::where('code', 'SECURITY_NIGHT')->first();

        // Generate tiny valid sample JPEG for photo upload
        $img = imagecreatetruecolor(100, 100);
        $bgColor = imagecolorallocate($img, 0, 88, 168);
        imagefilledrectangle($img, 0, 0, 99, 99, $bgColor);
        ob_start();
        imagejpeg($img, null, 75);
        $jpegBinary = ob_get_clean();
        imagedestroy($img);
        $this->samplePhotoBase64 = 'data:image/jpeg;base64,'.base64_encode($jpegBinary);

        // Generate sample 128-float normalized unit face vector and enroll employee
        $unitVal = round(1.0 / sqrt(128), 6);
        $this->sampleFaceVector = array_fill(0, 128, $unitVal);
        /** @var BiometricVerificationService $biometricService */
        $biometricService = app(BiometricVerificationService::class);
        $biometricService->enrollEmployee($this->employee->id, $this->sampleFaceVector);
    }

    /**
     * Test unauthenticated request cannot call check-in endpoint.
     */
    public function test_guest_cannot_check_in(): void
    {
        $response = $this->postJson('/mobile/attendance/check-in', [
            'position_id' => $this->driverPosition->id,
            'latitude' => (float) $this->office->latitude,
            'longitude' => (float) $this->office->longitude,
            'liveness_verified' => true,
            'photo' => $this->samplePhotoBase64,
        ]);

        $response->assertUnauthorized();
    }

    /**
     * Test check-in on time (arrival before/at schedule start) gives ON_TIME with 0% deduction.
     */
    public function test_check_in_on_time_records_attendance_with_zero_deduction(): void
    {
        // Schedule for Driver is 07:00:00 WIB. Freeze time at 06:55:00 WIB on 2026-11-18.
        Carbon::setTestNow(Carbon::parse('2026-11-18 06:55:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-in', [
                'position_id' => $this->driverPosition->id,
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'accuracy' => 15.0,
                'is_mock' => false,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'work_date' => '2026-11-18',
                'status' => 'ON_TIME',
                'status_label' => 'Tepat Waktu',
                'late_minutes' => 0,
                'tl_category' => 'NONE',
                'deduction_percentage' => '0.00',
                'required_compensation_minutes' => 0,
            ],
        ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'position_id' => $this->driverPosition->id,
            'status' => 'ON_TIME',
            'late_minutes' => 0,
            'tl_category' => 'NONE',
            'deduction_percentage' => 0.00,
            'required_compensation_minutes' => 0,
        ]);

        // Verify photo stored on disk and date correctly set
        $attendance = Attendance::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($attendance);
        $this->assertEquals('2026-11-18', $attendance->date->toDateString());
        $this->assertNotNull($attendance->check_in_photo_path);

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk('public');
        $storage->assertExists($attendance->check_in_photo_path);
    }

    /**
     * Test Driver check-in at 07:08:00 classifies directly as TL 1 (0.5% deduction) under role-based TL cutoffs.
     */
    public function test_driver_check_in_at_07_08_classifies_as_tl_1(): void
    {
        // Driver schedule: 07:00. TL 0 <= 07:00:59; 07:01:00 - 07:30:59 is TL 1.
        Carbon::setTestNow(Carbon::parse('2026-11-18 07:08:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-in', [
                'position_id' => $this->driverPosition->id,
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'accuracy' => 12.0,
                'is_mock' => false,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'status' => 'LATE_TL',
                'late_minutes' => 8,
                'tl_category' => 'TL_1',
                'deduction_percentage' => '0.50',
                'required_compensation_minutes' => 0,
            ],
        ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'status' => 'LATE_TL',
            'late_minutes' => 8,
            'required_compensation_minutes' => 0,
            'tl_category' => 'TL_1',
            'deduction_percentage' => 0.50,
        ]);

        $this->assertStringNotContainsString('Potongan', (string) $response->json('data.notes'));
    }

    /**
     * Test check-in late 16 to 30 minutes classifies as TL 1 with 0.5% deduction.
     */
    public function test_check_in_late_16_to_30_minutes_classifies_as_tl_1(): void
    {
        // Driver schedule: 07:00. Arrival at 07:25:00 (late 25 minutes).
        Carbon::setTestNow(Carbon::parse('2026-11-18 07:25:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-in', [
                'position_id' => $this->driverPosition->id,
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'accuracy' => 10.0,
                'is_mock' => false,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'status' => 'LATE_TL',
                'tl_category' => 'TL_1',
                'deduction_percentage' => '0.50',
                'late_minutes' => 25,
                'required_compensation_minutes' => 0,
            ],
        ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'status' => 'LATE_TL',
            'tl_category' => 'TL_1',
            'deduction_percentage' => 0.50,
            'late_minutes' => 25,
        ]);
    }

    /**
     * Test check-in late 31 to 60 minutes classifies as TL 2 with 1.0% deduction.
     */
    public function test_check_in_late_31_to_60_minutes_classifies_as_tl_2(): void
    {
        // Driver schedule: 07:00. Arrival at 07:38:00 (late 38 minutes).
        Carbon::setTestNow(Carbon::parse('2026-11-18 07:38:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-in', [
                'position_id' => $this->driverPosition->id,
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'accuracy' => 10.0,
                'is_mock' => false,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'status' => 'LATE_TL',
                'tl_category' => 'TL_2',
                'deduction_percentage' => '1.00',
                'late_minutes' => 38,
            ],
        ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'status' => 'LATE_TL',
            'tl_category' => 'TL_2',
            'deduction_percentage' => 1.00,
            'late_minutes' => 38,
        ]);
    }

    /**
     * Test check-in late over 60 minutes classifies as TL 3 with 1.5% deduction.
     */
    public function test_check_in_late_over_60_minutes_classifies_as_tl_3(): void
    {
        // Driver schedule: 07:00. Arrival at 08:15:00 (late 75 minutes).
        Carbon::setTestNow(Carbon::parse('2026-11-18 08:15:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-in', [
                'position_id' => $this->driverPosition->id,
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'accuracy' => 10.0,
                'is_mock' => false,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'status' => 'LATE_TL',
                'tl_category' => 'TL_3',
                'deduction_percentage' => '1.50',
                'late_minutes' => 75,
            ],
        ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'status' => 'LATE_TL',
            'tl_category' => 'TL_3',
            'deduction_percentage' => 1.50,
            'late_minutes' => 75,
        ]);
    }

    /**
     * Test Pramubakti (OB) check-in at 06:42:00 falls inside TL 0 (06:30 s.d. < 06:46) with 0% deduction.
     */
    public function test_pramubhakti_check_in_within_tl_0_window_is_on_time(): void
    {
        // OB TL 0 window: 06:30 s.d. < 06:46 -> arrival 06:42:00 has 0% deduction.
        Carbon::setTestNow(Carbon::parse('2026-11-18 06:42:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-in', [
                'position_id' => $this->obPosition->id,
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'status' => 'ON_TIME',
                'tl_category' => 'NONE',
                'late_minutes' => 0,
                'deduction_percentage' => '0.00',
                'required_compensation_minutes' => 0,
            ],
        ]);
    }

    /**
     * Test check-in outside 100m geofence is rejected with 422.
     */
    public function test_check_in_outside_geofence_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 06:50:00', 'Asia/Jakarta'));

        // Point ~500m away
        $outsideLat = (float) $this->office->latitude + (500.0 / 111139.0);

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-in', [
                'position_id' => $this->driverPosition->id,
                'latitude' => $outsideLat,
                'longitude' => (float) $this->office->longitude,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);

        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $this->employee->id,
        ]);
    }

    /**
     * Test check-in with fake GPS mock location is rejected with 422.
     */
    public function test_check_in_with_mock_location_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 06:50:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-in', [
                'position_id' => $this->driverPosition->id,
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'is_mock' => true,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $this->employee->id,
        ]);
    }

    /**
     * Test check-in without liveness verification is rejected.
     */
    public function test_check_in_without_liveness_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 06:50:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-in', [
                'position_id' => $this->driverPosition->id,
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'liveness_verified' => false,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $this->employee->id,
        ]);
    }

    /**
     * Test check-in succeeds without mathematical 128-D face matching as long as geofence, liveness, and selfie photo are valid.
     */
    public function test_check_in_succeeds_without_mathematical_face_matching(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 06:50:00', 'Asia/Jakarta'));

        // Even without face template enrolled or with arbitrary probe vector, liveness + geofence + selfie succeeds
        $this->employee->faceTemplate()?->delete();

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-in', [
                'position_id' => $this->driverPosition->id,
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'liveness_verified' => true,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $attendance = Attendance::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($attendance);
        $this->assertEquals('2026-11-18', $attendance->date->toDateString());
        $this->assertNotNull($attendance->check_in_photo_path);
    }

    /**
     * Test duplicate check-in on the same day is rejected with 422.
     */
    public function test_duplicate_check_in_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 06:50:00', 'Asia/Jakarta'));

        // First check-in
        $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-in', [
                'position_id' => $this->driverPosition->id,
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ])
            ->assertOk();

        // Second check-in on same day
        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-in', [
                'position_id' => $this->driverPosition->id,
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'data' => [
                'already_checked_in' => true,
            ],
        ]);
    }

    /**
     * Test Satpam Night Shift check-in correctly tags shift type and TL 1 at 19:10:00.
     */
    public function test_satpam_night_shift_check_in(): void
    {
        // Satpam night TL 0: <= 19:00:59; 19:01:00 - 19:30:59 is TL 1.
        Carbon::setTestNow(Carbon::parse('2026-11-18 19:10:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-in', [
                'position_id' => $this->securityNightPosition->id,
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'shift_type' => 'SECURITY_NIGHT',
            'status' => 'LATE_TL',
            'tl_category' => 'TL_1',
            'deduction_percentage' => 0.50,
            'late_minutes' => 10,
        ]);
    }

    /**
     * Test role-based TL 0, TL 1, TL 2, and TL 3 cutoffs across all PPNPN position groups.
     */
    public function test_role_based_tl_thresholds_for_all_positions(): void
    {
        $service = app(AttendanceCalculationService::class);
        $receptionist = Position::where('code', 'RECEPTIONIST')->firstOrFail();
        $technician = Position::where('code', 'TECHNICIAN')->firstOrFail();
        $securityDay = Position::where('code', 'SECURITY_DAY')->firstOrFail();

        $cases = [
            // Pramubakti (OB)
            [$this->obPosition, '2026-11-18 06:25:00', Attendance::STATUS_ON_TIME, Attendance::TL_NONE, 0.00],
            [$this->obPosition, '2026-11-18 06:45:59', Attendance::STATUS_ON_TIME, Attendance::TL_NONE, 0.00],
            [$this->obPosition, '2026-11-18 06:46:00', Attendance::STATUS_LATE_TL, Attendance::TL_1, 0.50],
            [$this->obPosition, '2026-11-18 07:00:59', Attendance::STATUS_LATE_TL, Attendance::TL_1, 0.50],
            [$this->obPosition, '2026-11-18 07:01:00', Attendance::STATUS_LATE_TL, Attendance::TL_2, 1.00],
            [$this->obPosition, '2026-11-18 07:29:59', Attendance::STATUS_LATE_TL, Attendance::TL_2, 1.00],
            [$this->obPosition, '2026-11-18 07:30:00', Attendance::STATUS_LATE_TL, Attendance::TL_3, 1.50],

            // Pengemudi, Receptionist, Teknisi, Satpam Shift Pagi
            [$this->driverPosition, '2026-11-18 06:46:00', Attendance::STATUS_ON_TIME, Attendance::TL_NONE, 0.00],
            [$this->driverPosition, '2026-11-18 07:00:59', Attendance::STATUS_ON_TIME, Attendance::TL_NONE, 0.00],
            [$this->driverPosition, '2026-11-18 07:01:00', Attendance::STATUS_LATE_TL, Attendance::TL_1, 0.50],
            [$receptionist, '2026-11-18 07:30:59', Attendance::STATUS_LATE_TL, Attendance::TL_1, 0.50],
            [$technician, '2026-11-18 07:31:00', Attendance::STATUS_LATE_TL, Attendance::TL_2, 1.00],
            [$securityDay, '2026-11-18 07:59:59', Attendance::STATUS_LATE_TL, Attendance::TL_2, 1.00],
            [$securityDay, '2026-11-18 08:00:00', Attendance::STATUS_LATE_TL, Attendance::TL_3, 1.50],

            // Satpam Shift Malam
            [$this->securityNightPosition, '2026-11-18 17:46:00', Attendance::STATUS_ON_TIME, Attendance::TL_NONE, 0.00],
            [$this->securityNightPosition, '2026-11-18 19:00:59', Attendance::STATUS_ON_TIME, Attendance::TL_NONE, 0.00],
            [$this->securityNightPosition, '2026-11-18 19:01:00', Attendance::STATUS_LATE_TL, Attendance::TL_1, 0.50],
            [$this->securityNightPosition, '2026-11-18 19:30:59', Attendance::STATUS_LATE_TL, Attendance::TL_1, 0.50],
            [$this->securityNightPosition, '2026-11-18 19:31:00', Attendance::STATUS_LATE_TL, Attendance::TL_2, 1.00],
            [$this->securityNightPosition, '2026-11-18 19:59:59', Attendance::STATUS_LATE_TL, Attendance::TL_2, 1.00],
            [$this->securityNightPosition, '2026-11-18 20:00:00', Attendance::STATUS_LATE_TL, Attendance::TL_3, 1.50],
            [$this->securityNightPosition, '2026-11-19 00:30:00', Attendance::STATUS_LATE_TL, Attendance::TL_3, 1.50],
        ];

        foreach ($cases as [$position, $timestamp, $expectedStatus, $expectedTl, $expectedDeduction]) {
            $result = $service->calculateCheckIn($position, Carbon::parse($timestamp, 'Asia/Jakarta'));

            $this->assertSame($expectedStatus, $result['status'], "Failed status for {$position->code} at {$timestamp}");
            $this->assertSame($expectedTl, $result['tl_category'], "Failed TL for {$position->code} at {$timestamp}");
            $this->assertSame($expectedDeduction, $result['deduction_percentage'], "Failed deduction for {$position->code} at {$timestamp}");
        }

        // Verify post-midnight Security Night arrival keeps previous calendar work_date
        $overnightLate = $service->calculateCheckIn(
            $this->securityNightPosition,
            Carbon::parse('2026-11-19 00:30:00', 'Asia/Jakarta')
        );
        $this->assertSame('2026-11-18', $overnightLate['work_date']);
        $this->assertSame(330, $overnightLate['late_minutes']);
    }
}
