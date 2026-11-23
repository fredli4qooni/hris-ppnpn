<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\BiometricVerificationService;
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
     * Test scanner displays warning message if employee face is not yet enrolled.
     */
    public function test_scanner_shows_warning_when_face_is_not_enrolled(): void
    {
        // Ensure no face template
        $this->employee->faceTemplate()?->delete();

        $response = $this->actingAs($this->karyawanUser)
            ->get('/mobile/attendance');

        $response->assertSuccessful();
        $response->assertSee('Belum Terdaftar');
        $response->assertSee('Pendaftaran Biometrik Diperlukan');
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
        $response->assertSee('Buka Kamera Presensi Sekarang');
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
}
