<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeViolation;
use App\Models\User;
use App\Models\ViolationCatalog;
use Carbon\Carbon;
use Database\Seeders\SampleFiveEmployeesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EarlyWarningSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $supervisorUser;

    protected Employee $employee;

    protected ViolationCatalog $catalogItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->adminUser = User::where('username', 'admin')->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->firstOrFail();

        $this->supervisorUser = User::where('username', 'supervisor')->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'supervisor'))->firstOrFail();

        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->firstOrFail();
        $this->employee->user?->update(['must_change_password' => false]);

        $this->catalogItem = ViolationCatalog::firstOrFail();
    }

    /**
     * Test FR-407: Employee with < 60 points has EWS_NORMAL status.
     */
    public function test_employee_with_under_60_points_has_normal_status(): void
    {
        EmployeeViolation::create([
            'employee_id' => $this->employee->id,
            'violation_catalog_id' => $this->catalogItem->id,
            'supervisor_id' => $this->supervisorUser->id,
            'incident_date' => Carbon::today(),
            'notes' => 'Pelanggaran ringan 20 poin',
            'points' => 20,
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        $this->assertEquals(20, $this->employee->calculateActiveViolationPoints());

        $ews = $this->employee->getEwsStatus();
        $this->assertEquals(Employee::EWS_NORMAL, $ews['status']);
        $this->assertEquals('success', $ews['color']);
        $this->assertFalse($ews['is_warning']);
        $this->assertFalse($ews['is_critical']);
        $this->assertFalse($ews['is_alert']);
    }

    /**
     * Test FR-407: Employee with 60 to 84 points triggers EWS_WARNING status (Gold/Amber).
     */
    public function test_employee_with_60_to_84_points_triggers_warning_status(): void
    {
        EmployeeViolation::create([
            'employee_id' => $this->employee->id,
            'violation_catalog_id' => $this->catalogItem->id,
            'supervisor_id' => $this->supervisorUser->id,
            'incident_date' => Carbon::today(),
            'notes' => 'Akumulasi sanksi mencapai 65 poin',
            'points' => 65,
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        $this->assertEquals(65, $this->employee->calculateActiveViolationPoints());

        $ews = $this->employee->getEwsStatus();
        $this->assertEquals(Employee::EWS_WARNING, $ews['status']);
        $this->assertEquals('warning', $ews['color']);
        $this->assertTrue($ews['is_warning']);
        $this->assertFalse($ews['is_critical']);
        $this->assertTrue($ews['is_alert']);
        $this->assertStringContainsString('Peringatan Kuning', $ews['label']);
    }

    /**
     * Test FR-407: Employee with >= 85 points triggers EWS_CRITICAL status (Red Alert).
     */
    public function test_employee_with_85_or_more_points_triggers_critical_status(): void
    {
        EmployeeViolation::create([
            'employee_id' => $this->employee->id,
            'violation_catalog_id' => $this->catalogItem->id,
            'supervisor_id' => $this->supervisorUser->id,
            'incident_date' => Carbon::today(),
            'notes' => 'Akumulasi sanksi berat mencapai 90 poin',
            'points' => 90,
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        $this->assertEquals(90, $this->employee->calculateActiveViolationPoints());

        $ews = $this->employee->getEwsStatus();
        $this->assertEquals(Employee::EWS_CRITICAL, $ews['status']);
        $this->assertEquals('danger', $ews['color']);
        $this->assertFalse($ews['is_warning']);
        $this->assertTrue($ews['is_critical']);
        $this->assertTrue($ews['is_alert']);
        $this->assertStringContainsString('Alarm Merah Kritis', $ews['label']);
    }

    /**
     * Test FR-407: Cancelled violations do not contribute to EWS threshold calculations.
     */
    public function test_cancelled_violations_do_not_affect_ews_status(): void
    {
        EmployeeViolation::create([
            'employee_id' => $this->employee->id,
            'violation_catalog_id' => $this->catalogItem->id,
            'supervisor_id' => $this->supervisorUser->id,
            'incident_date' => Carbon::today(),
            'notes' => 'Pelanggaran dibatalkan',
            'points' => 90,
            'status' => EmployeeViolation::STATUS_CANCELLED,
            'cancellation_reason' => 'Klarifikasi dinas resmi',
        ]);

        $this->assertEquals(0, $this->employee->calculateActiveViolationPoints());

        $ews = $this->employee->getEwsStatus();
        $this->assertEquals(Employee::EWS_NORMAL, $ews['status']);
    }

    /**
     * Test FR-407: Query scope scopeEwsStatus correctly filters employees by threshold.
     */
    public function test_query_scope_filters_employees_by_ews_status(): void
    {
        $this->seed(SampleFiveEmployeesSeeder::class);

        // Employee 1: Critical (85 pts)
        EmployeeViolation::create([
            'employee_id' => $this->employee->id,
            'violation_catalog_id' => $this->catalogItem->id,
            'supervisor_id' => $this->supervisorUser->id,
            'incident_date' => Carbon::today(),
            'notes' => '85 poin',
            'points' => 85,
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        // Employee 2: Warning (60 pts)
        $employee2 = Employee::where('id', '!=', $this->employee->id)->firstOrFail();
        EmployeeViolation::create([
            'employee_id' => $employee2->id,
            'violation_catalog_id' => $this->catalogItem->id,
            'supervisor_id' => $this->supervisorUser->id,
            'incident_date' => Carbon::today(),
            'notes' => '60 poin',
            'points' => 60,
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        $criticalCount = Employee::ewsStatus(Employee::EWS_CRITICAL)->count();
        $warningCount = Employee::ewsStatus(Employee::EWS_WARNING)->count();

        $this->assertEquals(1, $criticalCount);
        $this->assertEquals(1, $warningCount);
    }

    /**
     * Test FR-407: Berita Acara Pembinaan route produces printable report for authorized actors.
     */
    public function test_authorized_user_can_view_discipline_summary_report(): void
    {
        EmployeeViolation::create([
            'employee_id' => $this->employee->id,
            'violation_catalog_id' => $this->catalogItem->id,
            'supervisor_id' => $this->supervisorUser->id,
            'incident_date' => Carbon::today(),
            'notes' => 'Pelanggaran indisipliner 70 poin',
            'points' => 70,
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.employees.discipline-summary', $this->employee));

        $response->assertStatus(200);
        $response->assertSee('BERITA ACARA PEMBINAAN DISIPLIN PPNPN');
        $response->assertSee($this->employee->full_name);
        $response->assertSee('70 Poin');
        $response->assertSee('Peringatan Kuning');
    }

    /**
     * Test FR-407: Employee themself can view their own Berita Acara Pembinaan.
     */
    public function test_employee_can_view_own_discipline_summary(): void
    {
        /** @var User $employeeUser */
        $employeeUser = $this->employee->user;

        $response = $this->actingAs($employeeUser)
            ->get(route('admin.employees.discipline-summary', $this->employee));

        $response->assertStatus(200);
        $response->assertSee($this->employee->full_name);
    }

    /**
     * Test FR-407: Mobile views display EWS alarm banner when threshold is exceeded.
     */
    public function test_mobile_views_render_ews_alert_banner_when_in_warning(): void
    {
        EmployeeViolation::create([
            'employee_id' => $this->employee->id,
            'violation_catalog_id' => $this->catalogItem->id,
            'supervisor_id' => $this->supervisorUser->id,
            'incident_date' => Carbon::today(),
            'notes' => 'Akumulasi sanksi 88 poin kritis',
            'points' => 88,
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        /** @var User $employeeUser */
        $employeeUser = $this->employee->user;

        // Home View
        $homeResponse = $this->actingAs($employeeUser)->get(route('mobile.home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('Early Warning System (EWS) Disiplin');
        $homeResponse->assertSee('88 Poin');

        // Profile View
        $profileResponse = $this->actingAs($employeeUser)->get(route('mobile.profile'));
        $profileResponse->assertStatus(200);
        $profileResponse->assertSee('Early Warning System (EWS) Disiplin');
        $profileResponse->assertSee('Unduh Ringkasan Pembinaan (BAP)');
    }
}
