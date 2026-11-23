<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ViolationDraft extends Model
{
    use HasFactory, SoftDeletes;

    // Trigger Types
    public const TRIGGER_CONSECUTIVE_ALPHA = 'CONSECUTIVE_ALPHA';

    public const TRIGGER_REJECTED_ATTENDANCE_CORRECTION = 'REJECTED_ATTENDANCE_CORRECTION';

    public const TRIGGER_REJECTED_FALLBACK = 'REJECTED_FALLBACK';

    public const TRIGGER_REPEATED_PSW = 'REPEATED_PSW';

    // Statuses
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_CONFIRMED = 'CONFIRMED';

    public const STATUS_DISMISSED = 'DISMISSED';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'violation_catalog_id',
        'trigger_type',
        'incident_date',
        'auto_generated_notes',
        'evidence_data',
        'status',
        'confirmed_by_id',
        'confirmed_at',
        'employee_violation_id',
        'dismissed_by_id',
        'dismissed_at',
        'dismissal_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'evidence_data' => 'array',
            'confirmed_at' => 'datetime',
            'dismissed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(ViolationCatalog::class, 'violation_catalog_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_id');
    }

    public function dismissedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dismissed_by_id');
    }

    public function employeeViolation(): BelongsTo
    {
        return $this->belongsTo(EmployeeViolation::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isDismissed(): bool
    {
        return $this->status === self::STATUS_DISMISSED;
    }

    public function getTriggerLabel(): string
    {
        return match ($this->trigger_type) {
            self::TRIGGER_CONSECUTIVE_ALPHA => 'Alpa Berturut-turut (>= 2 Hari)',
            self::TRIGGER_REJECTED_ATTENDANCE_CORRECTION => 'Koreksi Lupa Absen Ditolak',
            self::TRIGGER_REJECTED_FALLBACK => 'Presensi Fallback Ditolak',
            self::TRIGGER_REPEATED_PSW => 'Pulang Sebelum Waktu (PSW) Berulang (>= 3x)',
            default => $this->trigger_type,
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu Tindak Lanjut Supervisor',
            self::STATUS_CONFIRMED => 'Disahkan Menjadi Pelanggaran Resmi',
            self::STATUS_DISMISSED => 'Dibatalkan (Alasan Dinas)',
            default => $this->status,
        };
    }

    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_CONFIRMED => 'danger',
            self::STATUS_DISMISSED => 'gray',
            default => 'warning',
        };
    }
}
