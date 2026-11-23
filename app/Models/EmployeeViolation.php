<?php

namespace App\Models;

use App\Services\InAppNotificationService;
use App\Services\ViolationSanctionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeViolation extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_CANCELLED = 'CANCELLED';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'violation_catalog_id',
        'supervisor_id',
        'incident_date',
        'notes',
        'evidence_photo_path',
        'points',
        'status',
        'cancellation_reason',
        'cancelled_by_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'points' => 'integer',
        ];
    }

    /**
     * Employee who committed the recorded violation.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Clause definition from official catalog.
     */
    public function catalog(): BelongsTo
    {
        return $this->belongsTo(ViolationCatalog::class, 'violation_catalog_id');
    }

    /**
     * Field supervisor who recorded the violation.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * User who officially cancelled the violation.
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    /**
     * Bootstrap model events to automatically evaluate discipline sanctions.
     */
    protected static function booted(): void
    {
        static::saved(function (EmployeeViolation $violation): void {
            if ($violation->employee) {
                /** @var ViolationSanctionService $service */
                $service = app(ViolationSanctionService::class);
                $service->evaluateAndApplySanction($violation->employee, $violation->incident_date);
            }
        });

        static::created(function (EmployeeViolation $violation): void {
            if ($violation->employee && $violation->status === self::STATUS_ACTIVE) {
                app(InAppNotificationService::class)->notifyViolationRecorded($violation);
            }
        });
    }

    /**
     * Check if violation is active and counts towards sanction points.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if violation has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Officially cancel this violation with audit reason.
     */
    public function cancel(string $reason, int $cancelledById): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancellation_reason' => $reason,
            'cancelled_by_id' => $cancelledById,
        ]);
    }
}
