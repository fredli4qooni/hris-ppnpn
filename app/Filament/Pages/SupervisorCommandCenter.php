<?php

namespace App\Filament\Pages;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Position;
use App\Models\User;
use App\Models\ViolationDraft;
use App\Services\LeaveRequestService;
use App\Services\ViolationDraftService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class SupervisorCommandCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static \UnitEnum|string|null $navigationGroup = 'Manajemen Kehadiran & Izin';

    protected static ?string $navigationLabel = 'Dasbor Komando Supervisor';

    protected static ?string $title = 'Dasbor Komando Operasional Supervisor (Command Center)';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.supervisor-command-center';

    public string $selectedDate;

    public string $selectedShift = 'ALL';

    public ?int $selectedPositionId = null;

    public string $activeTab = 'live'; // 'live', 'late', 'queue_fallback', 'queue_leave', 'queue_violation'

    public ?int $rejectingAttendanceId = null;

    public string $fallbackRejectReason = '';

    public ?int $rejectingLeaveId = null;

    public string $leaveRejectReason = '';

    public ?int $dismissingDraftId = null;

    public string $draftDismissReason = '';

    public function mount(?string $date = null, ?string $shift = null, ?int $position_id = null, ?string $tab = null): void
    {
        $this->selectedDate = $date ?? request('date', Carbon::now('Asia/Jakarta')->toDateString());
        $this->selectedShift = $shift ?? request('shift', 'ALL');
        $this->selectedPositionId = $position_id ?? (request('position_id') ? (int) request('position_id') : null);
        $this->activeTab = $tab ?? request('tab', 'live');
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('supervisor') || $user->hasRole('admin');
    }

    /**
     * Get IDs of subordinates under current user's supervision.
     *
     * @return array<int>
     */
    public function getScopedEmployeeIds(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        if ($user->hasRole('admin')) {
            return Employee::query()->where('is_active', true)->pluck('id')->toArray();
        }

        return Employee::query()
            ->where('supervisor_id', $user->id)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Compute live KPI metrics for today.
     *
     * @return array<string, int>
     */
    public function getMetrics(): array
    {
        $employeeIds = $this->getScopedEmployeeIds();
        $totalSubordinates = count($employeeIds);

        if ($totalSubordinates === 0) {
            return [
                'total_subordinates' => 0,
                'on_time' => 0,
                'tolerance' => 0,
                'late' => 0,
                'leave' => 0,
                'alpha' => 0,
                'not_present' => 0,
                'queue_fallback' => 0,
                'queue_leave' => 0,
                'queue_draft' => 0,
                'total_queue' => 0,
            ];
        }

        $attendances = Attendance::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('date', $this->selectedDate)
            ->get();

        $onTime = $attendances->where('status', Attendance::STATUS_ON_TIME)->count();
        $tolerance = $attendances->where('status', Attendance::STATUS_TOLERANCE)->count();
        $late = $attendances->where('status', Attendance::STATUS_LATE_TL)->count();
        $leave = $attendances->whereIn('status', [Attendance::STATUS_LEAVE, Attendance::STATUS_PERMIT, Attendance::STATUS_SICK])->count();
        $alpha = $attendances->where('status', Attendance::STATUS_ALPHA)->count();

        $recordedCount = $attendances->count();
        $notPresent = max(0, $totalSubordinates - $recordedCount);

        $queueFallback = Attendance::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('is_fallback', true)
            ->where('fallback_status', Attendance::FALLBACK_PENDING)
            ->count();

        $queueLeave = LeaveRequest::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('status', LeaveRequest::STATUS_PENDING_SUPERVISOR)
            ->count();

        $queueDraft = ViolationDraft::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('status', ViolationDraft::STATUS_PENDING)
            ->count();

        return [
            'total_subordinates' => $totalSubordinates,
            'on_time' => $onTime,
            'tolerance' => $tolerance,
            'late' => $late,
            'leave' => $leave,
            'alpha' => $alpha,
            'not_present' => $notPresent,
            'queue_fallback' => $queueFallback,
            'queue_leave' => $queueLeave,
            'queue_draft' => $queueDraft,
            'total_queue' => $queueFallback + $queueLeave + $queueDraft,
        ];
    }

    /**
     * Get late attendances today.
     *
     * @return Collection<int, Attendance>
     */
    public function getLateAttendances(): Collection
    {
        $employeeIds = $this->getScopedEmployeeIds();

        if (empty($employeeIds)) {
            return collect();
        }

        return Attendance::with(['employee.position', 'lateExceptions'])
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('date', $this->selectedDate)
            ->where('status', Attendance::STATUS_LATE_TL)
            ->orderByDesc('late_minutes')
            ->get();
    }

    /**
     * Get team roster with attendance status.
     *
     * @return Collection<int, Employee>
     */
    public function getTeamRoster(): Collection
    {
        $employeeIds = $this->getScopedEmployeeIds();

        if (empty($employeeIds)) {
            return collect();
        }

        $query = Employee::with([
            'position',
            'user',
            'attendances' => fn ($q) => $q->whereDate('date', $this->selectedDate),
        ])
            ->whereIn('id', $employeeIds);

        if ($this->selectedPositionId) {
            $query->where('position_id', $this->selectedPositionId);
        }

        $employees = $query->orderBy('full_name')->get();

        if ($this->selectedShift !== 'ALL') {
            $employees = $employees->filter(function (Employee $emp) {
                /** @var Attendance|null $att */
                $att = $emp->attendances->first();
                $shift = $att?->shift_type ?? ($emp->position?->code === 'SATPAM' ? Attendance::SHIFT_SECURITY_DAY : Attendance::SHIFT_NORMAL);

                return $shift === $this->selectedShift;
            });
        }

        return $employees;
    }

    /**
     * Get emergency fallback check-in queue.
     *
     * @return Collection<int, Attendance>
     */
    public function getFallbackQueue(): Collection
    {
        $employeeIds = $this->getScopedEmployeeIds();

        if (empty($employeeIds)) {
            return collect();
        }

        return Attendance::with(['employee.position'])
            ->whereIn('employee_id', $employeeIds)
            ->where('is_fallback', true)
            ->where('fallback_status', Attendance::FALLBACK_PENDING)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get leave requests queue awaiting tier 1 review.
     *
     * @return Collection<int, LeaveRequest>
     */
    public function getLeaveQueue(): Collection
    {
        $employeeIds = $this->getScopedEmployeeIds();

        if (empty($employeeIds)) {
            return collect();
        }

        return LeaveRequest::with(['employee.position'])
            ->whereIn('employee_id', $employeeIds)
            ->where('status', LeaveRequest::STATUS_PENDING_SUPERVISOR)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get violation drafts queue awaiting confirmation.
     *
     * @return Collection<int, ViolationDraft>
     */
    public function getViolationDraftQueue(): Collection
    {
        $employeeIds = $this->getScopedEmployeeIds();

        if (empty($employeeIds)) {
            return collect();
        }

        return ViolationDraft::with(['employee.position', 'catalog'])
            ->whereIn('employee_id', $employeeIds)
            ->where('status', ViolationDraft::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * List of positions for filtering.
     *
     * @return Collection<int, Position>
     */
    public function getPositions(): Collection
    {
        return Position::orderBy('name')->get();
    }

    /**
     * Action: Approve biometric fallback check-in.
     */
    public function approveFallback(int $attendanceId): void
    {
        /** @var User $user */
        $user = Auth::user();

        $attendance = Attendance::whereIn('employee_id', $this->getScopedEmployeeIds())
            ->where('is_fallback', true)
            ->where('fallback_status', Attendance::FALLBACK_PENDING)
            ->findOrFail($attendanceId);

        $attendance->update([
            'fallback_status' => Attendance::FALLBACK_APPROVED,
            'fallback_verified_by' => $user->id,
            'fallback_verified_at' => Carbon::now('Asia/Jakarta'),
            'notes' => 'Disahkan oleh '.$user->name,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'ATTENDANCE_FALLBACK_APPROVED',
            'entity_type' => Attendance::class,
            'entity_id' => $attendance->id,
            'new_values' => [
                'fallback_status' => Attendance::FALLBACK_APPROVED,
                'verified_by' => $user->id,
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        Notification::make()
            ->title('Swafoto Darurat Disetujui')
            ->body("Presensi fallback {$attendance->employee?->full_name} berhasil disahkan.")
            ->success()
            ->send();
    }

    /**
     * Action: Reject biometric fallback check-in.
     */
    public function rejectFallback(int $attendanceId, ?string $reason = null): void
    {
        /** @var User $user */
        $user = Auth::user();

        $attendance = Attendance::whereIn('employee_id', $this->getScopedEmployeeIds())
            ->where('is_fallback', true)
            ->where('fallback_status', Attendance::FALLBACK_PENDING)
            ->findOrFail($attendanceId);

        $notes = $reason ?: ($this->fallbackRejectReason ?: 'Swafoto tidak memenuhi kriteria verifikasi identitas fisik.');

        $attendance->update([
            'fallback_status' => Attendance::FALLBACK_REJECTED,
            'fallback_verified_by' => $user->id,
            'fallback_verified_at' => Carbon::now('Asia/Jakarta'),
            'fallback_notes' => $notes,
            'status' => Attendance::STATUS_ALPHA,
            'notes' => 'Presensi Fallback Ditolak',
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'ATTENDANCE_FALLBACK_REJECTED',
            'entity_type' => Attendance::class,
            'entity_id' => $attendance->id,
            'new_values' => [
                'fallback_status' => Attendance::FALLBACK_REJECTED,
                'reason' => $notes,
                'status' => Attendance::STATUS_ALPHA,
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        $this->rejectingAttendanceId = null;
        $this->fallbackRejectReason = '';

        Notification::make()
            ->title('Swafoto Darurat Ditolak')
            ->body("Presensi fallback {$attendance->employee?->full_name} ditolak dan status diubah menjadi ALPA.")
            ->danger()
            ->send();
    }

    /**
     * Action: Verify Leave Request (Tier 1).
     */
    public function verifyLeave(int $leaveRequestId, bool $approved, ?string $notes = null): void
    {
        /** @var User $user */
        $user = Auth::user();

        $leaveRequest = LeaveRequest::whereIn('employee_id', $this->getScopedEmployeeIds())
            ->where('status', LeaveRequest::STATUS_PENDING_SUPERVISOR)
            ->findOrFail($leaveRequestId);

        $reason = $notes ?: ($approved ? 'Disetujui untuk diteruskan ke Atasan.' : ($this->leaveRejectReason ?: 'Ditolak oleh Supervisor Lapangan.'));

        app(LeaveRequestService::class)->verifyBySupervisor($leaveRequest, $user, $approved, $reason);

        $this->rejectingLeaveId = null;
        $this->leaveRejectReason = '';

        Notification::make()
            ->title($approved ? 'Permohonan Cuti/Izin Disetujui Tahap 1' : 'Permohonan Cuti/Izin Ditolak')
            ->body("Pengajuan {$leaveRequest->employee?->full_name} telah diproses.")
            ->color($approved ? 'success' : 'danger')
            ->send();
    }

    /**
     * Action: Confirm automatic violation draft into active violation.
     */
    public function confirmViolationDraft(int $draftId): void
    {
        /** @var User $user */
        $user = Auth::user();

        $draft = ViolationDraft::whereIn('employee_id', $this->getScopedEmployeeIds())
            ->where('status', ViolationDraft::STATUS_PENDING)
            ->findOrFail($draftId);

        app(ViolationDraftService::class)->confirmDraft($draft, $user);

        Notification::make()
            ->title('Draf Pelanggaran Disahkan')
            ->body("Draf pelanggaran {$draft->employee?->full_name} berhasil disahkan menjadi pelanggaran aktif.")
            ->success()
            ->send();
    }

    /**
     * Action: Dismiss automatic violation draft.
     */
    public function dismissViolationDraft(int $draftId, ?string $reason = null): void
    {
        /** @var User $user */
        $user = Auth::user();

        $draft = ViolationDraft::whereIn('employee_id', $this->getScopedEmployeeIds())
            ->where('status', ViolationDraft::STATUS_PENDING)
            ->findOrFail($draftId);

        $dismissalNotes = $reason ?: ($this->draftDismissReason ?: 'Klarifikasi telah diverifikasi valid oleh Supervisor Lapangan.');

        app(ViolationDraftService::class)->dismissDraft($draft, $user, $dismissalNotes);

        $this->dismissingDraftId = null;
        $this->draftDismissReason = '';

        Notification::make()
            ->title('Draf Pelanggaran Digugurkan')
            ->body("Draf pelanggaran {$draft->employee?->full_name} telah digugurkan dengan alasan sah.")
            ->info()
            ->send();
    }
}
