<?php

namespace Tests\Unit;

use App\Services\BiometricVerificationService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LivenessDetectionTest extends TestCase
{
    protected BiometricVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BiometricVerificationService;
    }

    /**
     * Test Eye Aspect Ratio (EAR) calculation with open eye landmark points (FR-202 / US-302).
     */
    public function test_calculate_eye_aspect_ratio_for_open_eye(): void
    {
        // 6 landmark points for an open eye
        $openEyePoints = [
            ['x' => 10, 'y' => 50], // p1 (outer corner)
            ['x' => 20, 'y' => 40], // p2 (top eyelid 1)
            ['x' => 30, 'y' => 40], // p3 (top eyelid 2)
            ['x' => 40, 'y' => 50], // p4 (inner corner)
            ['x' => 30, 'y' => 60], // p5 (bottom eyelid 2)
            ['x' => 20, 'y' => 60], // p6 (bottom eyelid 1)
        ];

        $ear = $this->service->calculateEyeAspectRatio($openEyePoints);

        // Vertical distances: 20 each. Horizontal distance: 30.
        // EAR = (20 + 20) / (2 * 30) = 40 / 60 = ~0.6667
        $this->assertGreaterThan(0.20, $ear);
        $this->assertEquals(0.6667, $ear);
    }

    /**
     * Test Eye Aspect Ratio (EAR) drops significantly for closed / blinking eye (< 0.20).
     */
    public function test_calculate_eye_aspect_ratio_for_closed_eye(): void
    {
        // 6 landmark points for a closed / blinking eye
        $closedEyePoints = [
            ['x' => 10, 'y' => 50], // p1
            ['x' => 20, 'y' => 49], // p2 (nearly closed)
            ['x' => 30, 'y' => 49], // p3
            ['x' => 40, 'y' => 50], // p4
            ['x' => 30, 'y' => 51], // p5
            ['x' => 20, 'y' => 51], // p6
        ];

        $ear = $this->service->calculateEyeAspectRatio($closedEyePoints);

        // Vertical distances: 2 each. Horizontal: 30.
        // EAR = (2 + 2) / 60 = 4 / 60 = ~0.0667
        $this->assertLessThan(0.20, $ear);
        $this->assertEquals(0.0667, $ear);
    }

    /**
     * Test EAR throws exception if point count is not exactly 6.
     */
    public function test_calculate_eye_aspect_ratio_validates_points_count(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->calculateEyeAspectRatio([
            ['x' => 1, 'y' => 2],
            ['x' => 3, 'y' => 4],
        ]);
    }

    /**
     * Test verifyLiveness passes valid blink challenge within 8 seconds.
     */
    public function test_verify_liveness_approves_valid_blink_challenge(): void
    {
        $result = $this->service->verifyLiveness([
            'passed' => true,
            'challenge_type' => 'blink',
            'duration_ms' => 2400,
            'ear_value' => 0.16,
        ]);

        $this->assertTrue($result['is_valid']);
        $this->assertGreaterThan(85.0, $result['score']);
        $this->assertEquals('blink', $result['challenge_type']);
        $this->assertStringContainsString('Terverifikasi manusia hidup', $result['message']);
    }

    /**
     * Test verifyLiveness passes valid head turn challenge within 8 seconds.
     */
    public function test_verify_liveness_approves_valid_head_turn_challenge(): void
    {
        $result = $this->service->verifyLiveness([
            'passed' => true,
            'challenge_type' => 'head_turn',
            'duration_ms' => 3100,
            'yaw_angle' => 16.5,
        ]);

        $this->assertTrue($result['is_valid']);
        $this->assertGreaterThan(85.0, $result['score']);
        $this->assertEquals('head_turn', $result['challenge_type']);
    }

    /**
     * Test verifyLiveness rejects challenge if duration exceeds 8000 ms timeout.
     */
    public function test_verify_liveness_rejects_exceeded_timeout(): void
    {
        $result = $this->service->verifyLiveness([
            'passed' => true,
            'challenge_type' => 'blink',
            'duration_ms' => 8500, // Exceeds 8000ms
            'ear_value' => 0.15,
        ]);

        $this->assertFalse($result['is_valid']);
        $this->assertEquals(0.0, $result['score']);
        $this->assertStringContainsString('melebihi batas maksimal 8000 ms', $result['message']);
    }

    /**
     * Test verifyLiveness rejects blink challenge if EAR value exceeds blink threshold (> 0.20).
     */
    public function test_verify_liveness_rejects_insufficient_blink(): void
    {
        $result = $this->service->verifyLiveness([
            'passed' => true,
            'challenge_type' => 'blink',
            'duration_ms' => 1800,
            'ear_value' => 0.28, // Did not actually blink
        ]);

        $this->assertFalse($result['is_valid']);
        $this->assertStringContainsString('tidak memenuhi syarat kedipan mata', $result['message']);
    }

    /**
     * Test verifyLiveness rejects head turn if yaw angle is below 15 degrees.
     */
    public function test_verify_liveness_rejects_insufficient_head_turn(): void
    {
        $result = $this->service->verifyLiveness([
            'passed' => true,
            'challenge_type' => 'head_turn',
            'duration_ms' => 2000,
            'yaw_angle' => 8.2, // Below 15 degrees
        ]);

        $this->assertFalse($result['is_valid']);
        $this->assertStringContainsString('tidak mencapai ambang batas tantangan', $result['message']);
    }

    /**
     * Test verifyLiveness rejects when passed flag is false (spoofing alert).
     */
    public function test_verify_liveness_rejects_when_passed_is_false(): void
    {
        $result = $this->service->verifyLiveness([
            'passed' => false,
            'challenge_type' => 'passive',
            'duration_ms' => 1200,
        ]);

        $this->assertFalse($result['is_valid']);
        $this->assertStringContainsString('Anti-Spoofing Alert', $result['message']);
    }
}
