<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OvertimeRequest extends Model
{
    use HasFactory;

    public const TYPE_WITH_LETTER = 'WITH_LETTER';

    public const TYPE_WITHOUT_LETTER = 'WITHOUT_LETTER';

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_SUBMITTED = 'SUBMITTED';

    public const STATUS_RETURNED = 'RETURNED';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const DAY_TYPE_WORKDAY = 'WORKDAY';

    public const DAY_TYPE_HOLIDAY = 'HOLIDAY';

    public const DAY_TYPE_MIXED = 'MIXED';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'request_type',
        'overtime_master_order_id',
        'letter_year',
        'sequence_number',
        'letter_number',
        'draft_code',
        'start_date',
        'end_date',
        'letter_date',
        'day_type',
        'signer_name',
        'signer_title',
        'signer_nip',
        'attachment_pdf_path',
        'signed_pdf_path',
        'imported_pdf_summary',
        'status',
        'proposer_id',
        'approver_id',
        'submitted_at',
        'approved_at',
        'returned_at',
        'rejected_at',
        'cancelled_at',
        'approver_notes',
        'cancellation_reason',
        'replaces_overtime_request_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'letter_year' => 'integer',
            'sequence_number' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'letter_date' => 'date',
            'imported_pdf_summary' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'returned_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function masterOrder(): BelongsTo
    {
        return $this->belongsTo(OvertimeMasterOrder::class, 'overtime_master_order_id');
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposer_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function replacesRequest(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_overtime_request_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OvertimeRequestItem::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_RETURNED], true);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draf',
            self::STATUS_SUBMITTED => 'Diajukan',
            self::STATUS_RETURNED => 'Dikembalikan',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => (string) $this->status,
        };
    }

    public function getTypeLabel(): string
    {
        return $this->request_type === self::TYPE_WITH_LETTER
            ? 'Surat Tugas (SPTPL)'
            : 'Tanpa Surat';
    }

    public function getDisplayCode(): string
    {
        return $this->letter_number ?: $this->draft_code;
    }

    public function getDateRangeLabel(): string
    {
        if (! $this->start_date) {
            return '-';
        }

        $start = Carbon::parse($this->start_date)->locale('id');
        if (! $this->end_date || $this->start_date->isSameDay($this->end_date)) {
            return $start->translatedFormat('d F Y');
        }

        $end = Carbon::parse($this->end_date)->locale('id');

        return $start->translatedFormat('d M Y').' s.d. '.$end->translatedFormat('d M Y');
    }

    public function getDayCount(): int
    {
        if (! $this->start_date || ! $this->end_date) {
            return 1;
        }

        return max(1, (int) $this->start_date->diffInDays($this->end_date) + 1);
    }
}
