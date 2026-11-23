<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Early Warning System (EWS) Status Constants (FR-407 / US-504).
     */
    public const EWS_NORMAL = 'NORMAL';

    public const EWS_WARNING = 'WARNING';

    public const EWS_CRITICAL = 'CRITICAL';

    public const EWS_THRESHOLD_WARNING = 60;

    public const EWS_THRESHOLD_CRITICAL = 85;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'employee_code',
        'full_name',
        'gender',
        'phone_number',
        'position_id',
        'supervisor_id',
        'atasan_id',
        'base_salary',
        'leave_quota_balance',
        'photo_path',
        'spk_number',
        'contract_start_date',
        'contract_end_date',
        'contract_value',
        'registered_device_id',
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
            'base_salary' => 'decimal:2',
            'contract_value' => 'decimal:2',
            'leave_quota_balance' => 'integer',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Associated user account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Default job position and schedule.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Direct supervisor.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Higher-level approving atasan (e.g., Kepala Subbagian TU & RT / Kepala Bagian Umum).
     */
    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    /**
     * Registered biometric face template.
     */
    public function faceTemplate(): HasOne
    {
        return $this->hasOne(FaceTemplate::class);
    }

    /**
     * Attendance records for this employee.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Discipline and SPK violation records.
     */
    public function violations(): HasMany
    {
        return $this->hasMany(EmployeeViolation::class);
    }

    /**
     * Official discipline sanctions issued based on SPK point thresholds.
     */
    public function sanctions(): HasMany
    {
        return $this->hasMany(ViolationSanction::class);
    }

    /**
     * Leave and permit requests submitted by this employee.
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Attendance correction (lupa absen) requests submitted by this employee.
     */
    public function attendanceCorrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    /**
     * Check if employee has an enrolled biometric face template.
     */
    public function hasFaceEnrolled(): bool
    {
        return $this->faceTemplate()->exists();
    }

    /**
     * Calculate remaining days until the contract expiration date.
     */
    public function getDaysUntilContractExpiry(?CarbonInterface $referenceDate = null): ?int
    {
        if (! $this->contract_end_date) {
            return null;
        }

        $ref = $referenceDate ? Carbon::instance($referenceDate) : Carbon::now('Asia/Jakarta');

        return (int) $ref->startOfDay()->diffInDays($this->contract_end_date->copy()->startOfDay(), false);
    }

    /**
     * Determine the categorical lifecycle status of the contract based on remaining days.
     */
    public function getContractExpiryStatus(?CarbonInterface $referenceDate = null): string
    {
        $days = $this->getDaysUntilContractExpiry($referenceDate);

        if ($days === null) {
            return 'UNKNOWN';
        }

        if ($days < 0) {
            return 'EXPIRED';
        }

        if ($days <= 7) {
            return 'CRITICAL_7';
        }

        if ($days <= 30) {
            return 'WARNING_30';
        }

        if ($days <= 60) {
            return 'WARNING_60';
        }

        if ($days <= 90) {
            return 'WARNING_90';
        }

        return 'ACTIVE';
    }

    /**
     * Get the badge color corresponding to contract expiry status.
     */
    public function getContractStatusBadgeColor(?CarbonInterface $referenceDate = null): string
    {
        return match ($this->getContractExpiryStatus($referenceDate)) {
            'EXPIRED', 'CRITICAL_7' => 'danger',
            'WARNING_30', 'WARNING_60' => 'warning',
            'WARNING_90' => 'info',
            'ACTIVE' => 'success',
            default => 'gray',
        };
    }

    /**
     * Get human-readable contract status label.
     */
    public function getContractStatusLabel(?CarbonInterface $referenceDate = null): string
    {
        $days = $this->getDaysUntilContractExpiry($referenceDate);

        return match ($this->getContractExpiryStatus($referenceDate)) {
            'EXPIRED' => 'Kedaluwarsa ('.abs($days).' hari lalu)',
            'CRITICAL_7' => 'Kritis (H-'.$days.')',
            'WARNING_30' => 'Perlu Perpanjangan (H-'.$days.')',
            'WARNING_60' => 'Peringatan (H-'.$days.')',
            'WARNING_90' => 'Peringatan Dini (H-'.$days.')',
            'ACTIVE' => 'Aktif ('.$days.' hari)',
            default => 'Tidak Ditentukan',
        };
    }

    /**
     * Extend contract or create an addendum while strictly preserving all historical attendance and violation data.
     */
    public function extendContract(
        string $newSpkNumber,
        CarbonInterface|string $newStartDate,
        CarbonInterface|string $newEndDate,
        ?float $newSalary = null,
        ?float $newContractValue = null,
        ?string $notes = null,
        ?User $actor = null
    ): self {
        $oldValues = [
            'spk_number' => $this->spk_number,
            'contract_start_date' => $this->contract_start_date?->toDateString(),
            'contract_end_date' => $this->contract_end_date?->toDateString(),
            'base_salary' => $this->base_salary,
            'contract_value' => $this->contract_value,
        ];

        $startDate = is_string($newStartDate) ? Carbon::parse($newStartDate) : Carbon::instance($newStartDate);
        $endDate = is_string($newEndDate) ? Carbon::parse($newEndDate) : Carbon::instance($newEndDate);

        $this->update([
            'spk_number' => $newSpkNumber,
            'contract_start_date' => $startDate->toDateString(),
            'contract_end_date' => $endDate->toDateString(),
            'base_salary' => $newSalary ?? $this->base_salary,
            'contract_value' => $newContractValue ?? $this->contract_value,
            'is_active' => true,
        ]);

        AuditLog::create([
            'user_id' => $actor?->id ?? Auth::id(),
            'action' => 'CONTRACT_EXTENDED',
            'entity_type' => self::class,
            'entity_id' => $this->id,
            'old_values' => $oldValues,
            'new_values' => [
                'spk_number' => $this->spk_number,
                'contract_start_date' => $this->contract_start_date?->toDateString(),
                'contract_end_date' => $this->contract_end_date?->toDateString(),
                'base_salary' => $this->base_salary,
                'contract_value' => $this->contract_value,
                'notes' => $notes,
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => Carbon::now('Asia/Jakarta'),
        ]);

        return $this;
    }

    /**
     * Late exceptions / force majeure dispensations submitted by this employee.
     */
    public function lateExceptions(): HasMany
    {
        return $this->hasMany(LateException::class);
    }

    /**
     * Automated violation drafts awaiting supervisor review for this employee.
     */
    public function violationDrafts(): HasMany
    {
        return $this->hasMany(ViolationDraft::class);
    }

    /**
     * Calculate total active violation points accumulated by the employee.
     */
    public function calculateActiveViolationPoints(): int
    {
        return (int) $this->violations()
            ->where('status', EmployeeViolation::STATUS_ACTIVE)
            ->sum('points');
    }

    /**
     * Determine Early Warning System (EWS) status based on active violation points.
     * FR-407: Warning >= 60 points (approaching SP 2C / 70 pts), Critical >= 85 points (approaching SP 3 / 100 pts).
     *
     * @return array{
     *     status: string,
     *     points: int,
     *     label: string,
     *     badge_label: string,
     *     color: string,
     *     is_warning: bool,
     *     is_critical: bool,
     *     is_alert: bool,
     *     description: string
     * }
     */
    public function getEwsStatus(): array
    {
        $points = $this->calculateActiveViolationPoints();

        if ($points >= self::EWS_THRESHOLD_CRITICAL) {
            return [
                'status' => self::EWS_CRITICAL,
                'points' => $points,
                'label' => 'Alarm Merah Kritis (Ambang SP 3 / Pemutusan Kontrak)',
                'badge_label' => 'EWS Kritis (≥ 85)',
                'color' => 'danger',
                'is_warning' => false,
                'is_critical' => true,
                'is_alert' => true,
                'description' => 'Akumulasi saldo sanksi telah mencapai atau melampaui 85 poin. Sangat mendekati ambang 100 poin (SP 3 Pemutusan Kontrak Kerja). Diperlukan tindakan pembinaan formal dan evaluasi intensif segera.',
            ];
        }

        if ($points >= self::EWS_THRESHOLD_WARNING) {
            return [
                'status' => self::EWS_WARNING,
                'points' => $points,
                'label' => 'Peringatan Kuning EWS (Ambang SP 2 Tingkat C)',
                'badge_label' => 'EWS Waspada (≥ 60)',
                'color' => 'warning',
                'is_warning' => true,
                'is_critical' => false,
                'is_alert' => true,
                'description' => 'Akumulasi saldo sanksi telah mencapai 60-84 poin. Mendekati ambang batas SP 2 Tingkat C (potongan honor 10% selama 6 bulan). Atasan disarankan segera melakukan konseling preventif.',
            ];
        }

        return [
            'status' => self::EWS_NORMAL,
            'points' => $points,
            'label' => 'Disiplin Normal (Aman)',
            'badge_label' => 'Normal (< 60)',
            'color' => 'success',
            'is_warning' => false,
            'is_critical' => false,
            'is_alert' => false,
            'description' => 'Saldo poin pelanggaran aktif berada dalam batas toleransi aman di bawah ambang peringatan dini.',
        ];
    }

    /**
     * Scope query to filter employees by Early Warning System (EWS) status.
     */
    public function scopeEwsStatus(Builder $query, string $status): Builder
    {
        $activeStatus = EmployeeViolation::STATUS_ACTIVE;
        $subquery = '(SELECT COALESCE(SUM(points), 0) FROM employee_violations WHERE employee_violations.employee_id = employees.id AND employee_violations.status = "'.$activeStatus.'" AND employee_violations.deleted_at IS NULL)';

        return match ($status) {
            self::EWS_CRITICAL => $query->whereRaw("{$subquery} >= ?", [self::EWS_THRESHOLD_CRITICAL]),
            self::EWS_WARNING => $query->whereRaw("{$subquery} >= ? AND {$subquery} < ?", [self::EWS_THRESHOLD_WARNING, self::EWS_THRESHOLD_CRITICAL]),
            self::EWS_NORMAL => $query->whereRaw("{$subquery} < ?", [self::EWS_THRESHOLD_WARNING]),
            default => $query,
        };
    }
}
