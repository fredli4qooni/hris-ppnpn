<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\FaceTemplate;
use App\Models\Position;
use App\Models\User;
use App\Services\AttendancePhotoService;
use App\Services\BiometricVerificationService;
use Database\Seeders\SampleFiveEmployeesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CybersecurityAndPrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected User $karyawanUser;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->seed(SampleFiveEmployeesSeeder::class);

        $this->karyawanUser = User::where('username', 'ppnpn01')->firstOrFail();
        $this->karyawanUser->update(['must_change_password' => false]);
        $this->employee = $this->karyawanUser->employee()->firstOrFail();

        Cache::flush();
    }

    /**
     * Test production-grade security headers are attached to all web responses (CSIRT Kemenkeu).
     */
    public function test_security_headers_are_present_on_web_responses(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(self), geolocation=(self), microphone=()');
        $this->assertNotNull($response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("default-src 'self'", $response->headers->get('Content-Security-Policy'));
    }

    /**
     * Test rate limiting on authentication attempts blocks after 5 requests per minute (US-703).
     */
    public function test_auth_rate_limiting_blocks_after_5_login_attempts(): void
    {
        // 5 allowed attempts
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/login', [
                'login' => 'unknown_bruteforce_user',
                'password' => 'wrong_password_123',
            ]);
            $this->assertNotEquals(429, $response->status());
        }

        // 6th attempt should be blocked by rate limiter with 429
        $blockedResponse = $this->postJson('/login', [
            'login' => 'unknown_bruteforce_user',
            'password' => 'wrong_password_123',
        ]);

        $this->assertEquals(429, $blockedResponse->status());
    }

    /**
     * Test rate limiting on mobile attendance API blocks after 30 requests per minute (US-703).
     */
    public function test_attendance_api_rate_limiting_blocks_after_30_attempts(): void
    {
        $this->actingAs($this->karyawanUser);

        // 30 allowed attempts
        for ($i = 1; $i <= 30; $i++) {
            $response = $this->postJson(route('mobile.attendance.verify-geofence'), [
                'latitude' => -5.3971400,
                'longitude' => 105.2667900,
                'accuracy' => 15,
                'is_mock' => false,
            ]);
            $this->assertNotEquals(429, $response->status());
        }

        // 31st attempt should be throttled
        $blockedResponse = $this->postJson(route('mobile.attendance.verify-geofence'), [
            'latitude' => -5.3971400,
            'longitude' => 105.2667900,
            'accuracy' => 15,
            'is_mock' => false,
        ]);

        $this->assertEquals(429, $blockedResponse->status());
        $this->assertEquals('RATE_LIMIT_EXCEEDED', $blockedResponse->json('error_code'));
    }

    /**
     * Test repeated biometric failures trigger a temporary 15-minute lockout (US-703).
     */
    public function test_biometric_brute_force_triggers_lockout_after_repeated_failures(): void
    {
        $this->actingAs($this->karyawanUser);

        // Enroll sample face vector
        $dummyVector = array_fill(0, BiometricVerificationService::VECTOR_DIMENSION, 0.1);
        $this->employee->faceTemplate()->updateOrCreate(
            ['employee_id' => $this->employee->id],
            [
                'encrypted_vector' => app(BiometricVerificationService::class)->encryptVector($dummyVector),
                'sample_count' => 1,
                'model_version' => BiometricVerificationService::MODEL_VERSION,
                'enrolled_by_admin_id' => User::where('username', 'admin')->first()->id,
                'enrolled_at' => now(),
            ]
        );

        // Mismatched face probe vector
        $mismatchedVector = array_fill(0, BiometricVerificationService::VECTOR_DIMENSION, 0.9);

        // 1x1 1x1 transparent dummy PNG base64
        $dummyPhoto = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        // Trigger 5 consecutive mismatched check-ins
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson(route('mobile.attendance.check-in'), [
                'position_id' => Position::first()->id,
                'latitude' => -5.3971400,
                'longitude' => 105.2667900,
                'accuracy' => 10,
                'is_mock' => false,
                'liveness_verified' => true,
                'face_probe_vector' => $mismatchedVector,
                'photo' => $dummyPhoto,
            ]);

            $this->assertFalse($response->json('success'));
        }

        // Verify lockout audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'BIOMETRIC_ANOMALY_LOCKOUT',
            'user_id' => $this->karyawanUser->id,
        ]);

        // Attempt 6 should immediately return 423 Locked
        $lockedResponse = $this->postJson(route('mobile.attendance.check-in'), [
            'position_id' => Position::first()->id,
            'latitude' => -5.3971400,
            'longitude' => 105.2667900,
            'accuracy' => 10,
            'is_mock' => false,
            'liveness_verified' => true,
            'face_probe_vector' => $mismatchedVector,
            'photo' => $dummyPhoto,
        ]);

        $this->assertEquals(423, $lockedResponse->status());
        $this->assertEquals('BIOMETRIC_LOCKOUT', $lockedResponse->json('error_code'));
    }

    /**
     * Test FaceTemplate model hides encrypted_vector from array/json serialization for UU PDP No. 27/2022.
     */
    public function test_face_template_hides_encrypted_vector_during_serialization(): void
    {
        $dummyVector = array_fill(0, BiometricVerificationService::VECTOR_DIMENSION, 0.25);
        $encryptedString = app(BiometricVerificationService::class)->encryptVector($dummyVector);

        $template = new FaceTemplate([
            'employee_id' => $this->employee->id,
            'encrypted_vector' => $encryptedString,
            'sample_count' => 1,
            'model_version' => 'vladmandic-face-api-v1',
        ]);

        $serializedArray = $template->toArray();

        // Attribute encrypted_vector must NOT be present in array or json representation
        $this->assertArrayNotHasKey('encrypted_vector', $serializedArray);
        $this->assertStringNotContainsString($encryptedString, json_encode($template));
    }

    /**
     * Test AttendancePhotoService sanitizes and strips metadata from uploaded images.
     */
    public function test_photo_service_sanitizes_exif_metadata_for_uu_pdp(): void
    {
        $photoService = app(AttendancePhotoService::class);

        // 1x1 dummy PNG
        $dummyBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $storedPath = $photoService->storeSelfieThumbnail($dummyBase64, $this->employee->employee_code, 'check_in');

        $this->assertNotEmpty($storedPath);
        $this->assertStringEndsWith('.jpg', $storedPath);
    }
}
