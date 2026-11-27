<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\BiometricVerificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileAttendanceScannerTest extends TestCase
{
    use RefreshDatabase;

    protected User $karyawanUser;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->karyawanUser = User::where('username', 'ppnpn01')->first();
        $this->karyawanUser->update(['must_change_password' => false]);

        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->first();
    }

    /**
     * Test unauthenticated guest cannot access mobile attendance scanner (FR-201 / US-301).
     */
    public function test_guest_is_redirected_to_login_when_accessing_scanner(): void
    {
        $response = $this->get('/mobile/attendance');

        $response->assertRedirect('/login');
    }

    /**
     * Test user with must_change_password is locked to first-change page before accessing attendance scanner.
     */
    public function test_user_with_must_change_password_is_redirected(): void
    {
        $this->karyawanUser->update(['must_change_password' => true]);

        $response = $this->actingAs($this->karyawanUser)
            ->get('/mobile/attendance');

        $response->assertRedirect('/password/first-change');
    }

    /**
     * Test authenticated employee can view the attendance scanner page with position selection (FR-201 / US-301).
     */
    public function test_authenticated_karyawan_can_access_scanner_and_see_positions(): void
    {
        // Enroll face so employee is ready
        $service = new BiometricVerificationService;
        $vector = $service->generateMockDescriptor(0.5);
        $service->enrollEmployee($this->employee->id, $vector);

        $response = $this->actingAs($this->karyawanUser)
            ->get('/mobile/attendance');

        $response->assertSuccessful();

        // Check user info and title
        $response->assertSee('Ahmad Subari');
        $response->assertSee('PPNPN-2025-001');
        $response->assertSee('Pemindai Biometrik Wajah');

        // Check FR-201 active positions are loaded in dropdown
        $activePositions = Position::where('is_active', true)->get();
        foreach ($activePositions as $pos) {
            $response->assertSee($pos->name);
        }

        // Check Face oval outline element and core scanner elements
        $response->assertSee('ellipse');
        $response->assertSee('Pilih Posisi Tugas Hari Ini');
        $response->assertSee('Pemindai Biometrik Wajah');
    }

    /**
     * Test scanner remains accessible even if employee face template is not enrolled (Milestone 1 - Poin 7).
     */
    public function test_scanner_allows_access_without_face_template_enrollment(): void
    {
        // Ensure no face template
        $this->employee->faceTemplate()?->delete();

        $response = $this->actingAs($this->karyawanUser)
            ->get('/mobile/attendance');

        $response->assertSuccessful();
        $response->assertDontSee('Pendaftaran Biometrik Diperlukan');
        $response->assertSee('Pemindai Biometrik Wajah');
        $response->assertSee('ellipse');
    }

    /**
     * Test PWA manifest file is accessible and contains required DJPb PWA properties.
     */
    public function test_pwa_manifest_is_accessible_and_valid(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $content = file_get_contents($manifestPath);
        $this->assertNotFalse($content);

        $json = json_decode($content, true);
        $this->assertIsArray($json);
        $this->assertEquals('HRIS PPNPN Kanwil DJPb Provinsi Lampung', $json['name']);
        $this->assertEquals('standalone', $json['display']);
        $this->assertEquals('#0058A8', $json['theme_color']);
        $this->assertEquals('/mobile/home', $json['start_url']);
    }

    /**
     * Test mobile layout includes PWA meta tags and updated navigation link to attendance.
     */
    public function test_mobile_layout_has_pwa_tags_and_attendance_navigation(): void
    {
        $response = $this->actingAs($this->karyawanUser)
            ->get('/mobile/home');

        $response->assertSuccessful();
        $response->assertSee('manifest.json');
        $response->assertSee('apple-mobile-web-app-capable');
        $response->assertSee(route('mobile.attendance'));
        $response->assertSee('Buka Kamera Presensi');
    }

    /**
     * Test unauthenticated guest cannot call verify-liveness API endpoint.
     */
    public function test_guest_cannot_call_verify_liveness(): void
    {
        $response = $this->postJson('/mobile/attendance/verify-liveness', [
            'passed' => true,
            'challenge_type' => 'head_turn',
            'duration_ms' => 2000,
            'yaw_angle' => 16.0,
        ]);

        $response->assertUnauthorized();
    }

    /**
     * Test authenticated employee can successfully verify liveness (FR-202 / US-302).
     */
    public function test_authenticated_karyawan_can_verify_liveness_successfully(): void
    {
        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/verify-liveness', [
                'passed' => true,
                'challenge_type' => 'head_turn',
                'duration_ms' => 2500,
                'yaw_angle' => 16.0,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'is_valid',
                'score',
                'challenge_type',
                'duration_ms',
                'message',
            ],
        ]);
    }

    /**
     * Test failed liveness check returns 422 and records spoofing alert in audit logs (FR-202).
     */
    public function test_failed_liveness_check_records_audit_log_spoofing_alert(): void
    {
        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/verify-liveness', [
                'passed' => false,
                'challenge_type' => 'passive',
                'duration_ms' => 1500,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);

        // Verify audit log has recorded the event
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->karyawanUser->id,
            'action' => 'LIVENESS_CHECK_FAILED',
        ]);
    }

    /**
     * Test liveness verification enforces 8000ms maximum timeout (US-302).
     */
    public function test_liveness_rejects_exceeded_timeout_via_api(): void
    {
        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/verify-liveness', [
                'passed' => true,
                'challenge_type' => 'head_turn',
                'duration_ms' => 8900, // > 8000 ms
                'yaw_angle' => 16.0,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('melebihi batas maksimal 8000 ms', $response->json('message'));
    }

    /**
     * Test scanner hides camera and locks check-out when current time is before scheduled end time (Milestone 2 - Poin 1).
     */
    public function test_scanner_locks_checkout_before_scheduled_end_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-27 11:00:00', 'Asia/Jakarta'));

        $driver = Position::where('code', 'DRIVER')->first();

        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $driver->id,
            'date' => '2026-11-27',
            'check_in_at' => Carbon::parse('2026-11-27 06:55:00', 'Asia/Jakarta'),
            'status' => Attendance::STATUS_ON_TIME,
            'shift_type' => Attendance::SHIFT_NORMAL,
        ]);

        $response = $this->actingAs($this->karyawanUser)
            ->get('/mobile/attendance');

        $response->assertSuccessful();
        $response->assertSee('Belum Waktunya Pulang');
        $response->assertSee('18:00 WIB');
        $response->assertSee('Mode Pulang (Check-out)');
        $response->assertDontSee('scheduledCheckOutTime: "18:00"', false);
        $response->assertDontSee('Pilih Posisi Tugas Hari Ini');
        $response->assertDontSee('Aktifkan Kamera Pulang');
    }

    /**
     * Test scanner locks completely when both check-in and check-out are completed for the day (Milestone 2 - Poin 2).
     */
    public function test_scanner_locks_entirely_when_day_attendance_is_completed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-27 19:00:00', 'Asia/Jakarta'));

        $driver = Position::where('code', 'DRIVER')->first();

        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $driver->id,
            'date' => '2026-11-27',
            'check_in_at' => Carbon::parse('2026-11-27 06:55:00', 'Asia/Jakarta'),
            'check_out_at' => Carbon::parse('2026-11-27 18:05:00', 'Asia/Jakarta'),
            'status' => Attendance::STATUS_ON_TIME,
            'check_out_status' => Attendance::CHECK_OUT_ON_TIME,
            'shift_type' => Attendance::SHIFT_NORMAL,
        ]);

        $response = $this->actingAs($this->karyawanUser)
            ->get('/mobile/attendance');

        $response->assertSuccessful();
        $response->assertSee('Presensi Hari Ini Selesai');
        $response->assertSee('06:55 WIB');
        $response->assertSee('18:05 WIB');
        $response->assertDontSee('Pilih Posisi Tugas Hari Ini');
        $response->assertDontSee('Pemindai Biometrik Wajah');
    }
}
