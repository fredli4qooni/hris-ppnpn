<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyPeriodClosing extends Model
{
    use HasFactory;

    /**
     * Indonesian month names.
     *
     * @var array<int, string>
     */
    public const MONTH_NAMES = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'period_year',
        'period_month',
        'is_locked',
        'closed_by_admin_id',
        'closed_at',
        'total_employees_processed',
        'total_base_salary',
        'total_deductions_amount',
        'total_net_salary',
        'notes',
        'reopened_by_admin_id',
        'reopened_at',
        'reopen_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'period_month' => 'integer',
            'is_locked' => 'boolean',
            'closed_at' => 'datetime',
            'total_employees_processed' => 'integer',
            'total_base_salary' => 'decimal:2',
            'total_deductions_amount' => 'decimal:2',
            'total_net_salary' => 'decimal:2',
            'reopened_at' => 'datetime',
        ];
    }

    /**
     * Admin user who locked/closed this period.
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_admin_id');
    }

    /**
     * Super Admin user who re-opened this period.
     */
    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by_admin_id');
    }

    /**
     * Determine if a specific calendar year and month is locked.
     */
    public static function isPeriodLocked(int $year, int $month): bool
    {
        return static::where('period_year', $year)
            ->where('period_month', $month)
            ->where('is_locked', true)
            ->exists();
    }

    /**
     * Get the closing record for a specific calendar year and month.
     */
    public static function getPeriod(int $year, int $month): ?self
    {
        return static::where('period_year', $year)
            ->where('period_month', $month)
            ->first();
    }

    /**
     * Get human-readable Indonesian period label (e.g., "November 2026").
     */
    public function getPeriodLabel(): string
    {
        $monthName = self::MONTH_NAMES[$this->period_month] ?? (string) $this->period_month;

        return "{$monthName} {$this->period_year}";
    }
}
