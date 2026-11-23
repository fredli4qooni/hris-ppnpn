<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageMonthlyClosing;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\MonthlyPeriodClosing;
use App\Models\User;
use App\Models\ViolationSanction;
use App\Services\LeaveRequestService;
use App\Services\MonthlyClosingService;
use Carbon\Carbon;
use Database\Seeders\SampleFiveEmployeesSeeder;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MonthlyClosingAndExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $atasanUser;

    protected User $supervisorUser;

    protected User $karyawanUser;

    protected Employee $employee;

    protected MonthlyClosingService $closingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->seed(SampleFiveEmployeesSeeder::class);

        $this->adminUser = User::where('username', 'admin')->first();
        $this->atasanUser = User::where('username', 'atasan')->first();
        $this->supervisorUser = User::where('username', 'supervisor')->first();
        $this->karyawanUser = User::where('username', 'ppnpn01')->first();
        $this->karyawanUser->update(['must_change_password' => false]);

        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->first();

        $this->closingService = app(MonthlyClosingService::class);
    }

    public function test_admin_and_atasan_can_access_manage_monthly_closing_page(): void
    {
        $this->actingAs($this->adminUser);
        $this->assertTrue(ManageMonthlyClosing::canAccess());

        $this->actingAs($this->atasanUser);
        $this->assertTrue(ManageMonthlyClosing::canAccess());

        $this->actingAs($this->karyawanUser);
        $this->assertFalse(ManageMonthlyClosing::canAccess());
    }

    public function test_payroll_summary_calculates_correct_dual_accumulations_and_totals(): void
    {
        $year = 2026;
        $month = 5;

        // 1. Create Attendance with TL 1 (0.5%) on May 4, 2026
        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->employee->position_id,
            'office_location_id' => 1,
            'date' => "{$year}-05-04",
            'check_in_at' => "{$year}-05-04 07:40:00",
            'check_out_at' => "{$year}-05-04 16:30:00",
            'status' => Attendance::STATUS_LATE_TL,
            'check_out_status' => Attendance::CHECK_OUT_ON_TIME,
            'late_minutes' => 40,
            'tl_category' => Attendance::TL_1,
            'deduction_percentage' => 0.50,
            'is_compensated' => false,
        ]);

        // 2. Create Attendance with TL 2 (1.0%) on May 5, 2026
        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->employee->position_id,
            'office_location_id' => 1,
            'date' => "{$year}-05-05",
            'check_in_at' => "{$year}-05-05 08:10:00",
            'check_out_at' => "{$year}-05-05 16:30:00",
            'status' => Attendance::STATUS_LATE_TL,
            'check_out_status' => Attendance::CHECK_OUT_ON_TIME,
            'late_minutes' => 70,
            'tl_category' => Attendance::TL_2,
            'deduction_percentage' => 1.00,
            'is_compensated' => false,
        ]);

        // Total Akumulasi 1 = 0.5% + 1.0% = 1.5%

        // 3. Create Active Sanction SP 1 (10% deduction) active in May 2026 (Akumulasi 2)
        ViolationSanction::create([
            'employee_id' => $this->employee->id,
            'sanction_type' => ViolationSanction::TYPE_SP_1,
            'total_points_snapshot' => 30,
            'deduction_percentage' => 10.00,
            'duration_months' => 1,
            'start_date' => "{$year}-05-01",
            'end_date' => "{$year}-05-31",
            'issued_date' => "{$year}-04-15",
            'is_active' => true,
            'is_termination_recommended' => false,
            'notes' => 'SP 1 bulan Mei 2026',
        ]);

        $data = $this->closingService->getPayrollData($year, $month);

        $this->assertEquals($year, $data['year']);
        $this->assertEquals($month, $data['month']);
        $this->assertFalse($data['is_locked']);

        // Find employee 1 in items
        $empItem = collect($data['items'])->firstWhere('employee_id', $this->employee->id);
        $this->assertNotNull($empItem);

        // Base salary = 3,200,000
        $this->assertEquals(3200000.00, $empItem['base_salary']);

        // Akumulasi 1 (TL 1.5% of 3,200,000 = 48,000)
        $this->assertEquals(1.50, $empItem['accumulation_1_tl_percentage']);
        $this->assertEquals(48000.00, $empItem['accumulation_1_tl_amount']);

        // Akumulasi 2 (SP 10.0% of 3,200,000 = 320,000)
        $this->assertEquals(10.00, $empItem['accumulation_2_sanction_percentage']);
        $this->assertEquals(320000.00, $empItem['accumulation_2_sanction_amount']);

        // Total Potongan = 48,000 + 320,000 = 368,000 (11.5%)
        $this->assertEquals(11.50, $empItem['total_deduction_percentage']);
        $this->assertEquals(368000.00, $empItem['total_deduction_amount']);

        // Net salary = 3,200,000 - 368,000 = 2,832,000
        $this->assertEquals(2832000.00, $empItem['net_salary']);

        // Check Grand Totals
        $this->assertGreaterThan(0, $data['totals']['total_base_salary']);
        $this->assertGreaterThanOrEqual(368000.00, $data['totals']['total_deductions_amount']);
    }

    public function test_admin_can_close_and_lock_period_with_snapshots_and_audit(): void
    {
        $year = 2026;
        $month = 6;

        $closing = $this->closingService->closePeriod(
            $year,
            $month,
            $this->adminUser,
            'Penutupan resmi bulan Juni 2026'
        );

        $this->assertInstanceOf(MonthlyPeriodClosing::class, $closing);
        $this->assertTrue($closing->is_locked);
        $this->assertEquals($this->adminUser->id, $closing->closed_by_admin_id);
        $this->assertEquals(5, $closing->total_employees_processed);
        $this->assertNotNull($closing->closed_at);

        // Check static helper
        $this->assertTrue(MonthlyPeriodClosing::isPeriodLocked($year, $month));

        // Check Audit Log
        $audit = AuditLog::where('action', 'MONTHLY_PERIOD_CLOSED')
            ->where('entity_id', $closing->id)
            ->first();
        $this->assertNotNull($audit);
        $this->assertEquals($this->adminUser->id, $audit->user_id);
    }

    public function test_cannot_close_already_locked_period(): void
    {
        $year = 2026;
        $month = 7;

        $this->closingService->closePeriod($year, $month, $this->adminUser);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Periode {$year}-{$month} sudah dalam status terkunci.");

        $this->closingService->closePeriod($year, $month, $this->adminUser);
    }

    public function test_locked_period_prevents_attendance_check_in_and_check_out(): void
    {
        $nowWib = Carbon::now('Asia/Jakarta');

        // Lock the current period
        $this->closingService->closePeriod($nowWib->year, $nowWib->month, $this->adminUser);
        $this->assertTrue(MonthlyPeriodClosing::isPeriodLocked($nowWib->year, $nowWib->month));

        $this->actingAs($this->karyawanUser);

        // Attempt check-in
        $response = $this->postJson(route('mobile.attendance.check-in'), [
            'position_id' => $this->employee->position_id,
            'latitude' => -5.3971400,
            'longitude' => 105.2667900,
            'liveness_verified' => true,
            'photo' => 'data:image/jpeg;base64,mock',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Periode absensi bulan ini telah dikunci (READ ONLY). Transaksi presensi tidak dapat diproses.',
            ]);

        // Attempt check-out
        $responseOut = $this->postJson(route('mobile.attendance.check-out'), [
            'latitude' => -5.3971400,
            'longitude' => 105.2667900,
            'liveness_verified' => true,
            'photo' => 'data:image/jpeg;base64,mock',
        ]);

        $responseOut->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Periode absensi bulan ini telah dikunci (READ ONLY). Transaksi presensi tidak dapat diproses.',
            ]);
    }

    public function test_locked_period_prevents_leave_request_submission_and_approval(): void
    {
        $year = 2026;
        $month = 8;

        $this->closingService->closePeriod($year, $month, $this->adminUser);

        $leaveService = app(LeaveRequestService::class);

        // Attempt to submit leave request in locked month
        $this->expectException(ValidationException::class);

        $leaveService->submit($this->employee, [
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => "{$year}-08-10",
            'end_date' => "{$year}-08-11",
            'reason' => 'Keperluan keluarga penting',
        ]);
    }

    public function test_super_admin_can_reopen_locked_period_with_mandatory_reason_and_audit(): void
    {
        $year = 2026;
        $month = 9;

        $closing = $this->closingService->closePeriod($year, $month, $this->adminUser);
        $this->assertTrue(MonthlyPeriodClosing::isPeriodLocked($year, $month));

        // Reopen by Super Admin
        $reopened = $this->closingService->reopenPeriod(
            $year,
            $month,
            $this->adminUser,
            'Koreksi absensi petugas security tanggal 15 September sesuai memo dinas'
        );

        $this->assertFalse($reopened->is_locked);
        $this->assertFalse(MonthlyPeriodClosing::isPeriodLocked($year, $month));
        $this->assertEquals($this->adminUser->id, $reopened->reopened_by_admin_id);
        $this->assertNotNull($reopened->reopened_at);
        $this->assertStringContainsString('Koreksi absensi', $reopened->reopen_reason);

        // Check Audit Log
        $audit = AuditLog::where('action', 'MONTHLY_PERIOD_REOPENED')
            ->where('entity_id', $closing->id)
            ->first();
        $this->assertNotNull($audit);
    }

    public function test_non_admin_cannot_reopen_locked_period(): void
    {
        $year = 2026;
        $month = 10;

        $this->closingService->closePeriod($year, $month, $this->adminUser);

        // Attempt reopen by supervisor
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Hanya Super Admin yang berwenang membuka kunci periode bulanan.');

        $this->closingService->reopenPeriod(
            $year,
            $month,
            $this->supervisorUser,
            'Mencoba buka kunci tanpa hak akses'
        );
    }

    public function test_export_excel_csv_generates_valid_csv_stream_with_utf8_bom(): void
    {
        $year = 2026;
        $month = 11;

        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.payroll.export-excel', [
            'year' => $year,
            'month' => $month,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString("Rekap_Honorarium_PPNPN_{$year}_{$month}.csv", (string) $response->headers->get('Content-Disposition'));

        // Validate stream contents
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Check UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // Check official headings
        $this->assertStringContainsString('KEMENTERIAN KEUANGAN REPUBLIK INDONESIA', $content);
        $this->assertStringContainsString('KANTOR WILAYAH PROVINSI LAMPUNG', $content);
        $this->assertStringContainsString('SPK-20/WPB.08/PPK/2025', $content);
        $this->assertStringContainsString('TOTAL KESELURUHAN', $content);
        $this->assertStringContainsString('Ahmad Subari', $content);
        $this->assertStringContainsString('PPNPN-2025-001', $content);
    }

    public function test_export_pdf_renders_official_kemenkeu_view_with_ppk_signature(): void
    {
        $year = 2026;
        $month = 11;

        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.payroll.export-pdf', [
            'year' => $year,
            'month' => $month,
        ]));

        $response->assertOk();
        $response->assertViewIs('exports.payroll-report-pdf');
        $response->assertSee('KEMENTERIAN KEUANGAN REPUBLIK INDONESIA');
        $response->assertSee('DIREKTORAT JENDERAL PERBENDAHARAAN');
        $response->assertSee('KANTOR WILAYAH PROVINSI LAMPUNG');
        $response->assertSee('DAFTAR REKAPITULASI KEHADIRAN DAN PEMOTONGAN HONORARIUM PPNPN');
        $response->assertSee('SPK-20/WPB.08/PPK/2025');
        $response->assertSee('Pejabat Pembuat Komitmen (PPK)');
        $response->assertSee('Bandar Lampung');
        $response->assertSee('MOHAMAD AGUS PRIYANTO');
    }

    public function test_karyawan_cannot_access_export_endpoints(): void
    {
        $this->actingAs($this->karyawanUser);

        $responseExcel = $this->get(route('admin.payroll.export-excel', ['year' => 2026, 'month' => 11]));
        $responseExcel->assertForbidden();

        $responsePdf = $this->get(route('admin.payroll.export-pdf', ['year' => 2026, 'month' => 11]));
        $responsePdf->assertForbidden();
    }
}
