<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\FaceTemplate;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;

class BiometricVerificationService
{
    /**
     * Standard descriptor vector dimension extracted by FaceAPI.
     */
    public const VECTOR_DIMENSION = 128;

    /**
     * Maximum Euclidean distance threshold for a positive face match (FR-202 & SPK).
     */
    public const MATCH_THRESHOLD = 0.40;

    /**
     * Default FaceAPI weights model version.
     */
    public const MODEL_VERSION = 'vladmandic-face-api-v1';

    /**
     * Maximum allowed timeout for an active liveness challenge in milliseconds (8 seconds / US-302).
     */
    public const LIVENESS_TIMEOUT_MS = 8000;

    /**
     * Threshold for Eye Aspect Ratio (EAR) blink detection.
     * Open eyes typically range between 0.28 - 0.38, while a closed eye / blink drops below 0.24 (accommodating Asian monolid/hooded eyes & webcam noise).
     */
    public const EAR_BLINK_THRESHOLD = 0.24;

    /**
     * Threshold for head yaw turn detection in degrees (+/- 15 degrees).
     */
    public const YAW_TURN_THRESHOLD = 15.0;

    /**
     * Encrypt a 128-float face descriptor vector using AES-256 for UU PDP compliance.
     *
     * @param  list<float|numeric>  $vector
     */
    public function encryptVector(array $vector): string
    {
        if (count($vector) !== self::VECTOR_DIMENSION) {
            throw new InvalidArgumentException('Vektor deskriptor wajah harus tepat berjumlah '.self::VECTOR_DIMENSION.' dimensi float.');
        }

        $floatArray = array_map(fn ($val) => (float) $val, array_values($vector));
        $json = json_encode($floatArray, JSON_THROW_ON_ERROR);

        return Crypt::encryptString($json);
    }

    /**
     * Decrypt an AES-256 encrypted vector ciphertext back into a 128-float array.
     *
     * @return list<float>
     */
    public function decryptVector(string $encryptedCipher): array
    {
        $json = Crypt::decryptString($encryptedCipher);
        /** @var list<float|int> $array */
        $array = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($array) || count($array) !== self::VECTOR_DIMENSION) {
            throw new InvalidArgumentException('Format ciphertext biometrik tidak valid atau dimensi tidak sesuai 128 float.');
        }

        return array_map(fn ($val) => (float) $val, array_values($array));
    }

    /**
     * Calculate centroid (average) vector from 3 to 5 sample face frames and normalize it (L2 norm).
     *
     * @param  list<list<float|numeric>>  $sampleVectors
     * @return list<float>
     */
    public function calculateCentroid(array $sampleVectors): array
    {
        $count = count($sampleVectors);
        if ($count === 0) {
            throw new InvalidArgumentException('Minimal diperlukan 1 sampel frame wajah untuk ekstraksi centroid.');
        }

        // Sum across all vectors for each of the 128 dimensions
        $centroid = array_fill(0, self::VECTOR_DIMENSION, 0.0);

        foreach ($sampleVectors as $vector) {
            if (count($vector) !== self::VECTOR_DIMENSION) {
                throw new InvalidArgumentException('Setiap frame wajah harus memiliki tepat '.self::VECTOR_DIMENSION.' dimensi.');
            }
            foreach ($vector as $i => $val) {
                $centroid[$i] += (float) $val;
            }
        }

        // Average
        for ($i = 0; $i < self::VECTOR_DIMENSION; $i++) {
            $centroid[$i] /= $count;
        }

        // L2 normalize
        $norm = 0.0;
        for ($i = 0; $i < self::VECTOR_DIMENSION; $i++) {
            $norm += $centroid[$i] ** 2;
        }
        $norm = sqrt($norm);

        if ($norm > 0) {
            for ($i = 0; $i < self::VECTOR_DIMENSION; $i++) {
                $centroid[$i] = round($centroid[$i] / $norm, 6);
            }
        }

        return $centroid;
    }

    /**
     * Calculate Euclidean distance between two 128-dimension descriptor vectors.
     * Formula: d = sqrt( sum( (A[i] - B[i])^2 ) )
     *
     * @param  list<float>  $vectorA
     * @param  list<float>  $vectorB
     */
    public function calculateEuclideanDistance(array $vectorA, array $vectorB): float
    {
        if (count($vectorA) !== self::VECTOR_DIMENSION || count($vectorB) !== self::VECTOR_DIMENSION) {
            throw new InvalidArgumentException('Kedua vektor harus memiliki tepat '.self::VECTOR_DIMENSION.' dimensi float.');
        }

        $sumSquaredDiff = 0.0;
        for ($i = 0; $i < self::VECTOR_DIMENSION; $i++) {
            $diff = (float) $vectorA[$i] - (float) $vectorB[$i];
            $sumSquaredDiff += $diff * $diff;
        }

        return round(sqrt($sumSquaredDiff), 4);
    }

    /**
     * Verify a probe face vector against an encrypted registered template.
     *
     * @param  list<float>  $probeVector
     * @return array{
     *     is_match: bool,
     *     distance: float,
     *     threshold: float,
     *     similarity_percentage: float,
     *     status: 'MATCH'|'MISMATCH',
     *     message: string
     * }
     */
    public function verifyFace(
        array $probeVector,
        string $encryptedRegisteredTemplate,
        float $threshold = self::MATCH_THRESHOLD
    ): array {
        $registeredVector = $this->decryptVector($encryptedRegisteredTemplate);
        $distance = $this->calculateEuclideanDistance($probeVector, $registeredVector);

        $isMatch = $distance <= $threshold;

        // Similarity percentage calculation: 0.0 distance = 100%, 0.80 distance = 0%
        $similarity = round(max(0.0, min(100.0, (1.0 - ($distance / 0.80)) * 100.0)), 2);

        return [
            'is_match' => $isMatch,
            'distance' => $distance,
            'threshold' => $threshold,
            'similarity_percentage' => $similarity,
            'status' => $isMatch ? 'MATCH' : 'MISMATCH',
            'message' => $isMatch
                ? "Wajah COCOK (Jarak: {$distance}, Kemiripan: {$similarity}%, Batas: {$threshold})."
                : "Wajah TIDAK COCOK (Jarak: {$distance} melebihi batas toleransi {$threshold}).",
        ];
    }

    /**
     * Enroll an employee's face template with encryption and audit trail.
     *
     * @param  list<list<float>>|list<float>  $vectors  Single 128-float vector or array of 3-5 frame vectors
     */
    public function enrollEmployee(int $employeeId, array $vectors, ?int $adminId = null): FaceTemplate
    {
        // Detect if single vector or list of sample vectors
        if (isset($vectors[0]) && is_array($vectors[0])) {
            $sampleCount = count($vectors);
            /** @var list<list<float>> $vectors */
            $centroidVector = $this->calculateCentroid($vectors);
        } else {
            $sampleCount = 1;
            /** @var list<float> $vectors */
            $centroidVector = $this->calculateCentroid([$vectors]);
        }

        $encryptedVector = $this->encryptVector($centroidVector);

        $template = FaceTemplate::updateOrCreate(
            ['employee_id' => $employeeId],
            [
                'encrypted_vector' => $encryptedVector,
                'sample_count' => $sampleCount,
                'model_version' => self::MODEL_VERSION,
                'enrolled_by_admin_id' => $adminId,
                'enrolled_at' => now(),
            ]
        );

        // Record Audit Log
        AuditLog::create([
            'user_id' => $adminId,
            'action' => 'FACE_TEMPLATE_ENROLLED',
            'entity_type' => FaceTemplate::class,
            'entity_id' => $template->id,
            'new_values' => [
                'employee_id' => $employeeId,
                'sample_count' => $sampleCount,
                'model_version' => self::MODEL_VERSION,
                'is_encrypted_aes256' => true,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $template;
    }

    /**
     * Calculate Eye Aspect Ratio (EAR) from 6 landmark 2D coordinates for blink detection (FR-202 / US-302).
     * Formula: EAR = (||p2 - p6|| + ||p3 - p5||) / (2 * ||p1 - p4||)
     *
     * @param  list<array{x: float|numeric, y: float|numeric}>  $eyePoints  6 landmark points of an eye
     */
    public function calculateEyeAspectRatio(array $eyePoints): float
    {
        if (count($eyePoints) !== 6) {
            throw new InvalidArgumentException('Titik landmark mata harus tepat berjumlah 6 titik koordinat.');
        }

        // Distance between p2 and p6 (vertical 1)
        $p2_p6 = sqrt((((float) $eyePoints[1]['x'] - (float) $eyePoints[5]['x']) ** 2) + (((float) $eyePoints[1]['y'] - (float) $eyePoints[5]['y']) ** 2));
        // Distance between p3 and p5 (vertical 2)
        $p3_p5 = sqrt((((float) $eyePoints[2]['x'] - (float) $eyePoints[4]['x']) ** 2) + (((float) $eyePoints[2]['y'] - (float) $eyePoints[4]['y']) ** 2));
        // Distance between p1 and p4 (horizontal)
        $p1_p4 = sqrt((((float) $eyePoints[0]['x'] - (float) $eyePoints[3]['x']) ** 2) + (((float) $eyePoints[0]['y'] - (float) $eyePoints[3]['y']) ** 2));

        if ($p1_p4 == 0.0) {
            return 0.0;
        }

        return round(($p2_p6 + $p3_p5) / (2.0 * $p1_p4), 4);
    }

    /**
     * Validate an active or passive liveness challenge verification payload (FR-202 / US-302).
     *
     * @param  array{
     *     passed?: bool,
     *     challenge_type?: string,
     *     duration_ms?: int|float,
     *     ear_value?: float,
     *     yaw_angle?: float,
     *     timestamp?: int|string
     * }  $livenessData
     * @return array{
     *     is_valid: bool,
     *     score: float,
     *     challenge_type: string,
     *     duration_ms: float,
     *     message: string
     * }
     */
    public function verifyLiveness(array $livenessData): array
    {
        $passed = (bool) ($livenessData['passed'] ?? false);
        $challengeType = (string) ($livenessData['challenge_type'] ?? 'passive');
        $durationMs = (float) ($livenessData['duration_ms'] ?? 0);

        if (! $passed) {
            return [
                'is_valid' => false,
                'score' => 0.0,
                'challenge_type' => $challengeType,
                'duration_ms' => $durationMs,
                'message' => 'Liveness check gagal atau terdeteksi foto/layar statis (Anti-Spoofing Alert).',
            ];
        }

        // Validate timeout (max 8 seconds / 8000 ms)
        if ($durationMs > self::LIVENESS_TIMEOUT_MS) {
            return [
                'is_valid' => false,
                'score' => 0.0,
                'challenge_type' => $challengeType,
                'duration_ms' => $durationMs,
                'message' => "Liveness gagal: Durasi penyelesaian ({$durationMs} ms) melebihi batas maksimal ".self::LIVENESS_TIMEOUT_MS.' ms.',
            ];
        }

        // Validate specific metrics if provided
        if ($challengeType === 'blink' && isset($livenessData['ear_value'])) {
            $ear = (float) $livenessData['ear_value'];
            if ($ear > self::EAR_BLINK_THRESHOLD) {
                return [
                    'is_valid' => false,
                    'score' => 0.0,
                    'challenge_type' => $challengeType,
                    'duration_ms' => $durationMs,
                    'message' => "Nilai EAR ({$ear}) tidak memenuhi syarat kedipan mata (< ".self::EAR_BLINK_THRESHOLD.').',
                ];
            }
        }

        if ($challengeType === 'head_turn' && isset($livenessData['yaw_angle'])) {
            $yaw = abs((float) $livenessData['yaw_angle']);
            if ($yaw < self::YAW_TURN_THRESHOLD) {
                return [
                    'is_valid' => false,
                    'score' => 0.0,
                    'challenge_type' => $challengeType,
                    'duration_ms' => $durationMs,
                    'message' => "Sudut rotasi kepala ({$yaw}°) tidak mencapai ambang batas tantangan (>= ".self::YAW_TURN_THRESHOLD.'°).',
                ];
            }
        }

        $confidenceScore = round(min(99.5, max(85.0, 100.0 - ($durationMs / self::LIVENESS_TIMEOUT_MS) * 10)), 2);

        return [
            'is_valid' => true,
            'score' => $confidenceScore,
            'challenge_type' => $challengeType,
            'duration_ms' => $durationMs,
            'message' => "Liveness valid: Terverifikasi manusia hidup melalui tantangan {$challengeType} (Skor: {$confidenceScore}%).",
        ];
    }

    /**
     * Generate a deterministic 128-float normalized mock descriptor for testing and simulation.
     *
     * @return list<float>
     */
    public function generateMockDescriptor(float $seed = 0.5): array
    {
        $vector = [];
        for ($i = 0; $i < self::VECTOR_DIMENSION; $i++) {
            $val = sin($seed * ($i + 1)) * cos($seed * 2.0);
            $vector[] = $val;
        }

        // L2 normalize
        $norm = sqrt(array_sum(array_map(fn ($v) => $v * $v, $vector)));

        return array_map(fn ($v) => round($v / $norm, 6), $vector);
    }
}
