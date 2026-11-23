<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ON_TIME = 'ON_TIME';

    public const STATUS_TOLERANCE = 'TOLERANCE';

    public const STATUS_LATE_TL = 'LATE_TL';

    public const STATUS_EARLY_LEAVE = 'EARLY_LEAVE';

    public const STATUS_ALPHA = 'ALPHA';

    public const STATUS_LEAVE = 'LEAVE';

    public const STATUS_PERMIT = 'PERMIT';

    public const STATUS_SICK = 'SICK';

    public const CHECK_OUT_ON_TIME = 'ON_TIME';

    public const CHECK_OUT_COMPENSATED = 'LUNAS_PENGGANTIAN';

    public const CHECK_OUT_UNCOMPENSATED = 'TIDAK_MENGGANTI_JAM';

    public const CHECK_OUT_PSW = 'PSW';

    public const TL_NONE = 'NONE';

    public const TL_1 = 'TL_1';

    public const TL_2 = 'TL_2';

    public const TL_3 = 'TL_3';

    public const SHIFT_NORMAL = 'NORMAL';

    public const SHIFT_SECURITY_DAY = 'SECURITY_DAY';

    public const SHIFT_SECURITY_NIGHT = 'SECURITY_NIGHT';

    public const FALLBACK_NONE = 'NONE';

    public const FALLBACK_PENDING = 'PENDING';

    public const FALLBACK_APPROVED = 'APPROVED';

    public const FALLBACK_REJECTED = 'REJECTED';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'position_id',
        'office_location_id',
        'date',
        'check_in_at',
        'check_out_at',
        'check_in_lat',
        'check_in_lng',
        'check_out_lat',
        'check_out_lng',
        'check_in_distance_meters',
        'check_out_distance_meters',
        'check_in_photo_path',
        'check_out_photo_path',
        'status',
        'check_out_status',
        'late_minutes',
        'required_compensation_minutes',
        'early_leave_minutes',
        'is_compensated',
        'tl_category',
        'deduction_percentage',
        'shift_type',
        'is_dispensation',
        'tl_deduction_exempted',
        'dispensation_notes',
        'is_fallback',
        'fallback_reason',
        'fallback_status',
        'fallback_verified_by',
        'fallback_verified_at',
        'fallback_notes',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'check_in_lat' => 'decimal:7',
            'check_in_lng' => 'decimal:7',
            'check_out_lat' => 'decimal:7',
            'check_out_lng' => 'decimal:7',
            'check_in_distance_meters' => 'decimal:2',
            'check_out_distance_meters' => 'decimal:2',
            'late_minutes' => 'integer',
            'required_compensation_minutes' => 'integer',
            'early_leave_minutes' => 'integer',
            'is_compensated' => 'boolean',
            'is_dispensation' => 'boolean',
            'tl_deduction_exempted' => 'boolean',
            'is_fallback' => 'boolean',
            'fallback_verified_at' => 'datetime',
            'deduction_percentage' => 'decimal:2',
        ];
    }

    /**
     * Employee who performed this attendance.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Position active during this attendance session.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Office location where check-in occurred.
     */
    public function officeLocation(): BelongsTo
    {
        return $this->belongsTo(OfficeLocation::class);
    }

    /**
     * Supervisor or Admin user who verified the emergency fallback attendance.
     */
    public function fallbackVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fallback_verified_by');
    }

    /**
     * Late exceptions / force majeure dispensations associated with this attendance.
     */
    public function lateExceptions(): HasMany
    {
        return $this->hasMany(LateException::class);
    }

    /**
     * Check if this check-in is an emergency fallback attendance.
     */
    public function isFallback(): bool
    {
        return (bool) $this->is_fallback;
    }

    /**
     * Check if fallback attendance is awaiting supervisor verification.
     */
    public function isFallbackPending(): bool
    {
        return $this->fallback_status === self::FALLBACK_PENDING;
    }

    /**
     * Check if fallback attendance has been approved.
     */
    public function isFallbackApproved(): bool
    {
        return $this->fallback_status === self::FALLBACK_APPROVED;
    }

    /**
     * Check if this check-in is on time.
     */
    public function isOnTime(): bool
    {
        return $this->status === self::STATUS_ON_TIME;
    }

    /**
     * Check if this check-in is within the 15-minute grace tolerance.
     */
    public function isTolerance(): bool
    {
        return $this->status === self::STATUS_TOLERANCE;
    }

    /**
     * Check if this check-in incurs a TL deduction.
     */
    public function isLateTl(): bool
    {
        return $this->status === self::STATUS_LATE_TL;
    }

    /**
     * Check if check-out has been recorded.
     */
    public function isCheckedOut(): bool
    {
        return $this->check_out_at !== null;
    }

    /**
     * Check if checkout is marked as early leave (PSW).
     */
    public function isPsw(): bool
    {
        return $this->check_out_status === self::CHECK_OUT_PSW || $this->early_leave_minutes > 0;
    }

    /**
     * Label representasi status kehadiran.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ON_TIME => 'Tepat Waktu',
            self::STATUS_TOLERANCE => 'Toleransi Jam',
            self::STATUS_LATE_TL => 'Terlambat (TL)',
            self::STATUS_EARLY_LEAVE => 'Pulang Cepat (PSW)',
            self::STATUS_ALPHA => 'Alpa',
            self::STATUS_LEAVE => 'Cuti',
            self::STATUS_PERMIT => 'Izin',
            self::STATUS_SICK => 'Sakit',
            default => (string) $this->status,
        };
    }

    /**
     * Label kategori keterlambatan (TL).
     */
    public function getTlCategoryLabel(): string
    {
        return match ($this->tl_category) {
            self::TL_1 => 'TL 1 (1-30 Menit)',
            self::TL_2 => 'TL 2 (31-60 Menit)',
            self::TL_3 => 'TL 3 (>60 Menit)',
            default => $this->late_minutes > 0 ? "TL ({$this->late_minutes}m)" : '-',
        };
    }
}
