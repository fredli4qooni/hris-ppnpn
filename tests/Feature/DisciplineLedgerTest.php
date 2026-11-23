<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeViolation;
use App\Models\User;
use App\Models\ViolationCatalog;
use App\Models\ViolationSanction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisciplineLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected User $employeeUser;

    protected Employee $employee;

    protected User $supervisorUser;

    protected ViolationCatalog $catalogItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->firstOrFail();
        $this->employeeUser = $this->employee->user;
        $this->employeeUser->update(['must_change_password' => false]);

        $this->supervisorUser = User::where('username', 'supervisor')->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'supervisor'))->firstOrFail();

        $this->catalogItem = ViolationCatalog::firstOrFail();
    }

    /**
     * Test FR-409: Guest is redirected to login when accessing discipline ledger.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('mobile.discipline.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test FR-409: Authenticated employee can view personal discipline ledger.
     */
    public function test_authenticated_employee_can_view_discipline_ledger(): void
    {
        $response = $this->actingAs($this->employeeUser)
            ->get(route('mobile.discipline.index'));

        $response->assertStatus(200);
        $response->assertSee('Buku Catatan Disiplin', false);
        $response->assertSee('Saldo Poin Pelanggaran Aktif');
        $response->assertSee('Akumulasi 2 (SPK)');
        $response->assertSee('Tidak Ada Sanksi Berjalan');
    }

    /**
     * Test FR-409: Ledger accurately renders recorded violations with point weights and supervisor name.
     */
    public function test_ledger_displays_recorded_violations_and_points(): void
    {
        EmployeeViolation::create([
            'employee_id' => $this->employee->id,
            'violation_catalog_id' => $this->catalogItem->id,
            'supervisor_id' => $this->supervisorUser->id,
            'incident_date' => Carbon::parse('2026-11-15'),
            'notes' => 'Meninggalkan pos tugas tanpa izin lisan supervisor',
            'points' => 25,
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->employeeUser)
            ->get(route('mobile.discipline.index'));

        $response->assertStatus(200);
        $response->assertSee('25');
        $response->assertSee('+25 Poin');
        $response->assertSee($this->catalogItem->clause_number);
        $response->assertSee($this->catalogItem->name);
        $response->assertSee($this->supervisorUser->name);
        $response->assertSee('Meninggalkan pos tugas tanpa izin lisan supervisor');
    }

    /**
     * Test FR-307: Ledger accurately renders active multi-month sanction deduction schedule and isolation notice.
     */
    public function test_ledger_displays_active_sanction_and_deduction_schedule(): void
    {
        ViolationSanction::create([
            'employee_id' => $this->employee->id,
            'sanction_type' => ViolationSanction::TYPE_SP_2_A,
            'total_points_snapshot' => 50,
            'deduction_percentage' => 5.00,
            'duration_months' => 3,
            'start_date' => Carbon::parse('2026-12-01'),
            'end_date' => Carbon::parse('2027-02-28'),
            'issued_date' => Carbon::parse('2026-11-20'),
            'is_termination_recommended' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->employeeUser)
            ->get(route('mobile.discipline.index'));

        $response->assertStatus(200);
        $response->assertSee('Surat Peringatan 2 Tingkat A (SP 2A)');
        $response->assertSee('5%');
        $response->assertSee('3 Bulan');
        $response->assertSee('Pemisahan Tegas Akumulasi');
        $response->assertSee('Akumulasi 2');
    }

    /**
     * Test FR-409: Ledger includes button to access official Berita Acara Pembinaan (BAP).
     */
    public function test_ledger_contains_link_to_berita_acara_pembinaan(): void
    {
        $response = $this->actingAs($this->employeeUser)
            ->get(route('mobile.discipline.index'));

        $response->assertStatus(200);
        $response->assertSee(route('admin.employees.discipline-summary', $this->employee));
        $response->assertSee('Unduh Berita Acara Rekam Jejak (BAP)');
    }

    /**
     * Test FR-409: Home and Profile pages contain links to the discipline ledger.
     */
    public function test_home_and_profile_have_links_to_discipline_ledger(): void
    {
        $homeResponse = $this->actingAs($this->employeeUser)->get(route('mobile.home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee(route('mobile.discipline.index'));
        $homeResponse->assertSee('Buku Disiplin', false);

        $profileResponse = $this->actingAs($this->employeeUser)->get(route('mobile.profile'));
        $profileResponse->assertStatus(200);
        $profileResponse->assertSee(route('mobile.discipline.index'));
        $profileResponse->assertSee('Buku Catatan Disiplin', false);
    }
}
