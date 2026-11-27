<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OvertimeMasterOrder extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';

    public const STATUS_ISSUED = 'ISSUED';

    public const STATUS_CANCELLED = 'CANCELLED';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'period_year',
        'period_month',
        'sequence_number',
        'letter_number',
        'letter_date',
        'signer_name',
        'signer_title',
        'signer_nip',
        'template_body',
        'signed_pdf_path',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'period_month' => 'integer',
            'sequence_number' => 'integer',
            'letter_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function overtimeRequests(): HasMany
    {
        return $this->hasMany(OvertimeRequest::class);
    }

    public function getPeriodLabel(): string
    {
        $monthName = MonthlyPeriodClosing::MONTH_NAMES[$this->period_month] ?? (string) $this->period_month;

        return "{$monthName} {$this->period_year}";
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draf',
            self::STATUS_PENDING_APPROVAL => 'Menunggu Persetujuan',
            self::STATUS_ISSUED => 'Terbit',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => (string) $this->status,
        };
    }

    public function isIssued(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }
}
