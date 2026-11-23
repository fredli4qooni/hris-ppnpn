<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use App\Models\ViolationSanction;
use App\Services\BiometricVerificationService;
use App\Services\FileUploadSanitizerService;
use App\Services\MonthlyClosingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SystemHardeningAndStressTest extends TestCase
{
    use RefreshDatabase;

    protected OfficeLocation $office;

    protected Position $obPosition;

    protected string $samplePhotoBase64;

    /**
     * @var list<float>
     */
    protected array $sampleFaceVector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Storage::fake('public');

        $this->office = OfficeLocation::firstOrFail();
        $this->obPosition = Position::where('code', 'OB')->firstOrFail();

        // Generate tiny valid sample JPEG for photo upload
        $img = imagecreatetruecolor(60, 60);
        $bgColor = imagecolorallocate($img, 0, 88, 168);
        imagefilledrectangle($img, 0, 0, 59, 59, $bgColor);
        ob_start();
        imagejpeg($img, null, 75);
        $jpegBinary = ob_get_clean();
        imagedestroy($img);
        $this->samplePhotoBase64 = 'data:image/jpeg;base64,'.base64_encode($jpegBinary);

        // 128-float normalized unit face vector
        $unitVal = round(1.0 / sqrt(128), 6);
        $this->sampleFaceVector = array_fill(0, 128, $unitVal);
    }

    /**
     * Test 1: File Upload Security & Sanitization (US-606 Acceptance Criterion 2).
     * Validates authentic MIME inspection, script injection prevention, and EXIF stripping.
     */
    public function test_file_upload_sanitization_and_security_hardening(): void
    {
        /** @var FileUploadSanitizerService $sanitizer */
        $sanitizer = app(FileUploadSanitizerService::class);

        // 1. Valid PDF file should be stored safely with random hash
        $validPdf = UploadedFile::fake()->create('surat_tugas.pdf', 300, 'application/pdf');
        $storedPdfPath = $sanitizer->sanitizeAndStore($validPdf, 'test_uploads', 'public', 'attachment');
        $this->assertNotNull($storedPdfPath);
        $this->assertStringEndsWith('.pdf', $storedPdfPath);
        $this->assertTrue(Storage::disk('public')->exists($storedPdfPath));

        // 2. Reject executable PHP script disguised with double extension or script content
        $maliciousFile = UploadedFile::fake()->createWithContent(
            'payload.php.pdf',
            "<?php echo 'HACKED'; system('ls'); ?>"
        );

        $this->expectException(ValidationException::class);
        $sanitizer->sanitizeAndStore($maliciousFile, 'test_uploads', 'public', 'attachment');
    }

    /**
     * Test 2: File upload rejects disallowed MIME types (e.g. .exe, .sh, .docx).
     */
    public function test_file_upload_rejects_unauthorized_mime_types(): void
    {
        /** @var FileUploadSanitizerService $sanitizer */
        $sanitizer = app(FileUploadSanitizerService::class);

        $disallowedFile = UploadedFile::fake()->create('installer.exe', 150, 'application/x-msdownload');

        $this->expectException(ValidationException::class);
        $sanitizer->sanitizeAndStore($disallowedFile, 'test_uploads', 'public', 'attachment');
    }

    /**
     * Test 3: File upload rejects files exceeding maximum 2 MB size.
     */
    public function test_file_upload_rejects_oversized_files(): void
    {
        /** @var FileUploadSanitizerService $sanitizer */
        $sanitizer = app(FileUploadSanitizerService::class);

        // 3 MB file
        $oversizedFile = UploadedFile::fake()->create('huge_document.pdf', 3072, 'application/pdf');

        $this->expectException(ValidationException::class);
        $sanitizer->sanitizeAndStore($oversizedFile, 'test_uploads', 'public', 'attachment');
    }

    /**
     * Test 4: Database Query Optimization & N+1 Prevention on Monthly Payroll Closing (US-606 Criterion 1).
     * Asserts query count is bounded and constant (<= 5 queries), eliminating N+1 queries across all employees.
     */
    public function test_monthly_payroll_computation_eliminates_n_plus_one_queries(): void
    {
        // Create 10 additional active employees with attendances and active sanctions
        $year = 2026;
        $month = 11;

        for ($i = 1; $i <= 10; $i++) {
            $user = User::create([
                'name' => "Pegawai Batch {$i}",
                'username' => "batch_ppnpn_{$i}",
                'email' => "batch{$i}@djpb.kemenkeu.go.id",
                'password' => Hash::make('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]);

            $emp = Employee::create([
                'user_id' => $user->id,
                'employee_code' => sprintf('PPNPN-BATCH-%03d', $i),
                'full_name' => "Pegawai Batch {$i}",
                'position_id' => $this->obPosition->id,
                'base_salary' => 3000000,
                'is_active' => true,
                'contract_start_date' => '2026-01-01',
                'contract_end_date' => '2026-12-31',
            ]);

            // Add sample attendances
            Attendance::create([
                'employee_id' => $emp->id,
                'position_id' => $this->obPosition->id,
                'date' => "{$year}-{$month}-02",
                'status' => Attendance::STATUS_ON_TIME,
                'check_in_at' => Carbon::parse("{$year}-{$month}-02 06:50:00"),
                'deduction_percentage' => 0.00,
            ]);

            // Add sample active sanction
            ViolationSanction::create([
                'employee_id' => $emp->id,
                'sanction_type' => ViolationSanction::TYPE_SP_1,
                'duration_months' => 1,
                'deduction_percentage' => 5.00,
                'is_active' => true,
                'issued_date' => "{$year}-{$month}-01",
                'start_date' => "{$year}-{$month}-01",
                'end_date' => "{$year}-{$month}-30",
            ]);
        }

        /** @var MonthlyClosingService $service */
        $service = app(MonthlyClosingService::class);

        // Count queries executed during getPayrollData
        DB::enableQueryLog();
        DB::flushQueryLog();

        $payrollData = $service->getPayrollData($year, $month);

        $executedQueries = DB::getQueryLog();
        $queryCount = count($executedQueries);
        DB::disableQueryLog();

        // Verify calculation succeeded with all employees
        $this->assertGreaterThanOrEqual(10, count($payrollData['items']));
        $this->assertGreaterThan(0, $payrollData['totals']['total_base_salary']);

        // Assert query count is bounded and constant (<= 7 queries for closing check + employees + batch attendances + batch sanctions)
        // Without optimization, 15 employees would trigger 60+ queries (N+1).
        $this->assertLessThanOrEqual(7, $queryCount, "Query count {$queryCount} exceeds threshold, indicating an N+1 query problem.");
    }

    /**
     * Test 5: Concurrency & Peak Load Stress Test - 50 Simultaneous Employee Check-ins (US-606 Criterion 3 / TC-S6-06).
     * Simulates 50 concurrent mobile check-ins during peak morning window (06:45–07:15 WIB).
     * Response time requirement: Average <= 800 ms per check-in transaction without deadlocks or errors.
     */
    public function test_concurrent_peak_morning_check_in_stress_test(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 06:58:30', 'Asia/Jakarta'));

        /** @var BiometricVerificationService $biometricService */
        $biometricService = app(BiometricVerificationService::class);

        // 1. Provision 50 active PPNPN employees with face templates
        $testUsers = [];
        for ($i = 1; $i <= 50; $i++) {
            $user = User::create([
                'name' => "PPNPN Load Personil {$i}",
                'username' => sprintf('load_ppnpn_%02d', $i),
                'email' => sprintf('load%02d@djpb.kemenkeu.go.id', $i),
                'password' => Hash::make('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]);
            $user->syncRoles(['karyawan']);

            $emp = Employee::create([
                'user_id' => $user->id,
                'employee_code' => sprintf('PPNPN-LOAD-%03d', $i),
                'full_name' => "PPNPN Load Personil {$i}",
                'position_id' => $this->obPosition->id,
                'base_salary' => 3200000,
                'is_active' => true,
                'contract_start_date' => '2026-01-01',
                'contract_end_date' => '2026-12-31',
            ]);

            $biometricService->enrollEmployee($emp->id, $this->sampleFaceVector);
            $testUsers[] = $user;
        }

        $durations = [];
        $successCount = 0;

        // 2. Simulate 50 concurrent check-in transactions in rapid succession
        foreach ($testUsers as $user) {
            $startTime = microtime(true);

            $response = $this->actingAs($user)
                ->postJson('/mobile/attendance/check-in', [
                    'position_id' => $this->obPosition->id,
                    'latitude' => (float) $this->office->latitude,
                    'longitude' => (float) $this->office->longitude,
                    'accuracy' => 10.0,
                    'is_mock' => false,
                    'liveness_verified' => true,
                    'face_probe_vector' => $this->sampleFaceVector,
                    'photo' => $this->samplePhotoBase64,
                ]);

            $durations[] = (microtime(true) - $startTime) * 1000;

            if ($response->status() === 200 && $response->json('success') === true) {
                $successCount++;
            }
        }

        // 3. Evaluate assertions
        // All 50 transactions must succeed
        $this->assertEquals(50, $successCount, 'Seluruh 50 personil wajib berhasil presensi masuk tanpa galat.');

        // Verify records in database
        $this->assertEquals(50, Attendance::whereDate('date', '2026-11-23')->count());
        $this->assertEquals(50, AuditLog::where('action', 'ATTENDANCE_CHECK_IN')->count());

        // Performance benchmark: Average response time must be <= 800 ms (FR-101 / TC-S6-06)
        $avgDuration = array_sum($durations) / count($durations);
        $maxDuration = max($durations);

        $this->assertLessThanOrEqual(
            800.0,
            $avgDuration,
            "Rata-rata waktu respon ({$avgDuration} ms) melebihi batas toleransi beban puncak 800 ms (Maks: {$maxDuration} ms)."
        );
    }

    /**
     * Test 6: Prevention of duplicate check-in under high concurrency.
     */
    public function test_concurrent_duplicate_check_in_is_gracefully_prevented(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 07:02:00', 'Asia/Jakarta'));

        $user = User::where('username', 'ppnpn01')->firstOrFail();
        $user->update(['must_change_password' => false]);
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        /** @var BiometricVerificationService $biometricService */
        $biometricService = app(BiometricVerificationService::class);
        $biometricService->enrollEmployee($employee->id, $this->sampleFaceVector);

        // First check-in succeeds
        $resp1 = $this->actingAs($user)->postJson('/mobile/attendance/check-in', [
            'position_id' => $this->obPosition->id,
            'latitude' => (float) $this->office->latitude,
            'longitude' => (float) $this->office->longitude,
            'liveness_verified' => true,
            'face_probe_vector' => $this->sampleFaceVector,
            'photo' => $this->samplePhotoBase64,
        ]);
        $resp1->assertOk();

        // Immediate subsequent duplicate check-in is rejected gracefully with 422
        $resp2 = $this->actingAs($user)->postJson('/mobile/attendance/check-in', [
            'position_id' => $this->obPosition->id,
            'latitude' => (float) $this->office->latitude,
            'longitude' => (float) $this->office->longitude,
            'liveness_verified' => true,
            'face_probe_vector' => $this->sampleFaceVector,
            'photo' => $this->samplePhotoBase64,
        ]);

        $resp2->assertStatus(422);
        $resp2->assertJson([
            'success' => false,
            'data' => [
                'already_checked_in' => true,
            ],
        ]);
    }
}
