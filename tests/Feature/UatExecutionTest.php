<?php

namespace Tests\Feature;

use App\Models\UatTestCase;
use App\Models\User;
use App\Services\UatExecutionService;
use Database\Seeders\SampleFiveEmployeesSeeder;
use Database\Seeders\UatTestCaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UatExecutionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $atasanUser;

    protected User $supervisorUser;

    protected User $karyawanUser;

    protected UatExecutionService $uatService;

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

        $this->uatService = app(UatExecutionService::class);
    }

    /**
     * Test Seeder accurately populates all 15 core UAT scenarios with proper data integrity.
     */
    public function test_uat_test_case_seeder_populates_15_standard_scenarios(): void
    {
        $this->seed(UatTestCaseSeeder::class);

        $this->assertEquals(15, UatTestCase::count());

        // Verify key scenarios across functional modules
        $this->assertDatabaseHas('uat_test_cases', ['test_case_code' => 'UAT-PRES-01']);
        $this->assertDatabaseHas('uat_test_cases', ['test_case_code' => 'UAT-PRES-02']);
        $this->assertDatabaseHas('uat_test_cases', ['test_case_code' => 'UAT-PRES-03']);
        $this->assertDatabaseHas('uat_test_cases', ['test_case_code' => 'UAT-GEO-04']);
        $this->assertDatabaseHas('uat_test_cases', ['test_case_code' => 'UAT-GEO-05']);
        $this->assertDatabaseHas('uat_test_cases', ['test_case_code' => 'UAT-GEO-06']);
        $this->assertDatabaseHas('uat_test_cases', ['test_case_code' => 'UAT-EXC-07']);
        $this->assertDatabaseHas('uat_test_cases', ['test_case_code' => 'UAT-EXC-08']);
        $this->assertDatabaseHas('uat_test_cases', ['test_case_code' => 'UAT-EXC-09']);
        $this->assertDatabaseHas('uat_test_cases', ['test_case_code' => 'UAT-APPR-10']);
        $this->assertDatabaseHas('uat_test_cases', ['test_case_code' => 'UAT-DISC-11']);
        $this->assertDatabaseHas('uat_test_cases', ['test_case_code' => 'UAT-DISC-12']);
        $this->assertDatabaseHas('uat_test_cases', ['test_case_code' => 'UAT-CLOS-13']);
        $this->assertDatabaseHas('uat_test_cases', ['test_case_code' => 'UAT-EXPO-14']);
        $this->assertDatabaseHas('uat_test_cases', ['test_case_code' => 'UAT-DASH-15']);
    }

    /**
     * Test UAT execution service runs all verifications and achieves 100% pass rate.
     */
    public function test_uat_execution_service_runs_all_verifications_with_100_percent_pass_rate(): void
    {
        $this->actingAs($this->adminUser);

        $results = $this->uatService->runAllVerifications('Tim Penguji Lapangan Kanwil DJPb');

        $this->assertEquals(15, $results['total']);
        $this->assertEquals(15, $results['passed']);
        $this->assertEquals(0, $results['failed']);
        $this->assertEquals(100.0, $results['pass_rate']);
        $this->assertTrue($results['exit_criteria_met']);

        // Verify audit log record
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'UAT_FULL_VERIFICATION_EXECUTED',
            'user_id' => $this->adminUser->id,
        ]);
    }

    /**
     * Test defect tracking and remediation lifecycle (SLA <= 24 hours).
     */
    public function test_uat_defect_logging_and_remediation_lifecycle(): void
    {
        $this->actingAs($this->adminUser);
        $this->uatService->ensureDefaultTestCasesExist();

        // 1. Record a defect on a scenario
        $defectCase = $this->uatService->recordDefect(
            testCaseCode: 'UAT-GEO-04',
            actualResult: 'Ditemukan keterlambatan respon 1,2 detik pada jaringan edge.',
            severity: 'minor',
            notes: 'Optimasi caching radius geofence diperlukan.',
            reporter: 'Auditor Lapangan DJPb'
        );

        $this->assertEquals('failed', $defectCase->status);
        $this->assertEquals('minor', $defectCase->severity_if_failed);

        $stats = $this->uatService->getStatistics();
        $this->assertEquals(14, $stats['passed']);
        $this->assertEquals(1, $stats['failed']);
        $this->assertFalse($stats['exit_criteria_met']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'UAT_DEFECT_LOGGED',
        ]);

        // 2. Remediate / Resolve the defect
        $remediatedCase = $this->uatService->resolveDefect(
            testCaseCode: 'UAT-GEO-04',
            resolutionNotes: 'Algoritma Haversine dioptimasi dengan query pre-filtered bounding box.',
            resolver: 'Dewi Nadila (Lead Developer)'
        );

        $this->assertEquals('passed', $remediatedCase->status);
        $this->assertNull($remediatedCase->severity_if_failed);
        $this->assertStringContainsString('Remediasi Selesai', $remediatedCase->notes);

        $freshStats = $this->uatService->getStatistics();
        $this->assertEquals(15, $freshStats['passed']);
        $this->assertEquals(0, $freshStats['failed']);
        $this->assertTrue($freshStats['exit_criteria_met']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'UAT_DEFECT_REMEDIATED',
        ]);
    }

    /**
     * Test official PDF report export access authorization.
     */
    public function test_uat_report_pdf_export_is_accessible_to_authorized_roles(): void
    {
        $this->uatService->ensureDefaultTestCasesExist();

        // 1. Unauthenticated users get redirected to login
        $response = $this->get(route('admin.uat.export-pdf'));
        $response->assertRedirect(route('login'));

        // 2. Karyawan role is forbidden (403)
        $response = $this->actingAs($this->karyawanUser)->get(route('admin.uat.export-pdf'));
        $response->assertForbidden();

        // 3. Admin user can view the official UAT report
        $response = $this->actingAs($this->adminUser)->get(route('admin.uat.export-pdf'));
        $response->assertOk();
        $response->assertSee('BERITA ACARA PENGUJIAN PENERIMAAN PENGGUNA');
        $response->assertSee('BA-UAT/WPB.08/PPK/2026/001');
        $response->assertSee('UAT-PRES-01');
        $response->assertSee('UAT-DASH-15');
        $response->assertSee('Hendra Setiawan, M.M.');

        // 4. Atasan / PPK can also view the official UAT report
        $response = $this->actingAs($this->atasanUser)->get(route('admin.uat.export-pdf'));
        $response->assertOk();

        // 5. Supervisor can also view the official UAT report
        $response = $this->actingAs($this->supervisorUser)->get(route('admin.uat.export-pdf'));
        $response->assertOk();
    }

    /**
     * Test Filament admin resource access permissions.
     */
    public function test_filament_uat_resource_access_control(): void
    {
        $this->uatService->ensureDefaultTestCasesExist();

        // Admin can access the resource index
        $response = $this->actingAs($this->adminUser)->get('/admin/uat-test-cases');
        $response->assertOk();

        // Karyawan cannot access the admin resource
        $response = $this->actingAs($this->karyawanUser)->get('/admin/uat-test-cases');
        $response->assertForbidden();
    }
}
