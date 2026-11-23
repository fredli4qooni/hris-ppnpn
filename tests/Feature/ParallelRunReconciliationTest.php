<?php

namespace Tests\Feature;

use App\Filament\Resources\ParallelRunReconciliations\ParallelRunReconciliationResource;
use App\Models\Employee;
use App\Models\ParallelRunReconciliation;
use App\Models\User;
use App\Services\ParallelRunReconciliationService;
use Carbon\Carbon;
use Database\Seeders\SampleFiveEmployeesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParallelRunReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $atasanUser;

    protected User $supervisorUser;

    protected User $karyawanUser;

    protected Employee $employee;

    protected ParallelRunReconciliationService $service;

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

        $this->employee = Employee::firstOrFail();
        $this->service = app(ParallelRunReconciliationService::class);
    }

    /**
     * Test evaluatePair logic accurately identifies match, drift, mismatch, and missing data (US-801).
     */
    public function test_evaluate_pair_determines_match_time_drift_and_status_mismatch(): void
    {
        // 1. Perfect Match (07:25 vs 07:27 -> 2 min drift <= 5 min)
        $match = $this->service->evaluatePair('07:25', '17:00', 'HADIR', '07:27', '17:00', 'HADIR');
        $this->assertEquals(ParallelRunReconciliation::TYPE_MATCH, $match['discrepancy_type']);
        $this->assertEquals(2, $match['time_diff_minutes']);
        $this->assertEquals(100.0, $match['concordance_score']);
        $this->assertEquals(ParallelRunReconciliation::STATUS_AUTO_MATCHED, $match['resolution_status']);
        $this->assertEquals(0.0, $match['penalty_discrepancy_amount']);

        // 2. Time Drift (> 5 minutes: 07:20 vs 07:35 -> 15 min drift)
        $drift = $this->service->evaluatePair('07:20', '17:00', 'HADIR', '07:35', '17:00', 'HADIR');
        $this->assertEquals(ParallelRunReconciliation::TYPE_TIME_DRIFT, $drift['discrepancy_type']);
        $this->assertEquals(15, $drift['time_diff_minutes']);
        $this->assertEquals(90.0, $drift['concordance_score']);
        $this->assertEquals(ParallelRunReconciliation::STATUS_PENDING, $drift['resolution_status']);

        // 3. Status Mismatch (Manual: HADIR vs Digital: ALPA)
        $mismatch = $this->service->evaluatePair('07:25', '17:00', 'HADIR', null, null, 'ALPA');
        // When digital is null & status is ALPA, status mismatch
        $this->assertEquals(ParallelRunReconciliation::TYPE_DIGITAL_MISSING, $mismatch['discrepancy_type']);

        $statusDiff = $this->service->evaluatePair('07:25', '17:00', 'HADIR', '07:25', '17:00', 'IZIN');
        $this->assertEquals(ParallelRunReconciliation::TYPE_STATUS_MISMATCH, $statusDiff['discrepancy_type']);
        $this->assertEquals(50.0, $statusDiff['concordance_score']);
        $this->assertEquals(ParallelRunReconciliation::STATUS_PENDING, $statusDiff['resolution_status']);

        // 4. Missing in Manual (Manual null, Digital present)
        $manualMissing = $this->service->evaluatePair(null, null, 'ALPA', '07:25', '17:00', 'HADIR');
        $this->assertEquals(ParallelRunReconciliation::TYPE_MANUAL_MISSING, $manualMissing['discrepancy_type']);
        $this->assertEquals(ParallelRunReconciliation::STATUS_PENDING, $manualMissing['resolution_status']);
    }

    /**
     * Test reconcileRecord persists evaluation cleanly into the database.
     */
    public function test_reconcile_record_persists_into_database(): void
    {
        $record = $this->service->reconcileRecord(
            $this->employee,
            '2026-11-20',
            ['check_in' => '07:22', 'check_out' => '17:00', 'status' => 'HADIR'],
            ['check_in' => '07:24', 'check_out' => '17:00', 'status' => 'HADIR']
        );

        $this->assertDatabaseHas('parallel_run_reconciliations', [
            'id' => $record->id,
            'employee_id' => $this->employee->id,
            'discrepancy_type' => ParallelRunReconciliation::TYPE_MATCH,
            'time_diff_minutes' => 2,
            'resolution_status' => ParallelRunReconciliation::STATUS_AUTO_MATCHED,
        ]);

        $this->assertEquals('2026-11-20', $record->presence_date->toDateString());

        $this->assertTrue($record->isMatch());
        $this->assertTrue($record->isResolved());
        $this->assertEquals('Selaras (Match)', $record->getDiscrepancyTypeLabel());
        $this->assertEquals('success', $record->getDiscrepancyColor());
    }

    /**
     * Test resolveDiscrepancy updates resolution status, adds notes, and logs audit trail.
     */
    public function test_resolve_discrepancy_records_audit_trail_and_marks_resolved(): void
    {
        $record = $this->service->reconcileRecord(
            $this->employee,
            '2026-11-20',
            ['check_in' => '07:20', 'check_out' => '17:00', 'status' => 'HADIR'],
            ['check_in' => '07:38', 'check_out' => '17:00', 'status' => 'HADIR']
        );

        $this->assertEquals(ParallelRunReconciliation::STATUS_PENDING, $record->resolution_status);

        $resolved = $this->service->resolveDiscrepancy(
            $record,
            'Keterlambatan digital disebabkan antrian pemindai wajah di lobi; absensi manual 07:20 disahkan sah oleh PPK.',
            $this->atasanUser,
            'MANUAL'
        );

        $this->assertEquals(ParallelRunReconciliation::STATUS_RESOLVED, $resolved->resolution_status);
        $this->assertEquals($this->atasanUser->id, $resolved->resolved_by);
        $this->assertNotNull($resolved->resolved_at);
        $this->assertEquals(100.0, $resolved->concordance_score);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'PARALLEL_RUN_DISCREPANCY_RESOLVED',
            'entity_type' => ParallelRunReconciliation::class,
            'entity_id' => $record->id,
            'user_id' => $this->atasanUser->id,
        ]);
    }

    /**
     * Test getConcordanceStatistics accurately aggregates metrics.
     */
    public function test_get_concordance_statistics_calculates_rates_and_totals(): void
    {
        // 3 perfect matches
        for ($i = 1; $i <= 3; $i++) {
            $this->service->reconcileRecord(
                $this->employee,
                "2026-11-0{$i}",
                ['check_in' => '07:25', 'check_out' => '17:00', 'status' => 'HADIR'],
                ['check_in' => '07:25', 'check_out' => '17:00', 'status' => 'HADIR']
            );
        }

        // 1 pending drift
        $drift = $this->service->reconcileRecord(
            $this->employee,
            '2026-11-04',
            ['check_in' => '07:20', 'check_out' => '17:00', 'status' => 'HADIR'],
            ['check_in' => '07:45', 'check_out' => '17:00', 'status' => 'HADIR']
        );

        $stats = $this->service->getConcordanceStatistics();

        $this->assertEquals(4, $stats['total_records']);
        $this->assertEquals(3, $stats['matched_records']);
        $this->assertEquals(1, $stats['drift_records']);
        $this->assertEquals(1, $stats['pending_discrepancies']);
        $this->assertEquals(75.0, $stats['concordance_rate']); // 3 / 4 = 75%

        // Resolve drift
        $this->service->resolveDiscrepancy($drift, 'Disahkan PPK', $this->atasanUser);

        $updatedStats = $this->service->getConcordanceStatistics();
        $this->assertEquals(0, $updatedStats['pending_discrepancies']);
        $this->assertEquals(1, $updatedStats['resolved_discrepancies']);
        $this->assertEquals(100.0, $updatedStats['concordance_rate']); // (3 + 1) / 4 = 100%
    }

    /**
     * Test seedPilotDataset populates 10 working days for all active employees with high concordance (> 95%).
     */
    public function test_seed_pilot_dataset_populates_10_working_days_for_all_employees(): void
    {
        $startDate = Carbon::create(2026, 11, 2, 8, 0, 0, 'Asia/Jakarta'); // Monday
        $dataset = $this->service->seedPilotDataset($startDate, 10);

        $employeeCount = Employee::where('is_active', true)->count();
        $this->assertEquals($employeeCount * 10, $dataset->count());

        $stats = $this->service->getConcordanceStatistics();
        $this->assertEquals($employeeCount * 10, $stats['total_records']);
        $this->assertGreaterThanOrEqual(95.0, $stats['concordance_rate']);
    }

    /**
     * Test unauthenticated guest cannot access reconciliation export and gets redirected.
     */
    public function test_unauthenticated_guest_cannot_access_export_pdf(): void
    {
        $response = $this->get(route('admin.reconciliation.export-pdf'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test karyawan role is barred from reconciliation export and Filament resource.
     */
    public function test_karyawan_cannot_access_reconciliation_module(): void
    {
        $this->actingAs($this->karyawanUser);

        $this->assertFalse(ParallelRunReconciliationResource::canAccess());

        $response = $this->get(route('admin.reconciliation.export-pdf'));
        $response->assertForbidden();
    }

    /**
     * Test Admin and Atasan (PPK) can access reconciliation export and Filament resource.
     */
    public function test_admin_and_atasan_can_access_reconciliation_module_and_pdf(): void
    {
        // Admin
        $this->actingAs($this->adminUser);
        $this->assertTrue(ParallelRunReconciliationResource::canAccess());

        $adminResponse = $this->get(route('admin.reconciliation.export-pdf'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('BERITA ACARA REKONSILIASI UJI COBA PARALEL', false);
        $adminResponse->assertSee('KANTOR WILAYAH PROVINSI LAMPUNG', false);
        $adminResponse->assertSee('Tingkat Keselarasan', false);
        $adminResponse->assertSee('Dewi Nadila, S.Kom.', false);
        $adminResponse->assertSee('Hendra Setiawan, M.M.', false);

        // Atasan (Kasubbag Umum / PPK)
        $this->actingAs($this->atasanUser);
        $this->assertTrue(ParallelRunReconciliationResource::canAccess());

        $atasanResponse = $this->get(route('admin.reconciliation.export-pdf'));
        $atasanResponse->assertStatus(200);
        $atasanResponse->assertSee('BERITA ACARA REKONSILIASI UJI COBA PARALEL', false);
    }
}
