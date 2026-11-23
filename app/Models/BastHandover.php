<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BastHandover extends Model
{
    use HasFactory;

    protected $table = 'bast_handovers';

    protected $fillable = [
        'bast_number',
        'contract_number',
        'project_name',
        'work_unit',
        'handover_date',
        'first_party_name',
        'first_party_title',
        'first_party_signed_at',
        'second_party_name',
        'second_party_title',
        'second_party_nip',
        'second_party_signed_at',
        'total_sprints',
        'total_story_points',
        'total_test_cases',
        'uat_pass_rate',
        'concordance_rate',
        'status',
        'sign_notes',
        'checklist_items',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'handover_date' => 'date',
            'first_party_signed_at' => 'datetime',
            'second_party_signed_at' => 'datetime',
            'total_sprints' => 'integer',
            'total_story_points' => 'integer',
            'total_test_cases' => 'integer',
            'uat_pass_rate' => 'decimal:2',
            'concordance_rate' => 'decimal:2',
            'checklist_items' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isFullySigned(): bool
    {
        return ! empty($this->first_party_signed_at) && ! empty($this->second_party_signed_at);
    }

    public function signAsFirstParty(): void
    {
        $this->first_party_signed_at = Carbon::now('Asia/Jakarta');
        if (! empty($this->second_party_signed_at)) {
            $this->status = 'OFFICIALLY_SIGNED';
        } else {
            $this->status = 'PARTIALLY_SIGNED';
        }
        $this->save();
    }

    public function signAsSecondParty(): void
    {
        $this->second_party_signed_at = Carbon::now('Asia/Jakarta');
        if (! empty($this->first_party_signed_at)) {
            $this->status = 'OFFICIALLY_SIGNED';
        } else {
            $this->status = 'PARTIALLY_SIGNED';
        }
        $this->save();
    }
}
