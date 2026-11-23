<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
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
     * Test check-in within 15-minute grace tolerance records TOLERANCE with required return hours compensation.
     */
    public function test_check_in_within_15_minutes_tolerance_records_tolerance_and_compensation(): void
    {
        // Driver schedule: 07:00. Arrival at 07:08:00 (late 8 minutes).
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
                'status' => 'TOLERANCE',
                'late_minutes' => 8,
                'tl_category' => 'NONE',
                'deduction_percentage' => '0.00',
                'required_compensation_minutes' => 8,
            ],
        ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'status' => 'TOLERANCE',
            'late_minutes' => 8,
            'required_compensation_minutes' => 8,
            'tl_category' => 'NONE',
            'deduction_percentage' => 0.00,
        ]);
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
     * Test Pramubhakti (schedule 06:30) check-in rules with Lampiran IV SPK.
     */
    public function test_pramubhakti_check_in_tolerance_and_tl(): void
    {
        // 1. Arrival 06:42:00 -> 12 mins late, within 15 min tolerance
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
                'status' => 'TOLERANCE',
                'late_minutes' => 12,
                'deduction_percentage' => '0.00',
                'required_compensation_minutes' => 12,
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
     * Test face mismatch is rejected when probe vector is far from registered template.
     */
    public function test_face_mismatch_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 06:50:00', 'Asia/Jakarta'));

        // Vector far away from enrolled vector (0.05)
        $mismatchVector = array_fill(0, 128, 0.95);

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-in', [
                'position_id' => $this->driverPosition->id,
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'liveness_verified' => true,
                'face_probe_vector' => $mismatchVector,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['is_match' => false]);
        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $this->employee->id,
        ]);
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
     * Test Satpam Night Shift check-in correctly tags shift type.
     */
    public function test_satpam_night_shift_check_in(): void
    {
        // Satpam night schedule is 19:00:00. Check in at 19:10:00.
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
            'status' => 'TOLERANCE',
            'late_minutes' => 10,
        ]);
    }
}
