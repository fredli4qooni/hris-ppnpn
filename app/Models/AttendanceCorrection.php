<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceCorrection extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_APPROVED_SUPERVISOR = 'APPROVED_SUPERVISOR';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    public const TYPE_CHECK_IN = 'CHECK_IN';

    public const TYPE_CHECK_OUT = 'CHECK_OUT';

    public const TYPE_BOTH = 'BOTH';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'attendance_date',
        'correction_type',
        'suggested_check_in_at',
        'suggested_check_out_at',
        'work_summary',
        'proof_document_path',
        'status',
        'supervisor_id',
        'supervisor_reviewed_at',
        'supervisor_notes',
        'atasan_id',
        'atasan_decided_at',
        'atasan_notes',
        'created_attendance_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'suggested_check_in_at' => 'datetime:H:i',
            'suggested_check_out_at' => 'datetime:H:i',
            'supervisor_reviewed_at' => 'datetime',
            'atasan_decided_at' => 'datetime',
        ];
    }

    /**
     * Employee requesting attendance correction.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Supervisor who performed stage 1 review.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Atasan (Kasubbag/PPK) who issued final decision.
     */
    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    /**
     * Attendance record created or resolved by this correction.
     */
    public function createdAttendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'created_attendance_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getTypeLabel(): string
    {
        return match ($this->correction_type) {
            self::TYPE_CHECK_IN => 'Absen Masuk',
            self::TYPE_CHECK_OUT => 'Absen Pulang',
            self::TYPE_BOTH => 'Absen Masuk & Pulang',
            default => (string) $this->correction_type,
        };
    }

    public function isApprovedSupervisor(): bool
    {
        return $this->status === self::STATUS_APPROVED_SUPERVISOR;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
