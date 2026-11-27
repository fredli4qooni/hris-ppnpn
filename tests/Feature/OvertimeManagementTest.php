<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSetting;
use App\Models\Position;
use App\Models\User;
use App\Notifications\OvertimeStatusChangedNotification;
use App\Services\MonthlyClosingService;
use App\Services\OvertimePdfImportService;
use App\Services\OvertimeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OvertimeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $masYudha;

    protected User $pakDedi;

    protected User $employeeUser;

    protected Employee $employee;

    protected Employee $secondEmployee;

    protected OvertimeService $overtimeService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->masYudha = User::where('username', 'masyudha')->firstOrFail();
        $this->pakDedi = User::where('username', 'pakdedi')->firstOrFail();
        $this->employeeUser = User::where('username', 'ppnpn01')->firstOrFail();
        $this->employeeUser->update(['must_change_password' => false]);

        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->firstOrFail();

        $secondUser = User::factory()->create([
            'name' => 'Pegawai Kedua',
            'username' => 'ppnpn02',
            'must_change_password' => false,
        ]);
        $secondUser->assignRole('karyawan');

        $position = Position::firstOrFail();
        $this->secondEmployee = Employee::create([
            'user_id' => $secondUser->id,
            'employee_code' => 'PPNPN-2025-002',
            'full_name' => 'Budi Santoso',
            'position_id' => $position->id,
            'base_salary' => 3000000,
            'leave_balance' => 12,
            'is_active' => true,
        ]);

        $this->overtimeService = app(OvertimeService::class);
    }

    public function test_overtime_duration_calculation_supports_fractional_hours_and_rejects_invalid_range(): void
    {
        $result = $this->overtimeService->calculateTimeDuration('17:00', '18:30');

        $this->assertSame(90, $result['duration_minutes']);
        $this->assertSame(1.5, $result['duration_hours']);

        $this->expectException(ValidationException::class);
        $this->overtimeService->calculateTimeDuration('19:00', '18:00');
    }

    public function test_backdated_and_same_day_without_letter_deadline_validation(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 27, 15, 0, 0, 'Asia/Jakarta'));

        try {
            // 1. Reject past date
            try {
                $this->overtimeService->createRequest($this->masYudha, [
                    'request_type' => OvertimeRequest::TYPE_WITH_LETTER,
                    'start_date' => '2026-11-26',
                    'end_date' => '2026-11-26',
                    'items' => [[
                        'employee_id' => $this->employee->id,
                        'start_time' => '17:00',
                        'end_time' => '19:00',
                        'description' => 'Rekonsiliasi laporan keuangan',
                    ]],
                ]);
                $this->fail('Expected ValidationException for past start_date.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('start_date', $e->errors());
            }

            // 2. Reject WITHOUT_LETTER on Day H if start_time already passed (now is 15:00)
            try {
                $this->overtimeService->createRequest($this->employeeUser, [
                    'request_type' => OvertimeRequest::TYPE_WITHOUT_LETTER,
                    'start_date' => '2026-11-27',
                    'end_date' => '2026-11-27',
                    'items' => [[
                        'employee_id' => $this->employee->id,
                        'start_time' => '14:30',
                        'end_time' => '16:30',
                        'description' => 'Pengarsipan dokumen mendadak',
                    ]],
                ]);
                $this->fail('Expected ValidationException when WITHOUT_LETTER start_time already passed on Day H.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('start_time', $e->errors());
            }

            // 3. Allow WITHOUT_LETTER on Day H before start_time (now is 15:00, start is 17:00)
            $created = $this->overtimeService->createRequest($this->employeeUser, [
                'request_type' => OvertimeRequest::TYPE_WITHOUT_LETTER,
                'start_date' => '2026-11-27',
                'end_date' => '2026-11-27',
                'items' => [[
                    'employee_id' => $this->employee->id,
                    'start_time' => '17:00',
                    'end_time' => '19:00',
                    'description' => 'Penyusunan berkas pertanggungjawaban',
                ]],
            ]);

            $this->assertSame(OvertimeRequest::TYPE_WITHOUT_LETTER, $created->request_type);
            $this->assertCount(1, $created->items);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_duplicate_employee_on_overlapping_date_is_blocked_while_multiple_employees_in_one_sptpl_allowed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 27, 9, 0, 0, 'Asia/Jakarta'));

        try {
            // One SPTPL covering 2 days with 2 distinct employees and >4 hours daily (no cap enforced)
            $sptpl = $this->overtimeService->createRequest($this->masYudha, [
                'request_type' => OvertimeRequest::TYPE_WITH_LETTER,
                'start_date' => '2026-11-28',
                'end_date' => '2026-11-29',
                'items' => [
                    [
                        'employee_id' => $this->employee->id,
                        'start_time' => '08:00',
                        'end_time' => '18:00', // 10 hours (no max hour limit)
                        'description' => 'Pengamanan & pemeliharaan gedung akhir pekan',
                    ],
                    [
                        'employee_id' => $this->secondEmployee->id,
                        'start_time' => '08:30',
                        'end_time' => '17:00', // 8.5 hours
                        'description' => 'Dukungan kegiatan rapat koordinasi',
                    ],
                ],
            ]);

            $this->assertSame(2, $sptpl->getDayCount());
            $this->assertCount(2, $sptpl->items);
            $this->assertSame(600, $sptpl->items->firstWhere('employee_id', $this->employee->id)->duration_minutes);

            // Second request on overlapping date (2026-11-29) for same employee must be blocked
            try {
                $this->overtimeService->createRequest($this->masYudha, [
                    'request_type' => OvertimeRequest::TYPE_WITH_LETTER,
                    'start_date' => '2026-11-29',
                    'end_date' => '2026-11-29',
                    'items' => [[
                        'employee_id' => $this->employee->id,
                        'start_time' => '13:00',
                        'end_time' => '15:00',
                        'description' => 'Lembur tambahan bentrok tanggal',
                    ]],
                ]);
                $this->fail('Expected duplicate employee validation error.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('items', $e->errors());
            }

            // Cancelling the first request frees the slot
            $this->overtimeService->cancel($sptpl, $this->masYudha, 'Agenda ditunda');

            $replacement = $this->overtimeService->createRequest($this->masYudha, [
                'request_type' => OvertimeRequest::TYPE_WITH_LETTER,
                'start_date' => '2026-11-29',
                'end_date' => '2026-11-29',
                'items' => [[
                    'employee_id' => $this->employee->id,
                    'start_time' => '13:00',
                    'end_time' => '15:00',
                    'description' => 'Pengganti agenda lembur',
                ]],
            ]);

            $this->assertNotNull($replacement->id);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_sptpl_approval_generates_sequential_number_and_sends_non_blocking_spkl_reminder(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 27, 10, 0, 0, 'Asia/Jakarta'));

        try {
            $request = $this->overtimeService->createRequest($this->masYudha, [
                'request_type' => OvertimeRequest::TYPE_WITH_LETTER,
                'start_date' => '2026-11-28',
                'end_date' => '2026-11-28',
                'items' => [[
                    'employee_id' => $this->employee->id,
                    'start_time' => '09:00',
                    'end_time' => '12:30',
                    'description' => 'Penyiapan bahan evaluasi triwulanan',
                ]],
            ]);

            // Submit without SPKL issued -> succeeds with reminder warning
            $submitResult = $this->overtimeService->submit($request, $this->masYudha);
            $this->assertNotNull($submitResult['spkl_warning']);
            $this->assertStringContainsString('SPKL', $submitResult['spkl_warning']);

            // Approve as Pak Dedi -> succeeds with reminder warning and locks SPTPL number
            $approveResult = $this->overtimeService->approve($request->fresh(), $this->pakDedi, 'Silakan laksanakan sesuai tugas.');
            $approved = $approveResult['request'];

            $this->assertSame(OvertimeRequest::STATUS_APPROVED, $approved->status);
            $this->assertSame(1, $approved->sequence_number);
            $this->assertSame(sprintf('SPTPL-1/%s/2026', OvertimeSetting::current()->unit_code), $approved->letter_number);
            $this->assertNotNull($approveResult['spkl_warning']);

            // Proposer Mas Yudha receives DB notification containing SPKL reminder
            $this->assertDatabaseHas('notifications', [
                'notifiable_id' => $this->masYudha->id,
                'notifiable_type' => User::class,
                'type' => OvertimeStatusChangedNotification::class,
            ]);

            // Second SPTPL gets sequence_number 2
            $secondRequest = $this->overtimeService->createRequest($this->masYudha, [
                'request_type' => OvertimeRequest::TYPE_WITH_LETTER,
                'start_date' => '2026-11-30',
                'end_date' => '2026-11-30',
                'items' => [[
                    'employee_id' => $this->secondEmployee->id,
                    'start_time' => '17:00',
                    'end_time' => '19:30',
                    'description' => 'Penyusunan laporan akhir bulan',
                ]],
            ]);
            $this->overtimeService->submit($secondRequest, $this->masYudha);
            $secondApproved = $this->overtimeService->approve($secondRequest->fresh(), $this->pakDedi)['request'];
            $this->assertSame(2, $secondApproved->sequence_number);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_pdf_import_service_and_mobile_parse_pdf_endpoint_extract_fields(): void
    {
        $pdfContent = "BT\n"
            ."/F1 12 Tf\n"
            ."72 720 Td\n"
            ."(Tanggal: 28 November 2026 s.d. 29 November 2026) Tj\n"
            ."0 -20 Td\n"
            ."(Waktu: 17:00 - 19:30 WIB) Tj\n"
            ."0 -20 Td\n"
            ."(Uraian: Rekonsiliasi data kontrak dan arsip keuangan) Tj\n"
            .'ET';

        $pdfBinary = "%PDF-1.4\n1 0 obj << /Length ".strlen($pdfContent)." >>\nstream\n{$pdfContent}\nendstream\nendobj\n%%EOF";

        $parsed = app(OvertimePdfImportService::class)->parsePdfBinary($pdfBinary);

        $this->assertTrue($parsed['has_extractable_text']);
        $this->assertSame('2026-11-28', $parsed['start_date']);
        $this->assertSame('2026-11-29', $parsed['end_date']);
        $this->assertSame('17:00', $parsed['start_time']);
        $this->assertSame('19:30', $parsed['end_time']);
        $this->assertStringContainsString('Rekonsiliasi data kontrak', (string) $parsed['description']);

        $uploadedFile = UploadedFile::fake()->createWithContent('surat_lembur.pdf', $pdfBinary);

        $response = $this->actingAs($this->employeeUser)
            ->postJson(route('mobile.overtime.parse-pdf'), [
                'pdf_file' => $uploadedFile,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.start_date', '2026-11-28')
            ->assertJsonPath('data.end_date', '2026-11-29')
            ->assertJsonPath('data.start_time', '17:00')
            ->assertJsonPath('data.end_time', '19:30');
    }

    public function test_monthly_closing_displays_overtime_letter_employees_and_jenis_b_attendance_without_tariff(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 27, 9, 0, 0, 'Asia/Jakarta'));

        try {
            // Approved SPTPL for employee 1
            $req = $this->overtimeService->createRequest($this->masYudha, [
                'request_type' => OvertimeRequest::TYPE_WITH_LETTER,
                'start_date' => '2026-11-27',
                'end_date' => '2026-11-27',
                'items' => [[
                    'employee_id' => $this->employee->id,
                    'start_time' => '17:00',
                    'end_time' => '19:00',
                    'description' => 'Finalisasi dokumen SAKTI',
                ]],
            ]);
            $this->overtimeService->submit($req, $this->masYudha);
            $this->overtimeService->approve($req->fresh(), $this->pakDedi);

            // Attendance checkout exceeding Position::default_end_time on a different workday for employee 2 (Jenis B)
            $posEnd = $this->secondEmployee->position->default_end_time ?? '17:00:00';
            $checkOut = Carbon::parse('2026-11-26 '.$posEnd, 'Asia/Jakarta')->addMinutes(90);

            Attendance::create([
                'employee_id' => $this->secondEmployee->id,
                'position_id' => $this->secondEmployee->position_id,
                'date' => '2026-11-26',
                'check_in_at' => Carbon::parse('2026-11-26 07:30:00', 'Asia/Jakarta'),
                'check_out_at' => $checkOut,
                'check_in_status' => 'TEPAT_WAKTU',
                'late_minutes' => 0,
                'early_leave_minutes' => 0,
                'is_valid_location' => true,
            ]);

            $payrollData = app(MonthlyClosingService::class)->getPayrollData(2026, 11);

            $this->assertArrayHasKey('overtime_letter_employees', $payrollData);
            $this->assertCount(1, $payrollData['overtime_letter_employees']);
            $this->assertSame($this->employee->full_name, $payrollData['overtime_letter_employees'][0]['name']);
            $this->assertSame(2.0, (float) $payrollData['overtime_letter_employees'][0]['hours_with_letter']);

            $emp1Row = collect($payrollData['items'])->firstWhere('employee_id', $this->employee->id);
            $emp2Row = collect($payrollData['items'])->firstWhere('employee_id', $this->secondEmployee->id);

            $this->assertTrue($emp1Row['has_overtime_letter']);
            $this->assertSame(2.0, (float) $emp1Row['overtime_hours_with_letter']);
            $this->assertFalse($emp2Row['has_overtime_letter']);
            $this->assertSame(1.5, (float) $emp2Row['overtime_hours_without_letter']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_mobile_and_backoffice_overtime_endpoints_and_exports_work_without_tariff_display(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 27, 10, 0, 0, 'Asia/Jakarta'));

        try {
            // Mobile home displays overtime shortcut
            $homeResponse = $this->actingAs($this->employeeUser)->get(route('mobile.home'));
            $homeResponse->assertOk()
                ->assertSee('Pengajuan Lembur')
                ->assertDontSee('Rp ');

            // Employee views overtime list & submits WITHOUT_LETTER request
            $this->actingAs($this->employeeUser)
                ->get(route('mobile.overtime.index'))
                ->assertOk()
                ->assertDontSee('Rp ');

            $this->actingAs($this->employeeUser)
                ->get(route('mobile.overtime.create'))
                ->assertOk()
                ->assertSee('Baca Isi PDF')
                ->assertDontSee('Rp ');

            $storeResponse = $this->actingAs($this->employeeUser)
                ->post(route('mobile.overtime.store'), [
                    'start_date' => '2026-11-28',
                    'end_date' => '2026-11-28',
                    'start_time' => '17:00',
                    'end_time' => '18:30',
                    'description' => 'Rekapitulasi data BMN bulanan',
                ]);

            $storeResponse->assertRedirect(route('mobile.overtime.index'));
            $created = OvertimeRequest::where('proposer_id', $this->employeeUser->id)->firstOrFail();
            $this->assertSame(OvertimeRequest::STATUS_SUBMITTED, $created->status);

            $this->actingAs($this->employeeUser)
                ->get(route('mobile.overtime.show', $created))
                ->assertOk()
                ->assertSee('Rekapitulasi data BMN bulanan')
                ->assertDontSee('Rp ');

            // Karyawan forbidden on admin overtime exports and Filament panel
            $this->actingAs($this->employeeUser)
                ->get(route('admin.overtime.recap.excel', ['year' => 2026, 'month' => 11]))
                ->assertForbidden();

            // Mas Yudha & Pak Dedi can access Filament overtime resources & exports
            $this->actingAs($this->masYudha)->get('/admin/overtime-requests')->assertOk();
            $this->actingAs($this->masYudha)->get('/admin/overtime-master-orders')->assertOk();
            $this->actingAs($this->masYudha)->get('/admin/holidays')->assertOk();
            $this->actingAs($this->masYudha)->get('/admin/overtime-recap-page')->assertOk()->assertDontSee('Tarif')->assertDontSee('Rp ');

            $spkl = $this->overtimeService->getOrCreateMonthlyMasterOrder(2026, 11, $this->masYudha);
            $this->overtimeService->issueMasterOrder($spkl, $this->pakDedi);

            $this->actingAs($this->pakDedi)
                ->get(route('admin.overtime.spkl.print', $spkl))
                ->assertOk()
                ->assertSee($spkl->letter_number)
                ->assertDontSee('Tarif');

            $this->actingAs($this->pakDedi)
                ->get(route('admin.overtime.recap.pdf', ['year' => 2026, 'month' => 11]))
                ->assertOk()
                ->assertDontSee('Tarif')
                ->assertDontSee('Rp ');

            $excelResp = $this->actingAs($this->pakDedi)
                ->get(route('admin.overtime.recap.excel', ['year' => 2026, 'month' => 11]));
            $excelResp->assertOk();
            $this->assertStringContainsString('text/csv', (string) $excelResp->headers->get('content-type'));
        } finally {
            Carbon::setTestNow();
        }
    }
}
