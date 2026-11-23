<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LateException extends Model
{
    use HasFactory, SoftDeletes;

    // Categories
    public const CATEGORY_DISASTER = 'DISASTER';

    public const CATEGORY_ACCIDENT = 'ACCIDENT';

    public const CATEGORY_FAMILY_BEREAVEMENT = 'FAMILY_BEREAVEMENT';

    public const CATEGORY_OTHER = 'OTHER_FORCE_MAJEURE';

    // Statuses
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_APPROVED_SUPERVISOR = 'APPROVED_SUPERVISOR';

    public const STATUS_REJECTED_SUPERVISOR = 'REJECTED_SUPERVISOR';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'attendance_id',
        'incident_date',
        'category',
        'incident_time',
        'description',
        'proof_file_path',
        'status',
        'supervisor_id',
        'supervisor_reviewed_at',
        'supervisor_notes',
        'atasan_id',
        'atasan_decided_at',
        'atasan_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'supervisor_reviewed_at' => 'datetime',
            'atasan_decided_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return in_array($this->status, [self::STATUS_REJECTED, self::STATUS_REJECTED_SUPERVISOR], true);
    }

    public function getCategoryLabel(): string
    {
        return match ($this->category) {
            self::CATEGORY_DISASTER => 'Bencana Alam (Banjir, Longsor, Gempa)',
            self::CATEGORY_ACCIDENT => 'Kecelakaan Lalu Lintas / Kendala Perjalanan Berat',
            self::CATEGORY_FAMILY_BEREAVEMENT => 'Musibah Keluarga Inti (Wafat / Sakit Kritis)',
            default => 'Kondisi Kahar Lainnya',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu Telaah Supervisor',
            self::STATUS_APPROVED_SUPERVISOR => 'Diverifikasi Supervisor (Menunggu Kasubag)',
            self::STATUS_REJECTED_SUPERVISOR => 'Ditolak Supervisor Lapangan',
            self::STATUS_APPROVED => 'Disetujui Sah (Potongan TL Dianulir)',
            self::STATUS_REJECTED => 'Ditolak Definitif oleh Kasubag / PPK',
            default => $this->status,
        };
    }

    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'success',
            self::STATUS_APPROVED_SUPERVISOR => 'info',
            self::STATUS_REJECTED, self::STATUS_REJECTED_SUPERVISOR => 'danger',
            default => 'warning',
        };
    }
}
