<?php

namespace Tests\Feature;

use App\Filament\Pages\SystemHealthDashboard;
use App\Models\User;
use App\Services\SystemHealthService;
use Database\Seeders\SampleFiveEmployeesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SystemHealthTelemetryTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $atasanUser;

    protected User $supervisorUser;

    protected User $karyawanUser;

    protected SystemHealthService $healthService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->seed(SampleFiveEmployeesSeeder::class);

        $this->adminUser = User::where('username', 'admin')->firstOrFail();
        $this->atasanUser = User::where('username', 'atasan')->firstOrFail();
        $this->supervisorUser = User::where('username', 'supervisor')->firstOrFail();
        $this->karyawanUser = User::where('username', 'ppnpn01')->firstOrFail();
        $this->karyawanUser->update(['must_change_password' => false]);

        $this->healthService = app(SystemHealthService::class);
    }

    /**
     * Test SystemHealthService probe metrics returns complete telemetry data (FR-901 / US-803).
     */
    public function test_system_health_service_probes_database_storage_runtime_and_scheduler(): void
    {
        $metrics = $this->healthService->getTelemetryMetrics();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('overall_status', $metrics);
        $this->assertArrayHasKey('database', $metrics);
        $this->assertArrayHasKey('storage', $metrics);
        $this->assertArrayHasKey('runtime', $metrics);
        $this->assertArrayHasKey('scheduler', $metrics);
        $this->assertArrayHasKey('operational', $metrics);

        // Database probe
        $this->assertContains($metrics['database']['status'], ['ONLINE', 'DEGRADED']);
        $this->assertGreaterThanOrEqual(0, $metrics['database']['latency_ms']);

        // Storage probe
        $this->assertGreaterThan(0, $metrics['storage']['total_space_gb']);
        $this->assertGreaterThanOrEqual(0, $metrics['storage']['used_percentage']);

        // Runtime probe
        $this->assertEquals(PHP_VERSION, $metrics['runtime']['php_version']);
        $this->assertGreaterThan(0, $metrics['runtime']['memory_usage_mb']);

        // Scheduler probe
        $this->assertGreaterThanOrEqual(3, $metrics['scheduler']['total_jobs']);

        // Operational probe
        $this->assertArrayHasKey('attendances_today', $metrics['operational']);
        $this->assertArrayHasKey('active_employees', $metrics['operational']);
        $this->assertGreaterThanOrEqual(0, $metrics['operational']['attendances_today']);
    }

    /**
     * Test exception capture generates unique incident ticket and records into audit trail.
     */
    public function test_capture_incident_generates_ticket_and_logs_audit_trail(): void
    {
        $testException = new \RuntimeException('Koneksi timeout saat sinkronisasi presensi.');
        $ticketCode = $this->healthService->captureIncident($testException, $this->adminUser);

        $this->assertStringStartsWith('ERR-DJPB-', $ticketCode);
        $this->assertEquals(17, strlen($ticketCode)); // ERR-DJPB- (9) + 8 chars = 17

        // Verify audit log record
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'SYSTEM_INCIDENT_CAPTURED',
            'user_id' => $this->adminUser->id,
        ]);

        // Verify lookup capability
        $log = $this->healthService->lookupIncident($ticketCode);
        $this->assertNotNull($log);
        $this->assertStringContainsString($ticketCode, (string) $log->new_values);
        $this->assertStringContainsString('Koneksi timeout', (string) $log->new_values);
    }

    /**
     * Test HTTP 500 custom error view renders friendly message and incident ticket code.
     */
    public function test_error_500_view_renders_friendly_message_and_incident_ticket(): void
    {
        $view = $this->view('errors.500', [
            'incidentCode' => 'ERR-DJPB-TEST1234',
        ]);

        $view->assertSee('Mohon Maaf, Terjadi Kendala Sistem');
        $view->assertSee('ERR-DJPB-TEST1234');
        $view->assertSee('HTTP 500');
        $view->assertSee('Salin');
        $view->assertSee('Kembali ke Beranda');
    }

    /**
     * Test Filament SystemHealthDashboard authorization rules.
     */
    public function test_filament_system_health_dashboard_authorization(): void
    {
        // Admin allowed
        $this->actingAs($this->adminUser);
        $this->assertTrue(SystemHealthDashboard::canAccess());

        $adminResponse = $this->get('/admin/system-health-dashboard');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Dasbor Telemetri Kesehatan Server &amp; Pemantauan Sistem', false);
        $adminResponse->assertSee('Koneksi Basis Data');
        $adminResponse->assertSee('Kapasitas Storage');
        $adminResponse->assertSee('Memori PHP Runtime');
        $adminResponse->assertSee('Jadwal Tugas Otomatis');

        // Supervisor forbidden
        $this->actingAs($this->supervisorUser);
        $this->assertFalse(SystemHealthDashboard::canAccess());

        // Atasan forbidden
        $this->actingAs($this->atasanUser);
        $this->assertFalse(SystemHealthDashboard::canAccess());

        // Karyawan forbidden
        $this->actingAs($this->karyawanUser);
        $this->assertFalse(SystemHealthDashboard::canAccess());
        $karyawanResponse = $this->get('/admin/system-health-dashboard');
        $karyawanResponse->assertStatus(403);
    }

    /**
     * Test Livewire interaction on SystemHealthDashboard for search incident and refresh probe.
     */
    public function test_livewire_system_health_dashboard_component(): void
    {
        $this->actingAs($this->adminUser);

        // Generate an incident
        $exception = new \InvalidArgumentException('Nilai toleransi GPS tidak valid');
        $code = $this->healthService->captureIncident($exception, $this->adminUser);

        Livewire::test(SystemHealthDashboard::class)
            ->call('refreshMetrics')
            ->assertSet('metrics.overall_status', 'HEALTHY')
            ->set('searchTicketCode', $code)
            ->call('searchIncident')
            ->assertSet('foundIncident.ticket_code', $code)
            ->assertSee($code);
    }
}
