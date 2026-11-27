<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'name',
        'is_national_holiday',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_national_holiday' => 'boolean',
        ];
    }

    /**
     * Check whether a given date falls on a weekend or configured holiday.
     */
    public static function isNonWorkingDay(CarbonInterface $date): bool
    {
        if ($date->isWeekend()) {
            return true;
        }

        return self::query()
            ->whereDate('date', $date->toDateString())
            ->exists();
    }
}
