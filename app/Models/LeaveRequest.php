<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_ANNUAL_LEAVE = 'ANNUAL_LEAVE';

    public const TYPE_SICK_LEAVE = 'SICK_LEAVE';

    public const TYPE_SPECIAL_LEAVE = 'SPECIAL_LEAVE';

    public const TYPE_DUTY_LEAVE = 'DUTY_LEAVE';

    public const TYPE_EARLY_DEPARTURE = 'EARLY_DEPARTURE';

    public const STATUS_PENDING_SUPERVISOR = 'PENDING_SUPERVISOR';

    public const STATUS_PENDING_ATASAN = 'PENDING_ATASAN';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const SUPERVISOR_STATUS_PENDING = 'PENDING';

    public const SUPERVISOR_STATUS_VERIFIED = 'VERIFIED';

    public const SUPERVISOR_STATUS_REJECTED = 'REJECTED';

    public const SLA_MAX_WORKING_DAYS = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'attachment_path',
        'supervisor_id',
        'supervisor_status',
        'supervisor_notes',
        'supervisor_action_at',
        'atasan_id',
        'status',
        'atasan_notes',
        'approved_at',
        'rejected_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_days' => 'integer',
            'supervisor_action_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    /**
     * Pegawai pemohon izin / cuti.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Supervisor lapangan yang melakukan verifikasi tahap 1.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Pejabat Atasan (Kasubbag Umum/PPK) yang memberikan persetujuan final tahap 2.
     */
    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    /**
     * Label jenis izin / cuti.
     */
    public function getTypeLabel(): string
    {
        return match ($this->leave_type) {
            self::TYPE_ANNUAL_LEAVE => 'Cuti Tahunan',
            self::TYPE_SICK_LEAVE => 'Izin Sakit',
            self::TYPE_SPECIAL_LEAVE => 'Izin di Luar Cuti / Khusus',
            self::TYPE_DUTY_LEAVE => 'Izin Tugas Kedinasan',
            self::TYPE_EARLY_DEPARTURE => 'Izin Pulang Lebih Awal',
            default => $this->leave_type,
        };
    }

    /**
     * Label status utama alur persetujuan.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_SUPERVISOR => 'Menunggu Verifikasi Supervisor',
            self::STATUS_PENDING_ATASAN => 'Menunggu Keputusan Atasan',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => $this->status,
        };
    }

    /**
     * Warna badge status utama.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_SUPERVISOR => 'amber',
            self::STATUS_PENDING_ATASAN => 'info',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Label status verifikasi tahap 1 supervisor.
     */
    public function getSupervisorStatusLabel(): string
    {
        return match ($this->supervisor_status) {
            self::SUPERVISOR_STATUS_PENDING => 'Menunggu Verifikasi',
            self::SUPERVISOR_STATUS_VERIFIED => 'Diverifikasi Faktual',
            self::SUPERVISOR_STATUS_REJECTED => 'Ditolak Supervisor',
            default => $this->supervisor_status,
        };
    }

    /**
     * Warna badge status verifikasi supervisor.
     */
    public function getSupervisorStatusColor(): string
    {
        return match ($this->supervisor_status) {
            self::SUPERVISOR_STATUS_PENDING => 'warning',
            self::SUPERVISOR_STATUS_VERIFIED => 'success',
            self::SUPERVISOR_STATUS_REJECTED => 'danger',
            default => 'gray',
        };
    }

    /**
     * Cek apakah pemohon dapat membatalkan permohonan.
     */
    public function canBeCancelledBy(User $user): bool
    {
        if ($this->employee?->user_id !== $user->id) {
            return false;
        }

        return in_array($this->status, [self::STATUS_PENDING_SUPERVISOR, self::STATUS_PENDING_ATASAN], true);
    }

    /**
     * Cek apakah status permohonan telah disetujui penuh oleh atasan.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Cek apakah status permohonan ditolak.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Cek apakah supervisor ini berhak memverifikasi.
     */
    public function canBeVerifiedBy(User $user): bool
    {
        if ($this->status !== self::STATUS_PENDING_SUPERVISOR) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('supervisor') && $this->supervisor_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Cek apakah atasan ini berhak menyetujui final.
     */
    public function canBeApprovedBy(User $user): bool
    {
        if ($this->status !== self::STATUS_PENDING_ATASAN) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('atasan')) {
            return true;
        }

        return false;
    }

    /**
     * Waktu mulai tahap antrean saat ini.
     */
    public function getCurrentStageStartedAt(): ?Carbon
    {
        if ($this->status === self::STATUS_PENDING_SUPERVISOR) {
            return $this->created_at ? Carbon::parse($this->created_at) : null;
        }

        if ($this->status === self::STATUS_PENDING_ATASAN) {
            return $this->supervisor_action_at
                ? Carbon::parse($this->supervisor_action_at)
                : ($this->created_at ? Carbon::parse($this->created_at) : null);
        }

        return null;
    }

    /**
     * Hitung hari kerja efektif antrean permohonan hingga saat ini (FR-508).
     */
    public function getPendingWorkingDays(?Carbon $referenceDate = null): int
    {
        if (! in_array($this->status, [self::STATUS_PENDING_SUPERVISOR, self::STATUS_PENDING_ATASAN], true)) {
            return 0;
        }

        $startedAt = $this->getCurrentStageStartedAt();
        if (! $startedAt) {
            return 0;
        }

        $now = ($referenceDate ?? Carbon::now())->copy()->startOfDay();
        $start = $startedAt->copy()->startOfDay();

        if ($now->lte($start)) {
            return 0;
        }

        $current = $start->copy()->addDay();
        $workingDays = 0;

        while ($current->lte($now)) {
            if (! $current->isWeekend()) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Cek apakah pengajuan melebihi ambang batas SLA (> 2 hari kerja).
     */
    public function isOverdueSla(?Carbon $referenceDate = null): bool
    {
        return $this->getPendingWorkingDays($referenceDate) > self::SLA_MAX_WORKING_DAYS;
    }

    /**
     * Label status SLA antrean perizinan.
     */
    public function getSlaStatusLabel(?Carbon $referenceDate = null): string
    {
        if (! in_array($this->status, [self::STATUS_PENDING_SUPERVISOR, self::STATUS_PENDING_ATASAN], true)) {
            return 'Selesai';
        }

        $days = $this->getPendingWorkingDays($referenceDate);

        if ($days > self::SLA_MAX_WORKING_DAYS) {
            return "Overdue SLA ({$days} Hari Kerja)";
        }

        if ($days === self::SLA_MAX_WORKING_DAYS) {
            return 'Mendekati Batas (2 Hari)';
        }

        return "Sesuai SLA ({$days} Hari)";
    }

    /**
     * Warna badge status SLA.
     */
    public function getSlaBadgeColor(?Carbon $referenceDate = null): string
    {
        if (! in_array($this->status, [self::STATUS_PENDING_SUPERVISOR, self::STATUS_PENDING_ATASAN], true)) {
            return 'gray';
        }

        $days = $this->getPendingWorkingDays($referenceDate);

        if ($days > self::SLA_MAX_WORKING_DAYS) {
            return 'danger';
        }

        if ($days === self::SLA_MAX_WORKING_DAYS) {
            return 'warning';
        }

        return 'success';
    }
}
