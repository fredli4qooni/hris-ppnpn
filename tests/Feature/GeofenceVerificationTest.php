<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\User;
use App\Services\BiometricVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeofenceVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $karyawanUser;

    protected Employee $employee;

    protected OfficeLocation $office;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->karyawanUser = User::where('username', 'ppnpn01')->first();
        $this->karyawanUser->update(['must_change_password' => false]);

        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->first();
        $this->office = OfficeLocation::where('name', 'Kanwil DJPb Provinsi Lampung')->first();
    }

    /**
     * Test unauthenticated guest cannot call verify-geofence API endpoint.
     */
    public function test_guest_cannot_call_verify_geofence(): void
    {
        $response = $this->postJson('/mobile/attendance/verify-geofence', [
            'latitude' => -5.3971400,
            'longitude' => 105.2667900,
            'accuracy' => 10,
        ]);

        $response->assertUnauthorized();
    }

    /**
     * Test authenticated employee inside 100m perimeter passes geofence check (FR-203 / US-303).
     */
    public function test_authenticated_karyawan_inside_100m_perimeter_is_valid(): void
    {
        $officeLat = (float) $this->office->latitude;
        $officeLon = (float) $this->office->longitude;

        // ~35 meters offset north: 35 / 111139 ≈ 0.000315 degrees
        $userLat = $officeLat + (35.0 / 111139.0);
        $userLon = $officeLon;

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/verify-geofence', [
                'latitude' => $userLat,
                'longitude' => $userLon,
                'accuracy' => 12.5,
                'is_mock' => false,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'is_valid' => true,
                'status' => 'VALID',
                'is_mock' => false,
            ],
        ]);

        $distance = $response->json('data.distance_meters');
        $this->assertGreaterThanOrEqual(34.0, $distance);
        $this->assertLessThanOrEqual(36.0, $distance);
    }

    /**
     * Test employee outside 100m perimeter is rejected and audit log is recorded (FR-203 / US-303).
     */
    public function test_coordinates_outside_perimeter_are_rejected_with_audit_log(): void
    {
        $officeLat = (float) $this->office->latitude;
        $officeLon = (float) $this->office->longitude;

        // ~180 meters offset north: 180 / 111139 ≈ 0.001620 degrees
        $userLat = $officeLat + (180.0 / 111139.0);
        $userLon = $officeLon;

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/verify-geofence', [
                'latitude' => $userLat,
                'longitude' => $userLon,
                'accuracy' => 15.0,
                'is_mock' => false,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'data' => [
                'is_valid' => false,
                'status' => 'OUT_OF_BOUNDS',
            ],
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->karyawanUser->id,
            'action' => 'GEOFENCE_CHECK_FAILED',
        ]);
    }

    /**
     * Test low accuracy GPS (> 150m) is rejected per FR-204 (Accuracy Guard).
     */
    public function test_low_accuracy_gps_above_150m_is_rejected(): void
    {
        $officeLat = (float) $this->office->latitude;
        $officeLon = (float) $this->office->longitude;

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/verify-geofence', [
                'latitude' => $officeLat,
                'longitude' => $officeLon,
                'accuracy' => 175.0, // > 150 meters
                'is_mock' => false,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'data' => [
                'is_valid' => false,
                'status' => 'LOW_ACCURACY',
            ],
        ]);

        $this->assertStringContainsString('Akurasi GPS rendah', $response->json('message'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->karyawanUser->id,
            'action' => 'GPS_LOW_ACCURACY',
        ]);
    }

    /**
     * Test fake GPS mock location flag is detected and rejected per FR-204 (Anti-Mock Guard).
     */
    public function test_fake_gps_mock_location_is_detected_and_rejected(): void
    {
        $officeLat = (float) $this->office->latitude;
        $officeLon = (float) $this->office->longitude;

        $response = $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/verify-geofence', [
                'latitude' => $officeLat,
                'longitude' => $officeLon,
                'accuracy' => 10.0,
                'is_mock' => true, // Mock location flagged
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'data' => [
                'is_valid' => false,
                'status' => 'MOCK_LOCATION_DETECTED',
                'is_mock' => true,
            ],
        ]);

        $this->assertStringContainsString('Fake GPS', $response->json('message'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->karyawanUser->id,
            'action' => 'FAKE_GPS_DETECTED',
        ]);
    }

    /**
     * Test mobile attendance scanner view contains geofence verification route and office coordinates.
     */
    public function test_scanner_page_has_geofence_verification_attributes(): void
    {
        // Enroll face so steps are rendered
        $service = new BiometricVerificationService;
        $vector = $service->generateMockDescriptor(0.5);
        $service->enrollEmployee($this->employee->id, $vector);

        $response = $this->actingAs($this->karyawanUser)
            ->get('/mobile/attendance');

        $response->assertSuccessful();
        $response->assertSee(route('mobile.attendance.verify-geofence'));
        $response->assertSee('Lokasi & Geofencing Kantor', false);
        $response->assertSee('-5.39714');
        $response->assertSee('105.26679');
    }
}
