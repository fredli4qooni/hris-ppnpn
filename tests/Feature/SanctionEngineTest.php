<?php

namespace Tests\Feature;

use App\Filament\Resources\ViolationSanctions\ViolationSanctionResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeViolation;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use App\Models\ViolationCatalog;
use App\Models\ViolationSanction;
use App\Services\ViolationSanctionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SanctionEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $supervisorUser;

    protected User $atasanUser;

    protected User $karyawanUser;

    protected Employee $employee;

    protected OfficeLocation $office;

    protected Position $driverPosition;

    protected ViolationSanctionService $sanctionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->adminUser = User::where('username', 'admin')->first();
        $this->supervisorUser = User::where('username', 'supervisor')->first();
        $this->atasanUser = User::where('username', 'atasan')->first();
        $this->karyawanUser = User::where('username', 'ppnpn01')->first();
        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->first();
        $this->office = OfficeLocation::where('name', 'Kanwil DJPb Provinsi Lampung')->first();
        $this->driverPosition = Position::where('code', 'DRIVER')->first();

        $this->employee->update([
            'base_salary' => 3000000,
            'supervisor_id' => $this->supervisorUser->id,
        ]);

        $this->sanctionService = app(ViolationSanctionService::class);
    }

    /**
     * Test active violation points below 15 do not trigger any sanction.
     */
    public function test_points_below_fifteen_do_not_trigger_sanction(): void
    {
        $clauseA3 = ViolationCatalog::where('clause_number', 'A3')->first(); // 10 points

        EmployeeViolation::create([
            'employee_id' => $this->employee->id,
            'violation_catalog_id' => $clauseA3->id,
            'supervisor_id' => $this->supervisorUser->id,
            'incident_date' => '2026-11-18',
            'notes' => 'Terlambat lebih dari 1 jam.',
            'points' => $clauseA3->points,
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        $this->assertEquals(10, $this->sanctionService->calculateActivePoints($this->employee));
        $this->assertNull($this->sanctionService->determineSanctionFromPoints(10));
        $this->assertEquals(0, $this->employee->sanctions()->count());
    }

    /**
     * Test points between 15 and 20 trigger Teguran Lisan (5% for 1 month).
     */
    public function test_points_between_fifteen_and_twenty_trigger_teguran_lisan(): void
    {
        $refDate = Carbon::parse('2026-11-18', 'Asia/Jakarta');
        Carbon::setTestNow($refDate);

        $clauseB8 = ViolationCatalog::where('clause_number', 'B8')->first(); // 20 points

        EmployeeViolation::create([
            'employee_id' => $this->employee->id,
            'violation_catalog_id' => $clauseB8->id,
            'supervisor_id' => $this->supervisorUser->id,
            'incident_date' => '2026-11-18',
            'notes' => 'Kinerja kurang cermat.',
            'points' => 20,
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        $this->assertEquals(1, $this->employee->sanctions()->count());
        $sanction = $this->employee->sanctions()->first();

        $this->assertEquals(ViolationSanction::TYPE_TEGURAN_LISAN, $sanction->sanction_type);
        $this->assertEquals(20, $sanction->total_points_snapshot);
        $this->assertEquals(5.00, $sanction->deduction_percentage);
        $this->assertEquals(1, $sanction->duration_months);
        $this->assertFalse($sanction->is_termination_recommended);

        // Starts next month: December 2026
        $this->assertEquals('2026-12-01', $sanction->start_date->toDateString());
        $this->assertEquals('2026-12-31', $sanction->end_date->toDateString());
    }

    /**
     * Test points between 21 and 40 trigger SP 1 (10% for 1 month).
     */
    public function test_points_between_twenty_one_and_forty_trigger_sp_one(): void
    {
        $refDate = Carbon::parse('2026-11-18', 'Asia/Jakarta');
        Carbon::setTestNow($refDate);

        $clauseB10 = ViolationCatalog::where('clause_number', 'B10')->first(); // 30 points

        EmployeeViolation::create([
            'employee_id' => $this->employee->id,
            'violation_catalog_id' => $clauseB10->id,
            'supervisor_id' => $this->supervisorUser->id,
            'incident_date' => '2026-11-18',
            'notes' => 'Tidak menyerahkan hasil pekerjaan tepat waktu.',
            'points' => 30,
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        $sanction = $this->employee->sanctions()->first();
        $this->assertNotNull($sanction);
        $this->assertEquals(ViolationSanction::TYPE_SP_1, $sanction->sanction_type);
        $this->assertEquals(10.00, $sanction->deduction_percentage);
        $this->assertEquals(1, $sanction->duration_months);
        $this->assertEquals('2026-12-01', $sanction->start_date->toDateString());
        $this->assertEquals('2026-12-31', $sanction->end_date->toDateString());
    }

    /**
     * Test SP 2 tiered stages (SP 2A, SP 2B, SP 2C).
     */
    public function test_sp_two_tiered_stages(): void
    {
        // 1. SP 2A (41 - 60 points): 5% for 3 months
        $sp2aDef = $this->sanctionService->determineSanctionFromPoints(50);
        $this->assertEquals(ViolationSanction::TYPE_SP_2_A, $sp2aDef['type']);
        $this->assertEquals(5.00, $sp2aDef['deduction_percentage']);
        $this->assertEquals(3, $sp2aDef['duration_months']);

        // 2. SP 2B (61 - 80 points): 10% for 3 months
        $sp2bDef = $this->sanctionService->determineSanctionFromPoints(70);
        $this->assertEquals(ViolationSanction::TYPE_SP_2_B, $sp2bDef['type']);
        $this->assertEquals(10.00, $sp2bDef['deduction_percentage']);
        $this->assertEquals(3, $sp2bDef['duration_months']);

        // 3. SP 2C (81 - 99 points): 10% for 6 months
        $sp2cDef = $this->sanctionService->determineSanctionFromPoints(90);
        $this->assertEquals(ViolationSanction::TYPE_SP_2_C, $sp2cDef['type']);
        $this->assertEquals(10.00, $sp2cDef['deduction_percentage']);
        $this->assertEquals(6, $sp2cDef['duration_months']);
    }

    /**
     * Test points 100 or more trigger SP 3 with termination recommendation.
     */
    public function test_points_hundred_or_more_trigger_sp_three_termination(): void
    {
        $clauseB1 = ViolationCatalog::where('clause_number', 'B1')->first(); // 100 points

        EmployeeViolation::create([
            'employee_id' => $this->employee->id,
            'violation_catalog_id' => $clauseB1->id,
            'supervisor_id' => $this->supervisorUser->id,
            'incident_date' => '2026-11-18',
            'notes' => 'Pelanggaran berat.',
            'points' => 100,
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        $sanction = $this->employee->sanctions()->first();
        $this->assertNotNull($sanction);
        $this->assertEquals(ViolationSanction::TYPE_SP_3, $sanction->sanction_type);
        $this->assertTrue($sanction->is_termination_recommended);
        $this->assertEquals(0.00, $sanction->deduction_percentage);
    }

    /**
     * Test Dual Accumulation principle: Accumulation 1 (TL) and Accumulation 2 (SP)
     * are strictly separated and calculated independently.
     */
    public function test_dual_accumulation_strict_isolation_and_calculation(): void
    {
        // 1. Akumulasi 1 (Attendance TL deductions in November 2026):
        // TL 1 = 0.50% on 2026-11-05
        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->driverPosition->id,
            'office_location_id' => $this->office->id,
            'date' => '2026-11-05',
            'check_in_at' => '2026-11-05 07:20:00',
            'check_out_at' => '2026-11-05 18:00:00',
            'status' => Attendance::STATUS_LATE_TL,
            'check_out_status' => Attendance::CHECK_OUT_ON_TIME,
            'late_minutes' => 20,
            'required_compensation_minutes' => 0,
            'early_leave_minutes' => 0,
            'is_compensated' => true,
            'tl_category' => Attendance::TL_1,
            'deduction_percentage' => 0.50,
            'shift_type' => Attendance::SHIFT_NORMAL,
        ]);

        // TL 2 = 1.00% on 2026-11-12
        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->driverPosition->id,
            'office_location_id' => $this->office->id,
            'date' => '2026-11-12',
            'check_in_at' => '2026-11-12 07:40:00',
            'check_out_at' => '2026-11-12 18:00:00',
            'status' => Attendance::STATUS_LATE_TL,
            'check_out_status' => Attendance::CHECK_OUT_ON_TIME,
            'late_minutes' => 40,
            'required_compensation_minutes' => 0,
            'early_leave_minutes' => 0,
            'is_compensated' => true,
            'tl_category' => Attendance::TL_2,
            'deduction_percentage' => 1.00,
            'shift_type' => Attendance::SHIFT_NORMAL,
        ]);

        // Total Akumulasi 1 for Nov 2026 = 1.50% (Rp 45.000)

        // 2. Akumulasi 2 (Sanction issued in October 2026 effective in Nov 2026):
        // SP 1 = 10.00% deduction for 1 month starting 2026-11-01
        ViolationSanction::create([
            'employee_id' => $this->employee->id,
            'sanction_type' => ViolationSanction::TYPE_SP_1,
            'total_points_snapshot' => 30,
            'deduction_percentage' => 10.00,
            'duration_months' => 1,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'issued_date' => '2026-10-20',
            'is_active' => true,
            'is_termination_recommended' => false,
            'notes' => 'SP 1 potongan 10% untuk bulan November 2026',
        ]);

        // Evaluate combined deductions for November 2026
        $combinedNov = $this->sanctionService->calculateCombinedDeduction($this->employee, 2026, 11);

        $this->assertEquals(1.50, $combinedNov['accumulation_1_tl_percentage']);
        $this->assertEquals(45000.00, $combinedNov['accumulation_1_tl_amount']);

        $this->assertEquals(10.00, $combinedNov['accumulation_2_sanction_percentage']);
        $this->assertEquals(300000.00, $combinedNov['accumulation_2_sanction_amount']);

        $this->assertEquals(11.50, $combinedNov['total_deduction_percentage']);
        $this->assertEquals(345000.00, $combinedNov['total_deduction_amount']);
        $this->assertEquals(2655000.00, $combinedNov['net_salary']);

        // Evaluate combined deductions for December 2026 (SP 1 expired)
        $combinedDec = $this->sanctionService->calculateCombinedDeduction($this->employee, 2026, 12);
        $this->assertEquals(0.00, $combinedDec['accumulation_1_tl_percentage']);
        $this->assertEquals(0.00, $combinedDec['accumulation_2_sanction_percentage']);
        $this->assertEquals(3000000.00, $combinedDec['net_salary']);
    }

    /**
     * Test supervisor, atasan, and admin access control to ViolationSanction resource.
     */
    /**
     * Test admin can access ViolationSanction resource.
     */
    public function test_admin_can_access_sanction_resource(): void
    {
        $this->actingAs($this->adminUser)
            ->get(ViolationSanctionResource::getUrl('index'))
            ->assertOk();
    }

    /**
     * Test supervisor can access ViolationSanction resource.
     */
    public function test_supervisor_can_access_sanction_resource(): void
    {
        $this->actingAs($this->supervisorUser)
            ->get(ViolationSanctionResource::getUrl('index'))
            ->assertOk();
    }

    /**
     * Test atasan can access ViolationSanction resource.
     */
    public function test_atasan_can_access_sanction_resource(): void
    {
        $this->actingAs($this->atasanUser)
            ->get(ViolationSanctionResource::getUrl('index'))
            ->assertOk();
    }

    /**
     * Test karyawan is forbidden from accessing ViolationSanction resource.
     */
    public function test_karyawan_cannot_access_sanction_resource(): void
    {
        $this->actingAs($this->karyawanUser)
            ->get(ViolationSanctionResource::getUrl('index'))
            ->assertForbidden();
    }
}
