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

class AttendanceCheckOutTest extends TestCase
{
    use RefreshDatabase;

    protected User $karyawanUser;

    protected Employee $employee;

    protected OfficeLocation $office;

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
     * Test unauthenticated request cannot call check-out endpoint.
     */
    public function test_guest_cannot_check_out(): void
    {
        $response = $this->postJson('/mobile/attendance/check-out', [
            'latitude' => (float) $this->office->latitude,
            'longitude' => (float) $this->office->longitude,
            'liveness_verified' => true,
            'photo' => $this->samplePhotoBase64,
        ]);

        $response->assertUnauthorized();
    }

    /**
     * Test employee without prior check-in cannot check out.
     */
    public function test_employee_without_check_in_cannot_check_out(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 18:05:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-out', [
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Anda belum melakukan presensi masuk hari ini. Silakan lakukan presensi masuk terlebih dahulu.',
        ]);
    }

    /**
     * Test check-out on time after schedule end records ON_TIME check_out_status.
     */
    public function test_check_out_on_time_after_schedule_end(): void
    {
        // 1. Check-in on time at 06:55 (schedule: 07:00 - 18:00)
        Carbon::setTestNow(Carbon::parse('2026-11-18 06:55:00', 'Asia/Jakarta'));
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

        // 2. Check-out at 18:05:00 (after 18:00:00 schedule end)
        Carbon::setTestNow(Carbon::parse('2026-11-18 18:05:00', 'Asia/Jakarta'));
        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-out', [
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
                'check_out_status' => 'ON_TIME',
                'is_early_leave' => false,
                'early_leave_minutes' => 0,
                'is_compensated' => true,
            ],
        ]);

        $attendance = Attendance::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($attendance->check_out_at);
        $this->assertEquals('ON_TIME', $attendance->check_out_status);
        $this->assertTrue($attendance->is_compensated);

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk('public');
        $storage->assertExists($attendance->check_out_photo_path);
    }

    /**
     * Test check-out with tolerance compensation fully satisfied.
     */
    public function test_check_out_with_tolerance_compensation_fully_paid(): void
    {
        // 1. Check-in 10 minutes late at 07:10:00 (Driver schedule 07:00 - 18:00).
        // Tolerance requires +10 minutes at checkout -> target: 18:10:00.
        Carbon::setTestNow(Carbon::parse('2026-11-18 07:10:00', 'Asia/Jakarta'));
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

        // 2. Check-out at 18:12:00 (past 18:10:00 target)
        Carbon::setTestNow(Carbon::parse('2026-11-18 18:12:00', 'Asia/Jakarta'));
        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-out', [
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'check_out_status' => 'LUNAS_PENGGANTIAN',
                'is_compensated' => true,
                'early_leave_minutes' => 0,
            ],
        ]);

        $attendance = Attendance::where('employee_id', $this->employee->id)->first();
        $this->assertEquals('LUNAS_PENGGANTIAN', $attendance->check_out_status);
        $this->assertTrue($attendance->is_compensated);
    }

    /**
     * Test check-out with tolerance compensation not fully paid (leaves before target).
     */
    public function test_check_out_with_tolerance_compensation_shortfall(): void
    {
        // 1. Check-in 10 minutes late at 07:10:00 (target end: 18:10:00)
        Carbon::setTestNow(Carbon::parse('2026-11-18 07:10:00', 'Asia/Jakarta'));
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

        // 2. Check-out at 18:04:00 (after normal 18:00, but before 18:10 target -> shortfall 6 mins)
        Carbon::setTestNow(Carbon::parse('2026-11-18 18:04:00', 'Asia/Jakarta'));
        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-out', [
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'check_out_status' => 'TIDAK_MENGGANTI_JAM',
                'is_compensated' => false,
            ],
        ]);

        $attendance = Attendance::where('employee_id', $this->employee->id)->first();
        $this->assertEquals('TIDAK_MENGGANTI_JAM', $attendance->check_out_status);
        $this->assertFalse($attendance->is_compensated);
    }

    /**
     * Test early leave before schedule end is detected as PSW (Pulang Sebelum Waktunya).
     */
    public function test_check_out_before_schedule_end_detected_as_psw(): void
    {
        // 1. Check-in on time at 06:55
        Carbon::setTestNow(Carbon::parse('2026-11-18 06:55:00', 'Asia/Jakarta'));
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

        // 2. Check-out at 17:15:00 (schedule ends at 18:00 -> 45 minutes early)
        Carbon::setTestNow(Carbon::parse('2026-11-18 17:15:00', 'Asia/Jakarta'));
        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-out', [
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'check_out_status' => 'PSW',
                'is_early_leave' => true,
                'early_leave_minutes' => 45,
            ],
        ]);

        $attendance = Attendance::where('employee_id', $this->employee->id)->first();
        $this->assertEquals('PSW', $attendance->check_out_status);
        $this->assertEquals(45, $attendance->early_leave_minutes);
        $this->assertTrue($attendance->isPsw());
    }

    /**
     * Test Satpam night shift check-out next morning links correctly to yesterday's check-in.
     */
    public function test_satpam_night_shift_check_out_next_morning_links_to_yesterday(): void
    {
        // 1. Satpam night shift check-in on 2026-11-18 at 19:00 WIB (Schedule: 19:00 - 07:00 H+1)
        Carbon::setTestNow(Carbon::parse('2026-11-18 19:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-in', [
                'position_id' => $this->securityNightPosition->id,
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ])
            ->assertOk();

        // Verify night shift record created with work date 2026-11-18
        $this->assertEquals(1, Attendance::count());
        $initialAttendance = Attendance::first();
        $this->assertEquals('2026-11-18', $initialAttendance->date->toDateString());
        $this->assertEquals('SECURITY_NIGHT', $initialAttendance->shift_type);
        $this->assertNull($initialAttendance->check_out_at);

        // 2. Check-out next morning on 2026-11-19 at 07:05:00 WIB
        Carbon::setTestNow(Carbon::parse('2026-11-19 07:05:00', 'Asia/Jakarta'));
        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-out', [
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'work_date' => '2026-11-18',
                'check_out_status' => 'ON_TIME',
                'is_early_leave' => false,
            ],
        ]);

        // Still only 1 record in database (no duplicate row for 2026-11-19)
        $this->assertEquals(1, Attendance::count());
        $updatedAttendance = Attendance::first();
        $this->assertEquals('2026-11-18', $updatedAttendance->date->toDateString());
        $this->assertNotNull($updatedAttendance->check_out_at);
        $this->assertEquals('2026-11-19 07:05:00', $updatedAttendance->check_out_at->format('Y-m-d H:i:s'));
    }

    /**
     * Test duplicate check-out on the same session is rejected with 422.
     */
    public function test_duplicate_check_out_is_rejected(): void
    {
        // 1. Check-in
        Carbon::setTestNow(Carbon::parse('2026-11-18 06:55:00', 'Asia/Jakarta'));
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

        // 2. First check-out
        Carbon::setTestNow(Carbon::parse('2026-11-18 18:05:00', 'Asia/Jakarta'));
        $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-out', [
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ])
            ->assertOk();

        // 3. Second check-out
        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-out', [
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
                'already_checked_out' => true,
            ],
        ]);
    }

    /**
     * Test check-out outside geofence is rejected with 422.
     */
    public function test_check_out_outside_geofence_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 06:55:00', 'Asia/Jakarta'));
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

        Carbon::setTestNow(Carbon::parse('2026-11-18 18:05:00', 'Asia/Jakarta'));
        $outsideLat = (float) $this->office->latitude + (500.0 / 111139.0);

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-out', [
                'latitude' => $outsideLat,
                'longitude' => (float) $this->office->longitude,
                'liveness_verified' => true,
                'face_probe_vector' => $this->sampleFaceVector,
                'photo' => $this->samplePhotoBase64,
            ]);

        $response->assertStatus(422);
        $attendance = Attendance::where('employee_id', $this->employee->id)->first();
        $this->assertNull($attendance->check_out_at);
    }
}
