<?php

namespace App\Services;

use App\Models\OfficeLocation;

class GeofenceService
{
    /**
     * Earth radius in meters (mean radius WGS-84).
     */
    public const EARTH_RADIUS_METERS = 6371000.0;

    /**
     * Maximum allowed horizontal accuracy for GPS in meters (FR-204 / US-303).
     * Toleransi disesuaikan hingga 150m untuk mengakomodasi posisi di dalam gedung/ruangan kantor & sinyal Wi-Fi.
     */
    public const MAX_ACCURACY_METERS = 150.0;

    /**
     * Default geofence radius tolerance in meters (FR-203 / US-303).
     */
    public const DEFAULT_GEOFENCE_RADIUS_METERS = 100;

    /**
     * Calculate the great-circle distance between two GPS coordinates using the Haversine formula.
     *
     * @return float Distance in meters (rounded to 2 decimal places)
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        $a = sin($deltaLat / 2) ** 2 +
            cos($lat1Rad) * cos($lat2Rad) * (sin($deltaLon / 2) ** 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round(self::EARTH_RADIUS_METERS * $c, 2);
    }

    /**
     * Static alias for convenience.
     */
    public static function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return (new self)->calculateDistance($lat1, $lon1, $lat2, $lon2);
    }

    /**
     * Determine whether coordinates are within the radius of a target location.
     */
    public function isWithinRadius(float $lat1, float $lon1, float $lat2, float $lon2, int $radiusMeters = 100): bool
    {
        $distance = $this->calculateDistance($lat1, $lon1, $lat2, $lon2);

        return $distance <= $radiusMeters;
    }

    /**
     * Validate an employee's presence GPS coordinates against active office location(s)
     * with accuracy guard and anti-mock fake GPS detection (FR-203, FR-204 / US-303).
     *
     * @return array{
     *     is_valid: bool,
     *     status: 'VALID'|'OUT_OF_BOUNDS'|'LOW_ACCURACY'|'MOCK_LOCATION_DETECTED',
     *     distance_meters: float,
     *     allowed_radius: int,
     *     accuracy_meters: float|null,
     *     is_mock: bool,
     *     office_id: int|null,
     *     office_name: string|null,
     *     message: string
     * }
     */
    public function validatePresenceLocation(
        float $userLatitude,
        float $userLongitude,
        ?OfficeLocation $targetOffice = null,
        ?float $accuracyMeters = null,
        bool $isMock = false
    ): array {
        // 1. Anti-Mock / Fake GPS Guard Check (FR-204)
        if ($isMock) {
            return [
                'is_valid' => false,
                'status' => 'MOCK_LOCATION_DETECTED',
                'distance_meters' => 0.0,
                'allowed_radius' => self::DEFAULT_GEOFENCE_RADIUS_METERS,
                'accuracy_meters' => $accuracyMeters,
                'is_mock' => true,
                'office_id' => $targetOffice?->id,
                'office_name' => $targetOffice?->name,
                'message' => 'Terdeteksi penggunaan lokasi tiruan / Fake GPS (Anti-Mock Guard). Presensi ditolak.',
            ];
        }

        // 2. GPS Accuracy Guard Check (FR-204: max 50 meters)
        if ($accuracyMeters !== null && $accuracyMeters > self::MAX_ACCURACY_METERS) {
            return [
                'is_valid' => false,
                'status' => 'LOW_ACCURACY',
                'distance_meters' => 0.0,
                'allowed_radius' => self::DEFAULT_GEOFENCE_RADIUS_METERS,
                'accuracy_meters' => $accuracyMeters,
                'is_mock' => false,
                'office_id' => $targetOffice?->id,
                'office_name' => $targetOffice?->name,
                'message' => "Akurasi GPS rendah (±{$accuracyMeters} m melebihi batas toleransi maksimal ".self::MAX_ACCURACY_METERS.' m). Harap aktifkan GPS akurasi tinggi atau berpindah ke area terbuka.',
            ];
        }

        if ($targetOffice) {
            $offices = collect([$targetOffice]);
        } else {
            $offices = OfficeLocation::where('is_active', true)->get();
        }

        if ($offices->isEmpty()) {
            return [
                'is_valid' => false,
                'status' => 'OUT_OF_BOUNDS',
                'distance_meters' => 0.0,
                'allowed_radius' => 0,
                'accuracy_meters' => $accuracyMeters,
                'is_mock' => false,
                'office_id' => null,
                'office_name' => null,
                'message' => 'Tidak ada titik lokasi kantor aktif yang terdaftar dalam sistem.',
            ];
        }

        $nearestOffice = null;
        $shortestDistance = PHP_FLOAT_MAX;

        foreach ($offices as $office) {
            $dist = $this->calculateDistance(
                $userLatitude,
                $userLongitude,
                (float) $office->latitude,
                (float) $office->longitude
            );

            if ($dist < $shortestDistance) {
                $shortestDistance = $dist;
                $nearestOffice = $office;
            }
        }

        $allowedRadius = $nearestOffice ? (int) $nearestOffice->radius_meters : self::DEFAULT_GEOFENCE_RADIUS_METERS;
        $isValid = $shortestDistance <= $allowedRadius;

        return [
            'is_valid' => $isValid,
            'status' => $isValid ? 'VALID' : 'OUT_OF_BOUNDS',
            'distance_meters' => $shortestDistance,
            'allowed_radius' => $allowedRadius,
            'accuracy_meters' => $accuracyMeters,
            'is_mock' => false,
            'office_id' => $nearestOffice?->id,
            'office_name' => $nearestOffice?->name,
            'message' => $isValid
                ? "Koordinat VALID. Jarak {$shortestDistance} m berada di dalam radius toleransi {$allowedRadius} m kantor {$nearestOffice?->name}."
                : "Koordinat DITOLAK (OUT OF BOUNDS). Jarak {$shortestDistance} m berada di luar radius toleransi {$allowedRadius} m kantor {$nearestOffice?->name}.",
        ];
    }
}
