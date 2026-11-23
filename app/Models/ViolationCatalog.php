<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViolationCatalog extends Model
{
    use HasFactory;

    public const CATEGORY_KEHADIRAN_A = 'KEHADIRAN_A';

    public const CATEGORY_KEWAJIBAN_B = 'KEWAJIBAN_B';

    public const CATEGORY_KEDISIPLINAN_C = 'KEDISIPLINAN_C';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category',
        'clause_number',
        'description',
        'points',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Violations recorded referencing this clause.
     */
    public function violations(): HasMany
    {
        return $this->hasMany(EmployeeViolation::class);
    }

    /**
     * Get human-readable Indonesian label for the category.
     */
    public function getCategoryLabel(): string
    {
        return match ($this->category) {
            self::CATEGORY_KEHADIRAN_A => 'Kategori A: Kehadiran',
            self::CATEGORY_KEWAJIBAN_B => 'Kategori B: Kewajiban',
            self::CATEGORY_KEDISIPLINAN_C => 'Kategori C: Kedisiplinan',
            default => $this->category,
        };
    }

    /**
     * Display option title for select inputs.
     */
    public function getSelectTitleAttribute(): string
    {
        return "[{$this->clause_number}] {$this->description} ({$this->points} Poin)";
    }
}
