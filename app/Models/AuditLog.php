<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * Boot the model.
     * Enforce strict immutability (FR-902): Audit logs can never be updated or deleted.
     */
    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new \RuntimeException('Rekaman audit log bersifat permanen dan tidak dapat diubah (Immutable FR-902).');
        });

        static::deleting(function (): void {
            throw new \RuntimeException('Rekaman audit log bersifat permanen dan tidak dapat dihapus (Immutable FR-902).');
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * The user who triggered the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
