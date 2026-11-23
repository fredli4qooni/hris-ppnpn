<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SampleFiveEmployeesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaOfflineResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected User $karyawanUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->seed(SampleFiveEmployeesSeeder::class);

        $this->karyawanUser = User::where('username', 'ppnpn01')->firstOrFail();
        $this->karyawanUser->update(['must_change_password' => false]);
    }

    /**
     * Test PWA manifest.json is present, valid JSON, and correctly configured.
     */
    public function test_pwa_manifest_json_is_valid_and_contains_proper_djpb_configuration(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $manifestContent = file_get_contents($manifestPath);
        $this->assertIsString($manifestContent);

        $manifestData = json_decode($manifestContent, true);
        $this->assertIsArray($manifestData);
        $this->assertStringContainsString('DJPb', $manifestData['name']);
        $this->assertEquals('/mobile/home', $manifestData['start_url']);
        $this->assertEquals('#0058A8', $manifestData['theme_color']);
        $this->assertEquals('standalone', $manifestData['display']);
    }

    /**
     * Test PWA service-worker.js file exists and declares caching strategies.
     */
    public function test_pwa_service_worker_asset_exists_and_caches_shell_assets(): void
    {
        $swPath = public_path('service-worker.js');
        $this->assertFileExists($swPath);

        $swContent = file_get_contents($swPath);
        $this->assertIsString($swContent);
        $this->assertStringContainsString('hris-djpb-', $swContent);
        $this->assertStringContainsString('/mobile/offline', $swContent);
        $this->assertStringContainsString('/manifest.json', $swContent);
        $this->assertStringContainsString('addEventListener(\'fetch\'', $swContent);
    }

    /**
     * Test PWA offline fallback shell is accessible publicly without requiring login.
     */
    public function test_pwa_offline_fallback_page_is_accessible_without_authentication(): void
    {
        $response = $this->get(route('mobile.offline'));

        $response->assertOk();
        $response->assertSee('Koneksi Internet Terputus');
        $response->assertSee('Mode Offline Aktif');
        $response->assertSee('Kanwil DJPb Provinsi Lampung');
        $response->assertSee('Coba Hubungkan Kembali');
    }

    /**
     * Test mobile layout renders interactive offline banner and service worker registration.
     */
    public function test_mobile_layout_renders_offline_banner_component(): void
    {
        $response = $this->actingAs($this->karyawanUser)->get(route('mobile.home'));

        $response->assertOk();
        $response->assertSee('isOnline: navigator.onLine', false);
        $response->assertSee('showReconnected', false);
        $response->assertSee('Koneksi Internet Terputus');
        $response->assertSee('Koneksi Internet Pulih');
        $response->assertSee('serviceWorker.register(\'/service-worker.js\')', false);
    }

    /**
     * Test attendance scanner view includes offline state alerts and disabled guards.
     */
    public function test_mobile_scanner_includes_offline_resilience_controls(): void
    {
        $response = $this->actingAs($this->karyawanUser)->get(route('mobile.attendance'));

        $response->assertOk();
        $response->assertSee('@online.window="handleNetworkOnline()"', false);
        $response->assertSee('@offline.window="handleNetworkOffline()"', false);
        $response->assertSee('Jaringan Internet Terputus (Mode Offline)');
        $response->assertSee('Koneksi Offline (Menunggu Sinyal)');
    }
}
