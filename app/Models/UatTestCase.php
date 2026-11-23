<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UatTestCase extends Model
{
    use HasFactory;

    protected $table = 'uat_test_cases';

    protected $fillable = [
        'test_case_code',
        'module_group',
        'title',
        'actor_role',
        'scenario_description',
        'expected_result',
        'actual_result',
        'status',
        'severity_if_failed',
        'tested_by',
        'tested_at',
        'notes',
        'execution_duration_ms',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tested_at' => 'datetime',
            'execution_duration_ms' => 'integer',
        ];
    }

    /**
     * Scope for passed test cases.
     *
     * @param  Builder<UatTestCase>  $query
     * @return Builder<UatTestCase>
     */
    public function scopePassed(Builder $query): Builder
    {
        return $query->where('status', 'passed');
    }

    /**
     * Scope for failed test cases.
     *
     * @param  Builder<UatTestCase>  $query
     * @return Builder<UatTestCase>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope filter by module group.
     *
     * @param  Builder<UatTestCase>  $query
     * @return Builder<UatTestCase>
     */
    public function scopeModule(Builder $query, string $module): Builder
    {
        return $query->where('module_group', $module);
    }
}
