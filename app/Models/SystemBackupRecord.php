<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;

class SystemBackupRecord extends Model
{
    use HasFactory;

    public const TYPE_FULL = 'FULL';

    public const TYPE_DATABASE_ONLY = 'DATABASE_ONLY';

    public const TYPE_ATTACHMENTS_ONLY = 'ATTACHMENTS_ONLY';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'filename',
        'file_path',
        'file_size_bytes',
        'sha256_checksum',
        'backup_type',
        'database_driver',
        'total_tables',
        'is_verified',
        'created_by',
        'notes',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'total_tables' => 'integer',
            'is_verified' => 'boolean',
        ];
    }

    /**
     * User who initiated the backup.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Format file size to human-readable string (KB, MB, GB).
     */
    public function getFormattedSizeBytesAttribute(): string
    {
        $bytes = (float) $this->file_size_bytes;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' Bytes';
    }

    /**
     * Check if the backup physical file currently exists on disk.
     */
    public function fileExistsOnDisk(): bool
    {
        return File::exists($this->file_path);
    }

    /**
     * Verify whether current file on disk matches stored SHA-256 hash.
     */
    public function verifyChecksumIntegrity(): bool
    {
        if (! $this->fileExistsOnDisk()) {
            return false;
        }

        $currentHash = hash_file('sha256', $this->file_path);

        return hash_equals($this->sha256_checksum, (string) $currentHash);
    }
}
