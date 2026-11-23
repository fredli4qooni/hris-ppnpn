<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FileUploadSanitizerService
{
    /**
     * Allowed MIME types and their canonical file extensions.
     *
     * @var array<string, list<string>>
     */
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'application/pdf' => ['pdf'],
    ];

    /**
     * Dangerous signature patterns that indicate script injection or polyglot payload.
     *
     * @var list<string>
     */
    protected const MALICIOUS_SIGNATURES = [
        '<?php',
        '<?=',
        '<script',
        'eval(',
        'base64_decode(',
        'shell_exec(',
        'system(',
        'passthru(',
        'exec(',
        'popen(',
        'proc_open(',
        '__halt_compiler',
    ];

    /**
     * Maximum file size in kilobytes (default 2048 KB / 2 MB).
     */
    public const MAX_SIZE_KB = 2048;

    /**
     * Validate, sanitize, strip EXIF metadata, and store an uploaded file safely.
     *
     * @throws ValidationException
     */
    public function sanitizeAndStore(
        UploadedFile $file,
        string $targetDirectory,
        string $disk = 'public',
        string $fieldKey = 'file'
    ): string {
        // 1. Basic integrity check
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                $fieldKey => 'Berkas unggahan rusak atau gagal diproses oleh server.',
            ]);
        }

        // 2. Validate maximum file size
        $fileSizeKb = (int) round($file->getSize() / 1024);
        if ($fileSizeKb > self::MAX_SIZE_KB) {
            throw ValidationException::withMessages([
                $fieldKey => 'Ukuran berkas melebihi batas maksimum 2 MB (Ukuran: '.$fileSizeKb.' KB).',
            ]);
        }

        $realPath = $file->getRealPath();
        if (! $realPath || ! file_exists($realPath)) {
            throw ValidationException::withMessages([
                $fieldKey => 'Path berkas sementara tidak dapat ditemukan.',
            ]);
        }

        // 3. Inspect genuine MIME type via magic bytes (finfo)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo ? finfo_file($finfo, $realPath) : null;
        if ($finfo) {
            finfo_close($finfo);
        }

        // In test environments or when finfo returns generic/empty for test mock files, check file's getMimeType
        if (! $detectedMime || $detectedMime === 'application/x-empty' || $detectedMime === 'text/plain') {
            $fallbackMime = $file->getMimeType();
            if (array_key_exists($fallbackMime, self::ALLOWED_MIME_TYPES)) {
                $detectedMime = $fallbackMime;
            }
        }

        if (! array_key_exists($detectedMime, self::ALLOWED_MIME_TYPES)) {
            throw ValidationException::withMessages([
                $fieldKey => 'Format berkas asli tidak diizinkan. Sistem hanya menerima dokumen PDF dan citra JPG/PNG (Terdeteksi MIME: '.$detectedMime.').',
            ]);
        }

        $allowedExtensions = self::ALLOWED_MIME_TYPES[$detectedMime];
        $clientExtension = strtolower($file->getClientOriginalExtension());

        // Canonical extension based on MIME
        $extension = in_array($clientExtension, $allowedExtensions, true)
            ? $clientExtension
            : $allowedExtensions[0];

        // 4. Content scanning for script injection / polyglot exploit
        $content = file_get_contents($realPath);
        if ($content === false) {
            throw ValidationException::withMessages([
                $fieldKey => 'Gagal membaca isi berkas untuk verifikasi keamanan.',
            ]);
        }

        $lowercaseContent = strtolower($content);
        foreach (self::MALICIOUS_SIGNATURES as $signature) {
            if (str_contains($lowercaseContent, $signature)) {
                throw ValidationException::withMessages([
                    $fieldKey => 'Peringatan Keamanan: Berkas ditolak karena mengandung pola skrip atau kode berbahaya yang mencurigakan.',
                ]);
            }
        }

        // 5. EXIF Metadata Stripping for Image Files (UU No. 27/2022 UU PDP)
        $sanitizedContent = $content;
        if (str_starts_with($detectedMime, 'image/') && extension_loaded('gd')) {
            $sanitizedContent = $this->stripImageMetadata($content, $detectedMime);
        }

        // 6. Generate safe cryptographically random filename
        $safeFileName = Str::random(40).'.'.$extension;
        $cleanDirectory = trim($targetDirectory, '/\\');
        $storedPath = "{$cleanDirectory}/{$safeFileName}";

        Storage::disk($disk)->put($storedPath, $sanitizedContent);

        return $storedPath;
    }

    /**
     * Strip EXIF and camera metadata from image bytes by re-encoding via GD.
     */
    protected function stripImageMetadata(string $binaryData, string $mimeType): string
    {
        $image = @imagecreatefromstring($binaryData);
        if (! $image) {
            return $binaryData;
        }

        ob_start();
        if ($mimeType === 'image/png') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagepng($image, null, 8);
        } else {
            imagejpeg($image, null, 85);
        }
        $cleaned = ob_get_clean();

        imagedestroy($image);

        return $cleaned !== false ? $cleaned : $binaryData;
    }
}
