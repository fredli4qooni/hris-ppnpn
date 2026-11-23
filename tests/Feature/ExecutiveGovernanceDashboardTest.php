<?php

namespace Tests\Feature;

use App\Filament\Pages\ExecutiveGovernanceDashboard;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use App\Models\ViolationSanction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ExecutiveGovernanceDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $atasanUser;

    protected User $supervisorUser;

    protected User $karyawanUser;

    protected Employee $employee;

    protected OfficeLocation $office;

    protected Position $obPosition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Storage::fake('public');

        $this->adminUser = User::where('username', 'admin')->first() ?? User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->firstOrFail();
        $this->adminUser->update(['must_change_password' => false]);

        $this->atasanUser = User::where('username', 'atasan')->first() ?? User::whereHas('roles', fn ($q) => $q->where('name', 'atasan'))->firstOrFail();
        $this->atasanUser->update(['must_change_password' => false]);

        $this->supervisorUser = User::where('username', 'supervisor')->first() ?? User::whereHas('roles', fn ($q) => $q->where('name', 'supervisor'))->firstOrFail();
        $this->supervisorUser->update(['must_change_password' => false]);

        $this->karyawanUser = User::where('username', 'ppnpn01')->firstOrFail();
        $this->karyawanUser->update(['must_change_password' => false]);

        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->firstOrFail();
        $this->employee->update([
            'is_active' => true,
            'base_salary' => 3000000.00,
        ]);

        $this->office = OfficeLocation::firstOrFail();
        $this->obPosition = Position::where('code', 'OB')->firstOrFail();
    }

    /**
     * Test FR-703: Access restricted to Atasan and Admin; Supervisor and Karyawan are forbidden.
     */
    public function test_atasan_can_access_executive_dashboard(): void
    {
        $response = $this->actingAs($this->atasanUser)->get('/admin/executive-governance-dashboard');
        $response->assertOk();
        $response->assertSee('Dasbor Tata Kelola Eksekutif');
    }

    public function test_admin_can_access_executive_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/executive-governance-dashboard');
        $response->assertOk();
        $response->assertSee('Dasbor Tata Kelola Eksekutif');
    }

    public function test_supervisor_cannot_access_executive_dashboard(): void
    {
        $response = $this->actingAs($this->supervisorUser)->get('/admin/executive-governance-dashboard');
        $response->assertForbidden();
    }

    public function test_karyawan_cannot_access_executive_dashboard(): void
    {
        $response = $this->actingAs($this->karyawanUser)->get('/admin/executive-governance-dashboard');
        $response->assertForbidden();
    }

    /**
     * Test FR-703: Dashboard displays budget efficiency metrics (Akumulasi 1 TL & Akumulasi 2 SP).
     */
    public function test_executive_dashboard_renders_budget_efficiency_summary(): void
    {
        $year = 2026;
        $month = 11;

        // Create TL attendance (Akumulasi 1)
        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->obPosition->id,
            'office_location_id' => $this->office->id,
            'date' => "{$year}-{$month}-05",
            'check_in_at' => Carbon::parse("{$year}-{$month}-05 08:15:00", 'Asia/Jakarta'),
            'status' => Attendance::STATUS_LATE_TL,
            'shift_type' => Attendance::SHIFT_NORMAL,
            'late_minutes' => 45,
            'tl_category' => Attendance::TL_2,
            'deduction_percentage' => 1.25,
        ]);

        // Create active SP sanction (Akumulasi 2)
        ViolationSanction::create([
            'employee_id' => $this->employee->id,
            'sanction_type' => ViolationSanction::TYPE_SP_1,
            'total_points_snapshot' => 25,
            'deduction_percentage' => 5.00,
            'duration_months' => 1,
            'start_date' => "{$year}-{$month}-01",
            'end_date' => "{$year}-{$month}-30",
            'issued_date' => "{$year}-{$month}-01",
            'is_active' => true,
        ]);

        Livewire::actingAs($this->atasanUser)
            ->test(ExecutiveGovernanceDashboard::class, [
                'year' => $year,
                'month' => $month,
            ])
            ->assertSee('Pagu Honor Bruto')
            ->assertSee('Efisiensi Akumulasi 1 (TL)')
            ->assertSee('Efisiensi Akumulasi 2 (SP)')
            ->assertSee('Total Nilai Efisiensi (Hemat)');
    }

    /**
     * Test FR-703: Dashboard renders division compliance analytics.
     */
    public function test_executive_dashboard_renders_division_compliance_analytics(): void
    {
        $year = 2026;
        $month = 11;

        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->obPosition->id,
            'office_location_id' => $this->office->id,
            'date' => "{$year}-{$month}-02",
            'check_in_at' => Carbon::parse("{$year}-{$month}-02 07:20:00", 'Asia/Jakarta'),
            'status' => Attendance::STATUS_ON_TIME,
            'shift_type' => Attendance::SHIFT_NORMAL,
        ]);

        Livewire::actingAs($this->atasanUser)
            ->test(ExecutiveGovernanceDashboard::class, [
                'year' => $year,
                'month' => $month,
            ])
            ->assertSee('Tingkat Kepatuhan Presensi Per Divisi')
            ->assertSee($this->obPosition->name)
            ->assertSee('100%');
    }

    /**
     * Test FR-703: Dashboard monitors active discipline sanctions and highlights SP 3 PHK.
     */
    public function test_executive_dashboard_monitors_active_sanctions_and_highlights_sp3(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 10:00:00', 'Asia/Jakarta'));

        // SP 3 sanction
        ViolationSanction::create([
            'employee_id' => $this->employee->id,
            'sanction_type' => ViolationSanction::TYPE_SP_3,
            'total_points_snapshot' => 85,
            'deduction_percentage' => 20.00,
            'duration_months' => 6,
            'start_date' => '2026-11-01',
            'end_date' => '2027-04-30',
            'issued_date' => '2026-11-01',
            'is_active' => true,
            'is_termination_recommended' => true,
        ]);

        Livewire::actingAs($this->atasanUser)
            ->test(ExecutiveGovernanceDashboard::class)
            ->assertSee($this->employee->full_name)
            ->assertSee('Surat Peringatan 3')
            ->assertSee('-20%')
            ->assertSee('Rekomendasi PHK');
    }

    /**
     * Test FR-703: Monitoring expiring contracts and processing extension via quick modal.
     */
    public function test_executive_can_monitor_expiring_contract_and_extend(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 10:00:00', 'Asia/Jakarta'));

        // Set contract ending in 20 days (H-20)
        $this->employee->update([
            'spk_number' => 'SPK/KUM/2025/001',
            'contract_start_date' => '2025-12-11',
            'contract_end_date' => '2026-12-10',
        ]);

        $test = Livewire::actingAs($this->atasanUser)
            ->test(ExecutiveGovernanceDashboard::class)
            ->assertSee($this->employee->full_name)
            ->assertSee('SPK/KUM/2025/001')
            ->assertSee('Perpanjang Kontrak')
            ->call('openExtendModal', $this->employee->id);

        $test->assertSet('extendingEmployeeId', $this->employee->id);

        // Process extension
        $test->set('newSpkNumber', 'SPK/KUM/2026/001')
            ->set('newStartDate', '2026-12-11')
            ->set('newEndDate', '2027-12-10')
            ->set('newSalary', 3200000.00)
            ->set('extendNotes', 'Perpanjangan kontrak tahun 2027')
            ->call('processExtendContract')
            ->assertNotified();

        $this->employee->refresh();
        $this->assertEquals('SPK/KUM/2026/001', $this->employee->spk_number);
        $this->assertEquals('2026-12-11', $this->employee->contract_start_date->toDateString());
        $this->assertEquals('2027-12-10', $this->employee->contract_end_date->toDateString());
        $this->assertEquals(3200000.00, (float) $this->employee->base_salary);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => Employee::class,
            'entity_id' => $this->employee->id,
            'action' => 'CONTRACT_EXTENDED',
        ]);
    }
}
