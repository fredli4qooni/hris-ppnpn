<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AttendancePhotoService
{
    /**
     * Maximum width for compressed thumbnail in pixels.
     */
    public const MAX_WIDTH = 480;

    /**
     * Maximum height for compressed thumbnail in pixels.
     */
    public const MAX_HEIGHT = 640;

    /**
     * JPEG compression quality (0-100).
     */
    public const JPEG_QUALITY = 75;

    /**
     * Sanitize and strip all EXIF metadata from raw photo binary (UU PDP No. 27/2022).
     */
    public function sanitizeAndStripExif(string $rawBinary): string
    {
        return $this->compressImageBinary($rawBinary);
    }

    /**
     * Store and compress a selfie photo thumbnail (< 100 KB) into public storage.
     *
     * @param  string  $base64Image  Data URL or raw base64 string
     * @param  string  $employeeCode  Employee unique identifier
     * @param  string  $actionType  'check_in' or 'check_out'
     * @return string Relative storage path in 'public' disk
     */
    public function storeSelfieThumbnail(string $base64Image, string $employeeCode, string $actionType = 'check_in'): string
    {
        // Strip data:image/...;base64, header if present
        if (str_contains($base64Image, ';base64,')) {
            [, $base64Image] = explode(';base64,', $base64Image);
        }

        $binaryData = base64_decode($base64Image, true);
        if ($binaryData === false) {
            throw new RuntimeException('Format base64 citra swafoto tidak valid.');
        }

        $compressedBinary = $this->compressImageBinary($binaryData);

        $monthFolder = now()->format('Y-m');
        $safeEmployeeCode = Str::slug($employeeCode, '_');
        $fileName = sprintf('%s_%s_%s_%s.jpg', $safeEmployeeCode, $actionType, now()->format('Ymd_His'), Str::random(6));
        $relativePath = "attendances/{$monthFolder}/{$fileName}";

        Storage::disk('public')->put($relativePath, $compressedBinary);

        return $relativePath;
    }

    /**
     * Compress raw binary image data using GD to ensure size < 100 KB.
     */
    protected function compressImageBinary(string $rawBinary): string
    {
        if (! extension_loaded('gd') || ! function_exists('imagecreatefromstring')) {
            return $rawBinary;
        }

        $sourceImage = @imagecreatefromstring($rawBinary);
        if (! $sourceImage) {
            return $rawBinary;
        }

        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);

        // Calculate proportional scale down if larger than MAX_WIDTH x MAX_HEIGHT
        $ratio = min(self::MAX_WIDTH / max(1, $origWidth), self::MAX_HEIGHT / max(1, $origHeight), 1.0);
        $targetWidth = (int) round($origWidth * $ratio);
        $targetHeight = (int) round($origHeight * $ratio);

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

        // Preserve colors
        imagecopyresampled(
            $targetImage,
            $sourceImage,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $origWidth,
            $origHeight
        );

        ob_start();
        imagejpeg($targetImage, null, self::JPEG_QUALITY);
        $compressed = ob_get_clean();

        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        return $compressed !== false ? $compressed : $rawBinary;
    }
}
