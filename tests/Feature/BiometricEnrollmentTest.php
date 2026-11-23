<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\FaceTemplate;
use App\Models\User;
use App\Services\BiometricVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BiometricEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Employee $employee;

    protected BiometricVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->adminUser = User::where('username', 'admin')->first();
        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->first();
        $this->service = new BiometricVerificationService;
    }

    /**
     * Test generating a deterministic 128-float mock descriptor (TC-08).
     */
    public function test_can_generate_valid_128_float_face_descriptor(): void
    {
        $vector = $this->service->generateMockDescriptor(0.75);

        $this->assertCount(128, $vector);
        $this->assertIsFloat($vector[0]);

        // L2 norm must be approximately 1.0
        $norm = sqrt(array_sum(array_map(fn ($v) => $v * $v, $vector)));
        $this->assertEqualsWithDelta(1.0, $norm, 0.001);
    }

    /**
     * TC-09: Memeriksa data templat wajah pada database secara langsung.
     * Kolom berisi string acak terenkripsi AES-256; kunci dekripsi berhasil mengembalikan 128 float.
     */
    public function test_tc_09_vector_is_encrypted_with_aes_256_in_database_and_decryptable(): void
    {
        $vector = $this->service->generateMockDescriptor(0.35);

        // Enroll employee
        $template = $this->service->enrollEmployee($this->employee->id, $vector, $this->adminUser->id);

        // Inspect direct raw database value via DB::table (bypassing model)
        $rawRow = DB::table('face_templates')->where('id', $template->id)->first();
        $this->assertNotNull($rawRow);

        $rawCipher = $rawRow->encrypted_vector;

        // Ciphertext must NOT be raw JSON or contain readable numbers
        $this->assertNotEmpty($rawCipher);
        $this->assertStringNotContainsString('[', $rawCipher);
        $this->assertStringNotContainsString(']', $rawCipher);
        $this->assertStringNotContainsString((string) $vector[0], $rawCipher);

        // Must be decryptable using Laravel Crypt (AES-256)
        $decryptedJson = Crypt::decryptString($rawCipher);
        $this->assertJson($decryptedJson);

        // Must accurately recover the original 128-float vector
        $recovered = $this->service->decryptVector($rawCipher);
        $this->assertCount(128, $recovered);
        $this->assertEqualsWithDelta($vector[0], $recovered[0], 0.0001);
        $this->assertEqualsWithDelta($vector[127], $recovered[127], 0.0001);
    }

    /**
     * Test calculateCentroid averages 3-5 face frames and normalizes properly (US-204).
     */
    public function test_calculate_centroid_averages_multiple_face_frames_accurately(): void
    {
        $frame1 = $this->service->generateMockDescriptor(0.1);
        $frame2 = $this->service->generateMockDescriptor(0.2);
        $frame3 = $this->service->generateMockDescriptor(0.3);

        $centroid = $this->service->calculateCentroid([$frame1, $frame2, $frame3]);

        $this->assertCount(128, $centroid);

        // Centroid must have L2 norm of 1.0
        $norm = sqrt(array_sum(array_map(fn ($v) => $v * $v, $centroid)));
        $this->assertEqualsWithDelta(1.0, $norm, 0.001);
    }

    /**
     * Test Euclidean distance calculation matches zero for identical vectors.
     */
    public function test_euclidean_distance_is_zero_for_identical_vectors(): void
    {
        $vector = $this->service->generateMockDescriptor(0.5);
        $distance = $this->service->calculateEuclideanDistance($vector, $vector);

        $this->assertEquals(0.0, $distance);
    }

    /**
     * Test face verification matches when distance <= 0.40 and mismatches when distance > 0.40 (FR-202).
     */
    public function test_face_verification_matches_within_threshold_and_fails_beyond(): void
    {
        $baseVector = $this->service->generateMockDescriptor(0.5);
        $encryptedTemplate = $this->service->encryptVector($baseVector);

        // 1. Same vector -> distance 0.0 -> MATCH
        $matchResult = $this->service->verifyFace($baseVector, $encryptedTemplate, 0.40);
        $this->assertTrue($matchResult['is_match']);
        $this->assertEquals('MATCH', $matchResult['status']);
        $this->assertEquals(0.0, $matchResult['distance']);
        $this->assertEquals(100.0, $matchResult['similarity_percentage']);

        // 2. Different vector with distance > 0.40 -> MISMATCH
        $differentVector = $this->service->generateMockDescriptor(0.9);
        $mismatchResult = $this->service->verifyFace($differentVector, $encryptedTemplate, 0.40);

        $this->assertGreaterThan(0.40, $mismatchResult['distance']);
        $this->assertFalse($mismatchResult['is_match']);
        $this->assertEquals('MISMATCH', $mismatchResult['status']);
    }

    /**
     * Test enrollment records audit log and updates template if already exists.
     */
    public function test_enrollment_records_audit_log_and_prevents_duplicate_template_rows(): void
    {
        $vector = $this->service->generateMockDescriptor(0.4);

        // First enrollment
        $template1 = $this->service->enrollEmployee($this->employee->id, $vector, $this->adminUser->id);
        $this->assertNotNull($template1);

        $this->assertEquals(1, FaceTemplate::where('employee_id', $this->employee->id)->count());

        // Second enrollment (update)
        $newVector = $this->service->generateMockDescriptor(0.6);
        $template2 = $this->service->enrollEmployee($this->employee->id, $newVector, $this->adminUser->id);

        $this->assertEquals(1, FaceTemplate::where('employee_id', $this->employee->id)->count());
        $this->assertEquals($template1->id, $template2->id);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'FACE_TEMPLATE_ENROLLED',
            'entity_type' => FaceTemplate::class,
        ]);
    }

    /**
     * Test employee model relationship with FaceTemplate.
     */
    public function test_employee_relationship_with_face_template(): void
    {
        $this->assertFalse($this->employee->hasFaceEnrolled());

        $vector = $this->service->generateMockDescriptor(0.4);
        $this->service->enrollEmployee($this->employee->id, $vector, $this->adminUser->id);

        $this->employee->refresh();
        $this->assertTrue($this->employee->hasFaceEnrolled());
        $this->assertInstanceOf(FaceTemplate::class, $this->employee->faceTemplate);

        // Can retrieve decrypted vector directly from model helper
        $decrypted = $this->employee->faceTemplate->getDecryptedVector();
        $this->assertCount(128, $decrypted);
    }
}
