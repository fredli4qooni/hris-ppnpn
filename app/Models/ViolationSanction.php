<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ViolationSanction extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_TEGURAN_LISAN = 'TEGURAN_LISAN';

    public const TYPE_SP_1 = 'SP_1';

    public const TYPE_SP_2_A = 'SP_2_A';

    public const TYPE_SP_2_B = 'SP_2_B';

    public const TYPE_SP_2_C = 'SP_2_C';

    public const TYPE_SP_3 = 'SP_3';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'sanction_type',
        'total_points_snapshot',
        'deduction_percentage',
        'duration_months',
        'start_date',
        'end_date',
        'issued_date',
        'is_active',
        'is_termination_recommended',
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
            'total_points_snapshot' => 'integer',
            'deduction_percentage' => 'decimal:2',
            'duration_months' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'issued_date' => 'date',
            'is_active' => 'boolean',
            'is_termination_recommended' => 'boolean',
        ];
    }

    /**
     * Employee who received this sanction.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get official Indonesian description for this sanction.
     */
    public function getTypeLabel(): string
    {
        return match ($this->sanction_type) {
            self::TYPE_TEGURAN_LISAN => 'Teguran Lisan',
            self::TYPE_SP_1 => 'Surat Peringatan 1 (SP 1)',
            self::TYPE_SP_2_A => 'Surat Peringatan 2 Tingkat A (SP 2A)',
            self::TYPE_SP_2_B => 'Surat Peringatan 2 Tingkat B (SP 2B)',
            self::TYPE_SP_2_C => 'Surat Peringatan 2 Tingkat C (SP 2C)',
            self::TYPE_SP_3 => 'Surat Peringatan 3 (SP 3 - Rekomendasi Pemutusan Kontrak)',
            default => $this->sanction_type,
        };
    }

    /**
     * Check if this sanction deduction applies to a specific calendar month.
     */
    public function isEffectiveInMonth(int $year, int $month): bool
    {
        if (! $this->is_active || ! $this->start_date || ! $this->end_date) {
            return false;
        }

        $targetStart = Carbon::createFromDate($year, $month, 1, 'Asia/Jakarta')->startOfMonth();
        $targetEnd = $targetStart->copy()->endOfMonth();

        return $this->start_date->lessThanOrEqualTo($targetEnd)
            && $this->end_date->greaterThanOrEqualTo($targetStart);
    }
}
