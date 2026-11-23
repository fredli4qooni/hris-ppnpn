<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LateException;
use App\Models\Position;
use App\Models\User;
use App\Services\LateExceptionService;
use App\Services\MonthlyClosingService;
use Carbon\Carbon;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LateExceptionDispensationTest extends TestCase
{
    use RefreshDatabase;

    protected User $karyawanUser;

    protected User $supervisorUser;

    protected User $atasanUser;

    protected Employee $employee;

    protected Position $obPosition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Storage::fake('public');

        $this->karyawanUser = User::where('username', 'ppnpn01')->firstOrFail();
        $this->karyawanUser->update(['must_change_password' => false]);

        $this->supervisorUser = User::where('username', 'supervisor')->first() ?? User::whereHas('roles', fn ($q) => $q->where('name', 'supervisor'))->firstOrFail();
        $this->atasanUser = User::where('username', 'atasan')->first() ?? User::whereHas('roles', fn ($q) => $q->where('name', 'atasan'))->firstOrFail();

        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->firstOrFail();
        $this->obPosition = Position::where('code', 'OB')->firstOrFail();
    }

    /**
     * Test FR-303: Employee can submit force majeure dispensation with mandatory proof file.
     */
    public function test_employee_can_submit_force_majeure_dispensation_with_proof(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 10:00:00', 'Asia/Jakarta'));

        $proofFile = UploadedFile::fake()->create('surat_keterangan_laka.pdf', 200, 'application/pdf');

        $response = $this->actingAs($this->karyawanUser)
            ->post('/mobile/late-exceptions', [
                'incident_date' => '2026-11-23',
                'category' => LateException::CATEGORY_ACCIDENT,
                'incident_time' => '06:50',
                'description' => 'Terjadi kecelakaan lalu lintas tabrakan beruntun di jalan bypass Soekarno Hatta yang menghalangi seluruh lajur kendaraan.',
                'proof_file' => $proofFile,
            ]);

        $response->assertRedirect('/mobile/late-exceptions');

        $this->assertDatabaseHas('late_exceptions', [
            'employee_id' => $this->employee->id,
            'category' => LateException::CATEGORY_ACCIDENT,
            'status' => LateException::STATUS_PENDING,
        ]);

        $created = LateException::where('employee_id', $this->employee->id)->latest('id')->first();
        $this->assertNotNull($created);
        $this->assertNotNull($created->proof_file_path);

        /** @var FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');
        $publicDisk->assertExists($created->proof_file_path);
    }

    /**
     * Test FR-303: Submission is rejected if date is in the future.
     */
    public function test_employee_cannot_submit_dispensation_for_future_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 10:00:00', 'Asia/Jakarta'));

        $proofFile = UploadedFile::fake()->create('foto_banjir.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($this->karyawanUser)
            ->from('/mobile/late-exceptions/create')
            ->post('/mobile/late-exceptions', [
                'incident_date' => '2026-11-24', // Tomorrow (future)
                'category' => LateException::CATEGORY_DISASTER,
                'description' => 'Bencana alam banjir bandang sebelum hari kejadian.',
                'proof_file' => $proofFile,
            ]);

        $response->assertSessionHasErrors(['incident_date']);

        $this->assertDatabaseMissing('late_exceptions', [
            'employee_id' => $this->employee->id,
            'category' => LateException::CATEGORY_DISASTER,
        ]);
    }

    /**
     * Test FR-303: Submission is rejected if proof file is missing.
     */
    public function test_employee_cannot_submit_dispensation_without_proof_file(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 10:00:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawanUser)
            ->from('/mobile/late-exceptions/create')
            ->post('/mobile/late-exceptions', [
                'incident_date' => '2026-11-23',
                'category' => LateException::CATEGORY_FAMILY_BEREAVEMENT,
                'description' => 'Musibah keluarga orang tua sakit kritis gawat darurat.',
            ]);

        $response->assertSessionHasErrors(['proof_file']);
    }

    /**
     * Test FR-303: Cannot submit duplicate pending dispensation for the same date.
     */
    public function test_employee_cannot_submit_duplicate_pending_dispensation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 10:00:00', 'Asia/Jakarta'));

        LateException::create([
            'employee_id' => $this->employee->id,
            'incident_date' => '2026-11-23',
            'category' => LateException::CATEGORY_DISASTER,
            'description' => 'Banjir besar di area perumahan.',
            'status' => LateException::STATUS_PENDING,
            'proof_file_path' => 'late_exceptions/proofs/test.jpg',
        ]);

        $proofFile = UploadedFile::fake()->create('bukti2.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($this->karyawanUser)
            ->from('/mobile/late-exceptions/create')
            ->post('/mobile/late-exceptions', [
                'incident_date' => '2026-11-23',
                'category' => LateException::CATEGORY_DISASTER,
                'description' => 'Pengajuan kedua di tanggal yang sama.',
                'proof_file' => $proofFile,
            ]);

        $response->assertSessionHasErrors(['incident_date']);
    }

    /**
     * Test FR-303: Stage 1 Supervisor can review and approve dispensation to Tier 2.
     */
    public function test_supervisor_review_tier_1(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 11:00:00', 'Asia/Jakarta'));

        /** @var LateExceptionService $service */
        $service = app(LateExceptionService::class);

        $exception = LateException::create([
            'employee_id' => $this->employee->id,
            'incident_date' => '2026-11-23',
            'category' => LateException::CATEGORY_ACCIDENT,
            'description' => 'Insiden kecelakaan lalu lintas pagi hari.',
            'status' => LateException::STATUS_PENDING,
            'proof_file_path' => 'late_exceptions/proofs/test.jpg',
        ]);

        $service->reviewBySupervisor(
            $exception,
            $this->supervisorUser,
            true,
            'Fakta lapangan diverifikasi benar terdapat kemacetan total akibat kecelakaan.'
        );

        $exception->refresh();
        $this->assertEquals(LateException::STATUS_APPROVED_SUPERVISOR, $exception->status);
        $this->assertEquals($this->supervisorUser->id, $exception->supervisor_id);
    }

    /**
     * Test FR-303: Stage 2 Atasan decides and exempts TL deduction on Attendance record.
     */
    public function test_atasan_decision_tier_2_exempts_attendance_tl_deduction(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 14:00:00', 'Asia/Jakarta'));

        /** @var LateExceptionService $service */
        $service = app(LateExceptionService::class);

        // Attendance recorded as TL_2 (1.25% deduction)
        $attendance = Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->obPosition->id,
            'date' => '2026-11-23',
            'check_in_at' => Carbon::parse('2026-11-23 07:45:00', 'Asia/Jakarta'),
            'status' => Attendance::STATUS_LATE_TL,
            'late_minutes' => 45,
            'tl_category' => Attendance::TL_2,
            'deduction_percentage' => 1.25,
            'shift_type' => Attendance::SHIFT_NORMAL,
            'is_dispensation' => false,
            'tl_deduction_exempted' => false,
        ]);

        $exception = LateException::create([
            'employee_id' => $this->employee->id,
            'attendance_id' => $attendance->id,
            'incident_date' => '2026-11-23',
            'category' => LateException::CATEGORY_DISASTER,
            'description' => 'Jalur utama tergenang banjir luapan sungai sedalam 60cm.',
            'status' => LateException::STATUS_APPROVED_SUPERVISOR,
            'proof_file_path' => 'late_exceptions/proofs/test.jpg',
            'supervisor_id' => $this->supervisorUser->id,
            'supervisor_reviewed_at' => now(),
            'supervisor_notes' => 'Diverifikasi benar.',
        ]);

        // Atasan grants final approval
        $service->decideByAtasan(
            $exception,
            $this->atasanUser,
            true,
            'Disetujui pembebasan sanksi keterlambatan karena kondisi kahar bencana banjir.'
        );

        $exception->refresh();
        $this->assertEquals(LateException::STATUS_APPROVED, $exception->status);
        $this->assertEquals($this->atasanUser->id, $exception->atasan_id);

        $attendance->refresh();
        $this->assertTrue($attendance->is_dispensation);
        $this->assertTrue($attendance->tl_deduction_exempted);
        $this->assertStringContainsString('Dispensasi Kahar Disetujui', $attendance->dispensation_notes);
    }

    /**
     * Test FR-303: Monthly closing service automatically annuls TL deduction when exempted.
     */
    public function test_monthly_closing_payroll_calculates_zero_tl_deduction_for_exempted_attendance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 15:00:00', 'Asia/Jakarta'));

        // Attendance has 1.25% TL deduction, BUT is exempted due to approved force majeure
        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->obPosition->id,
            'date' => '2026-11-23',
            'check_in_at' => Carbon::parse('2026-11-23 07:45:00', 'Asia/Jakarta'),
            'status' => Attendance::STATUS_LATE_TL,
            'late_minutes' => 45,
            'tl_category' => Attendance::TL_2,
            'deduction_percentage' => 1.25,
            'shift_type' => Attendance::SHIFT_NORMAL,
            'is_dispensation' => true,
            'tl_deduction_exempted' => true,
            'dispensation_notes' => 'Dispensasi Kahar Disetujui',
        ]);

        /** @var MonthlyClosingService $closingService */
        $closingService = app(MonthlyClosingService::class);
        $payroll = $closingService->getPayrollData(2026, 11);

        $employeeRow = collect($payroll['items'])->firstWhere('employee_id', $this->employee->id);
        $this->assertNotNull($employeeRow);

        // Akumulasi 1 (TL) deduction must be Rp 0 (exempted)
        $this->assertEquals(0.00, $employeeRow['accumulation_1_tl_amount']);
        $this->assertEquals(0.00, $employeeRow['accumulation_1_tl_percentage']);
    }
}
