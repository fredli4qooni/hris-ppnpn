<?php

namespace Tests\Feature;

use App\Filament\Resources\OfficeLocations\Pages\CreateOfficeLocation;
use App\Models\OfficeLocation;
use App\Models\User;
use App\Services\GeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GeofenceTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $supervisorUser;

    protected User $atasanUser;

    protected User $karyawanUser;

    protected OfficeLocation $office;

    protected GeofenceService $geofenceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->adminUser = User::where('username', 'admin')->first();
        $this->supervisorUser = User::where('username', 'supervisor')->first();
        $this->atasanUser = User::where('username', 'atasan')->first();
        $this->karyawanUser = User::where('username', 'ppnpn01')->first();

        $this->office = OfficeLocation::where('name', 'Kanwil DJPb Provinsi Lampung')->first();
        $this->geofenceService = new GeofenceService;
    }

    /**
     * Test Haversine formula distance calculation accuracy.
     */
    public function test_haversine_distance_between_same_points_is_zero(): void
    {
        $distance = $this->geofenceService->calculateDistance(
            -5.3971400,
            105.2667900,
            -5.3971400,
            105.2667900
        );

        $this->assertEquals(0.0, $distance);
    }

    /**
     * TC-07: Perhitungan jarak koordinat dengan rumus Haversine terhadap radius 100m.
     * Titik selisih 50m dinyatakan VALID, titik selisih 150m dinyatakan OUT_OF_BOUNDS.
     */
    public function test_tc_07_point_at_50m_is_valid_and_point_at_150m_is_out_of_bounds(): void
    {
        $officeLat = (float) $this->office->latitude;
        $officeLon = (float) $this->office->longitude;

        // Approx 1 degree latitude = 111,139 meters
        // 50 meters offset = 50 / 111139 ≈ 0.000450 degrees
        $point50mLat = $officeLat + (50.0 / 111139.0);
        $point50mLon = $officeLon;

        $dist50m = $this->geofenceService->calculateDistance($officeLat, $officeLon, $point50mLat, $point50mLon);
        $this->assertGreaterThanOrEqual(49.0, $dist50m);
        $this->assertLessThanOrEqual(51.0, $dist50m);

        $result50m = $this->geofenceService->validatePresenceLocation($point50mLat, $point50mLon, $this->office);
        $this->assertTrue($result50m['is_valid']);
        $this->assertEquals('VALID', $result50m['status']);
        $this->assertStringContainsString('VALID', $result50m['message']);

        // 150 meters offset = 150 / 111139 ≈ 0.001350 degrees
        $point150mLat = $officeLat + (150.0 / 111139.0);
        $point150mLon = $officeLon;

        $dist150m = $this->geofenceService->calculateDistance($officeLat, $officeLon, $point150mLat, $point150mLon);
        $this->assertGreaterThanOrEqual(149.0, $dist150m);
        $this->assertLessThanOrEqual(151.0, $dist150m);

        $result150m = $this->geofenceService->validatePresenceLocation($point150mLat, $point150mLon, $this->office);
        $this->assertFalse($result150m['is_valid']);
        $this->assertEquals('OUT_OF_BOUNDS', $result150m['status']);
        $this->assertStringContainsString('OUT OF BOUNDS', $result150m['message']);
    }

    /**
     * Test validation handles inactive offices correctly.
     */
    public function test_inactive_office_is_ignored_by_geofence_service(): void
    {
        $this->office->update(['is_active' => false]);

        $result = $this->geofenceService->validatePresenceLocation(
            (float) $this->office->latitude,
            (float) $this->office->longitude
        );

        $this->assertFalse($result['is_valid']);
        $this->assertEquals('OUT_OF_BOUNDS', $result['status']);
        $this->assertStringContainsString('Tidak ada titik lokasi kantor aktif', $result['message']);
    }

    /**
     * Test admin can access office locations list page.
     */
    public function test_admin_can_access_office_locations_list(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/office-locations');

        $response->assertSuccessful();
        $response->assertSee('Kanwil DJPb Provinsi Lampung');
    }

    /**
     * Test supervisor can view office locations list.
     */
    public function test_supervisor_can_view_office_locations_list(): void
    {
        $response = $this->actingAs($this->supervisorUser)
            ->get('/admin/office-locations');

        $response->assertSuccessful();
    }

    /**
     * Test atasan can view office locations list.
     */
    public function test_atasan_can_view_office_locations_list(): void
    {
        $response = $this->actingAs($this->atasanUser)
            ->get('/admin/office-locations');

        $response->assertSuccessful();
    }

    /**
     * Test karyawan is forbidden from accessing office locations management.
     */
    public function test_karyawan_cannot_access_office_locations(): void
    {
        $response = $this->actingAs($this->karyawanUser)
            ->get('/admin/office-locations');

        $response->assertForbidden();
    }

    /**
     * Test admin can create a new office location and audit log is recorded.
     */
    public function test_admin_can_create_office_location_via_filament(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(CreateOfficeLocation::class)
            ->fillForm([
                'name' => 'KPPN Bandar Lampung',
                'address' => 'Jl. Cut Mutia No. 29, Bandar Lampung',
                'latitude' => -5.4281200,
                'longitude' => 105.2589100,
                'radius_meters' => 120,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('office_locations', [
            'name' => 'KPPN Bandar Lampung',
            'radius_meters' => 120,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'OFFICE_LOCATION_CREATED',
            'entity_type' => OfficeLocation::class,
        ]);
    }
}
