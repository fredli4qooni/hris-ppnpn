<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParallelRunReconciliation extends Model
{
    use HasFactory;

    public const TYPE_MATCH = 'MATCH';

    public const TYPE_TIME_DRIFT = 'TIME_DRIFT';

    public const TYPE_STATUS_MISMATCH = 'STATUS_MISMATCH';

    public const TYPE_DIGITAL_MISSING = 'DIGITAL_MISSING';

    public const TYPE_MANUAL_MISSING = 'MANUAL_MISSING';

    public const STATUS_AUTO_MATCHED = 'AUTO_MATCHED';

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_RESOLVED = 'RESOLVED';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'presence_date',
        'manual_check_in',
        'manual_check_out',
        'manual_status',
        'digital_check_in',
        'digital_check_out',
        'digital_status',
        'time_diff_minutes',
        'discrepancy_type',
        'manual_penalty_rate',
        'digital_penalty_rate',
        'penalty_discrepancy_amount',
        'concordance_score',
        'resolution_status',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'presence_date' => 'date',
            'resolved_at' => 'datetime',
            'time_diff_minutes' => 'integer',
            'manual_penalty_rate' => 'float',
            'digital_penalty_rate' => 'float',
            'penalty_discrepancy_amount' => 'float',
            'concordance_score' => 'float',
        ];
    }

    /**
     * Employee relation.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Resolver user relation.
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Determine if discrepancy is resolved.
     */
    public function isResolved(): bool
    {
        return $this->resolution_status === self::STATUS_RESOLVED || $this->resolution_status === self::STATUS_AUTO_MATCHED;
    }

    /**
     * Determine if record is a perfect match.
     */
    public function isMatch(): bool
    {
        return $this->discrepancy_type === self::TYPE_MATCH;
    }

    /**
     * Get human-friendly label for discrepancy type.
     */
    public function getDiscrepancyTypeLabel(): string
    {
        return match ($this->discrepancy_type) {
            self::TYPE_MATCH => 'Selaras (Match)',
            self::TYPE_TIME_DRIFT => 'Deviasi Jam (> 5 Menit)',
            self::TYPE_STATUS_MISMATCH => 'Selisih Status Hadir',
            self::TYPE_DIGITAL_MISSING => 'Digital Tidak Tercatat',
            self::TYPE_MANUAL_MISSING => 'Manual Tidak Tercatat',
            default => $this->discrepancy_type,
        };
    }

    /**
     * Get badge color for Filament / UI.
     */
    public function getDiscrepancyColor(): string
    {
        return match ($this->discrepancy_type) {
            self::TYPE_MATCH => 'success',
            self::TYPE_TIME_DRIFT => 'warning',
            self::TYPE_STATUS_MISMATCH => 'danger',
            self::TYPE_DIGITAL_MISSING, self::TYPE_MANUAL_MISSING => 'info',
            default => 'gray',
        };
    }
}
