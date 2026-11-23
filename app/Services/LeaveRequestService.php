<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\MonthlyPeriodClosing;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveRequestService
{
    /**
     * Hitung jumlah hari kerja efektif antara dua tanggal (tidak termasuk akhir pekan).
     */
    public function calculateWorkingDays(CarbonInterface $startDate, CarbonInterface $endDate): int
    {
        if ($endDate->lt($startDate)) {
            return 0;
        }

        $workingDays = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            if (! $current->isWeekend()) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Ajukan permohonan cuti / izin baru oleh karyawan (FR-501, FR-502, FR-503).
     *
     * @param  array{leave_type: string, start_date: string, end_date: string, reason: string, attachment_path?: string|null}  $data
     *
     * @throws ValidationException
     */
    public function submit(Employee $employee, array $data): LeaveRequest
    {
        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $endDate = Carbon::parse($data['end_date'])->startOfDay();

        if ($endDate->lt($startDate)) {
            throw ValidationException::withMessages([
                'end_date' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            ]);
        }

        if (MonthlyPeriodClosing::isPeriodLocked($startDate->year, $startDate->month) || MonthlyPeriodClosing::isPeriodLocked($endDate->year, $endDate->month)) {
            throw ValidationException::withMessages([
                'start_date' => 'Periode tanggal yang diajukan berada pada bulan yang telah dikunci (READ ONLY).',
            ]);
        }

        $workingDays = $this->calculateWorkingDays($startDate, $endDate);

        if ($workingDays < 1) {
            throw ValidationException::withMessages([
                'start_date' => 'Rentang tanggal yang dipilih tidak memuat hari kerja efektif.',
            ]);
        }

        // Pembatasan maksimal 3 hari kerja beruntun per pengajuan (Lampiran II.A butir 9–11)
        if ($workingDays > 3) {
            throw ValidationException::withMessages([
                'total_days' => 'Durasi izin/cuti melebihi batas maksimal 3 hari kerja berturut-turut per pengajuan (Lampiran II.A SPK).',
            ]);
        }

        // Validasi kuota cuti tahunan
        if ($data['leave_type'] === LeaveRequest::TYPE_ANNUAL_LEAVE) {
            if ($workingDays > $employee->leave_quota_balance) {
                throw ValidationException::withMessages([
                    'total_days' => "Saldo kuota cuti tahunan tidak mencukupi (Sisa saldo: {$employee->leave_quota_balance} hari, diajukan: {$workingDays} hari).",
                ]);
            }
        }

        // Izin sakit wajib melampirkan surat keterangan dokter
        if ($data['leave_type'] === LeaveRequest::TYPE_SICK_LEAVE && empty($data['attachment_path'])) {
            throw ValidationException::withMessages([
                'attachment' => 'Pengajuan izin sakit wajib melampirkan surat keterangan dokter.',
            ]);
        }

        $hasSupervisor = ! empty($employee->supervisor_id);

        $leaveRequest = DB::transaction(function () use ($employee, $data, $startDate, $endDate, $workingDays, $hasSupervisor): LeaveRequest {
            return LeaveRequest::create([
                'employee_id' => $employee->id,
                'leave_type' => $data['leave_type'],
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_days' => $workingDays,
                'reason' => $data['reason'],
                'attachment_path' => $data['attachment_path'] ?? null,
                'supervisor_id' => $employee->supervisor_id,
                'supervisor_status' => $hasSupervisor
                    ? LeaveRequest::SUPERVISOR_STATUS_PENDING
                    : LeaveRequest::SUPERVISOR_STATUS_VERIFIED,
                'supervisor_notes' => $hasSupervisor ? null : 'Verifikasi otomatis (tidak ada supervisor langsung).',
                'supervisor_action_at' => $hasSupervisor ? null : now(),
                'atasan_id' => $employee->atasan_id,
                'status' => $hasSupervisor
                    ? LeaveRequest::STATUS_PENDING_SUPERVISOR
                    : LeaveRequest::STATUS_PENDING_ATASAN,
            ]);
        });

        app(InAppNotificationService::class)->notifyLeaveRequestSubmitted($leaveRequest);

        return $leaveRequest;
    }

    /**
     * Verifikasi tahap 1 oleh Supervisor Lapangan (FR-504).
     *
     * @throws AuthorizationException
     */
    public function verifyBySupervisor(LeaveRequest $request, User $user, bool $approved, ?string $notes = null): LeaveRequest
    {
        if ($request->status !== LeaveRequest::STATUS_PENDING_SUPERVISOR) {
            throw new \DomainException('Pengajuan ini tidak sedang dalam status menunggu verifikasi supervisor.');
        }

        $isAdmin = $user->hasRole('admin');
        $isAssignedSupervisor = $user->hasRole('supervisor') && $request->supervisor_id === $user->id;

        if (! $isAdmin && ! $isAssignedSupervisor) {
            throw new AuthorizationException('Anda tidak memiliki wewenang untuk memverifikasi permohonan ini.');
        }

        $result = DB::transaction(function () use ($request, $approved, $notes): LeaveRequest {
            if ($approved) {
                $request->update([
                    'supervisor_status' => LeaveRequest::SUPERVISOR_STATUS_VERIFIED,
                    'supervisor_notes' => $notes,
                    'supervisor_action_at' => now(),
                    'status' => LeaveRequest::STATUS_PENDING_ATASAN,
                ]);
            } else {
                $request->update([
                    'supervisor_status' => LeaveRequest::SUPERVISOR_STATUS_REJECTED,
                    'supervisor_notes' => $notes,
                    'supervisor_action_at' => now(),
                    'status' => LeaveRequest::STATUS_REJECTED,
                    'rejected_at' => now(),
                ]);
            }

            return $request->fresh();
        });

        if ($approved) {
            app(InAppNotificationService::class)->notifyLeaveRequestTier1Approved($result);
        } else {
            app(InAppNotificationService::class)->notifyLeaveRequestFinalDecision($result);
        }

        return $result;
    }

    /**
     * Putusan akhir tahap 2 oleh Pejabat Penyetuju / Atasan (FR-505, FR-506).
     *
     * @throws AuthorizationException
     */
    public function approveByAtasan(LeaveRequest $request, User $user, bool $approved, ?string $notes = null): LeaveRequest
    {
        if ($request->status !== LeaveRequest::STATUS_PENDING_ATASAN) {
            throw new \DomainException('Pengajuan ini belum melewati tahap verifikasi supervisor atau sudah selesai.');
        }

        $isAdmin = $user->hasRole('admin');
        $isAtasan = $user->hasRole('atasan');

        if (! $isAdmin && ! $isAtasan) {
            throw new AuthorizationException('Hanya Pejabat Penyetuju (Atasan) atau Admin yang berhak memberikan putusan akhir.');
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        if ($approved && (MonthlyPeriodClosing::isPeriodLocked($startDate->year, $startDate->month) || MonthlyPeriodClosing::isPeriodLocked($endDate->year, $endDate->month))) {
            throw ValidationException::withMessages([
                'period' => 'Pengajuan cuti/izin ini tidak dapat disetujui karena periode bulanan terkait telah dikunci (READ ONLY).',
            ]);
        }

        $result = DB::transaction(function () use ($request, $approved, $notes): LeaveRequest {
            if ($approved) {
                $request->update([
                    'status' => LeaveRequest::STATUS_APPROVED,
                    'atasan_notes' => $notes,
                    'approved_at' => now(),
                ]);

                // Sinkronisasi otomatis ke kalender kehadiran (menghapus status ALPA)
                $this->syncAttendanceRecords($request);

                // Pengurangan kuota saldo cuti tahunan jika jenisnya ANNUAL_LEAVE
                if ($request->leave_type === LeaveRequest::TYPE_ANNUAL_LEAVE) {
                    $request->employee()->decrement('leave_quota_balance', $request->total_days);
                }
            } else {
                $request->update([
                    'status' => LeaveRequest::STATUS_REJECTED,
                    'atasan_notes' => $notes,
                    'rejected_at' => now(),
                ]);
            }

            return $request->fresh();
        });

        app(InAppNotificationService::class)->notifyLeaveRequestFinalDecision($result);

        return $result;
    }

    /**
     * Sinkronisasikan pengajuan izin/cuti yang disetujui ke rekaman kehadiran (FR-506).
     */
    public function syncAttendanceRecords(LeaveRequest $request): void
    {
        $attendanceStatus = match ($request->leave_type) {
            LeaveRequest::TYPE_SICK_LEAVE => Attendance::STATUS_SICK,
            LeaveRequest::TYPE_DUTY_LEAVE => Attendance::STATUS_PERMIT,
            LeaveRequest::TYPE_EARLY_DEPARTURE => Attendance::STATUS_EARLY_LEAVE,
            default => Attendance::STATUS_LEAVE,
        };

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->startOfDay();
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            // Hanya buat rekaman pada hari kerja (Senin s.d. Jumat)
            if (! $current->isWeekend()) {
                Attendance::updateOrCreate(
                    [
                        'employee_id' => $request->employee_id,
                        'date' => $current->toDateString(),
                        'shift_type' => Attendance::SHIFT_NORMAL,
                    ],
                    [
                        'position_id' => $request->employee->position_id ?? 1,
                        'status' => $attendanceStatus,
                        'tl_category' => Attendance::TL_NONE,
                        'deduction_percentage' => 0.00,
                        'late_minutes' => 0,
                        'required_compensation_minutes' => 0,
                        'is_compensated' => true,
                        'notes' => "Disetujui: {$request->getTypeLabel()} (#{$request->id}) - {$request->reason}",
                    ]
                );
            }

            $current->addDay();
        }
    }

    /**
     * Batalkan pengajuan izin/cuti oleh pemohon sebelum diputus.
     *
     * @throws AuthorizationException
     */
    public function cancel(LeaveRequest $request, User $user, ?string $reason = null): LeaveRequest
    {
        if (! $request->canBeCancelledBy($user)) {
            throw new AuthorizationException('Anda tidak dapat membatalkan permohonan ini.');
        }

        $request->update([
            'status' => LeaveRequest::STATUS_CANCELLED,
            'atasan_notes' => $reason ?? 'Dibatalkan sendiri oleh pegawai pemohon.',
        ]);

        return $request->fresh();
    }

    /**
     * Sajikan data kalender ketersediaan regu kerja dan deteksi potensi bentrok staf (FR-503).
     *
     * @return array{
     *     year: int,
     *     month: int,
     *     month_name: string,
     *     days: list<array{date: string, day: int, dayName: string, isWeekend: bool}>,
     *     matrix: list<array{id: int, name: string, code: string, position: string, position_id: int|null, leave_quota: int, days: array<string, array{status: string, label: string, color: string}>}>,
     *     conflicts: list<array{date: string, date_formatted: string, position: string, absent_count: int, total_count: int, percentage: float|int}>,
     *     total_employees: int
     * }
     */
    public function getTeamAvailability(int $year, int $month, ?int $supervisorId = null, ?int $positionId = null): array
    {
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth()->startOfDay();

        // 1. Generate array hari dalam bulan
        $days = [];
        $current = $startOfMonth->copy();
        while ($current->lte($endOfMonth)) {
            $days[] = [
                'date' => $current->toDateString(),
                'day' => $current->day,
                'dayName' => $current->translatedFormat('D'),
                'isWeekend' => $current->isWeekend(),
            ];
            $current->addDay();
        }

        // 2. Ambil data pegawai subordinat
        $employeeQuery = Employee::with(['position', 'user'])
            ->where('is_active', true);

        if ($supervisorId) {
            $employeeQuery->where('supervisor_id', $supervisorId);
        }

        if ($positionId) {
            $employeeQuery->where('position_id', $positionId);
        }

        $employees = $employeeQuery->orderBy('position_id')->orderBy('full_name')->get();

        // 3. Ambil absensi hadir pada bulan ini
        $attendances = Attendance::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->groupBy('employee_id');

        // 4. Ambil pengajuan cuti berstatus APPROVED atau PENDING yang beririsan dengan bulan ini
        $leaveRequests = LeaveRequest::whereIn('employee_id', $employees->pluck('id'))
            ->whereIn('status', [
                LeaveRequest::STATUS_APPROVED,
                LeaveRequest::STATUS_PENDING_SUPERVISOR,
                LeaveRequest::STATUS_PENDING_ATASAN,
            ])
            ->where(function ($q) use ($startOfMonth, $endOfMonth): void {
                $q->whereBetween('start_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                    ->orWhereBetween('end_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                    ->orWhere(function ($sub) use ($startOfMonth, $endOfMonth): void {
                        $sub->where('start_date', '<=', $startOfMonth->toDateString())
                            ->where('end_date', '>=', $endOfMonth->toDateString());
                    });
            })
            ->get()
            ->groupBy('employee_id');

        // 5. Susun matriks ketersediaan dan hitung potensi bentrok per posisi per hari
        $matrix = [];
        $positionStaffCounts = [];

        foreach ($employees as $emp) {
            $posName = $emp->position?->name ?? 'PPNPN';
            $positionStaffCounts[$posName] = ($positionStaffCounts[$posName] ?? 0) + 1;
        }

        $dailyAbsences = [];

        foreach ($employees as $emp) {
            $empRow = [
                'id' => $emp->id,
                'name' => $emp->full_name,
                'code' => $emp->employee_code,
                'position' => $emp->position?->name ?? 'PPNPN',
                'position_id' => $emp->position_id,
                'leave_quota' => $emp->leave_quota_balance,
                'days' => [],
            ];

            $empAttendances = ($attendances[$emp->id] ?? collect())->keyBy(function ($item): string {
                return Carbon::parse($item->date)->toDateString();
            });

            $empLeaves = $leaveRequests[$emp->id] ?? collect();

            foreach ($days as $d) {
                $dateStr = $d['date'];
                $isWeekend = $d['isWeekend'];
                $cellStatus = 'SCHEDULED';
                $cellLabel = 'Jadwal';
                $cellColor = 'slate';

                if ($isWeekend) {
                    $cellStatus = 'WEEKEND';
                    $cellLabel = 'Libur';
                    $cellColor = 'gray';
                } else {
                    $activeLeave = $empLeaves->first(function ($leave) use ($dateStr): bool {
                        $start = Carbon::parse($leave->start_date)->toDateString();
                        $end = Carbon::parse($leave->end_date)->toDateString();

                        return $dateStr >= $start && $dateStr <= $end;
                    });

                    if ($activeLeave) {
                        if ($activeLeave->status === LeaveRequest::STATUS_APPROVED) {
                            $cellStatus = 'LEAVE_APPROVED';
                            $cellLabel = match ($activeLeave->leave_type) {
                                LeaveRequest::TYPE_SICK_LEAVE => 'Sakit',
                                LeaveRequest::TYPE_ANNUAL_LEAVE => 'Cuti',
                                LeaveRequest::TYPE_DUTY_LEAVE => 'Dinas',
                                default => 'Izin',
                            };
                            $cellColor = 'info';
                        } else {
                            $cellStatus = 'LEAVE_PENDING';
                            $cellLabel = 'Menunggu';
                            $cellColor = 'warning';
                        }

                        $posName = $emp->position?->name ?? 'PPNPN';
                        $dailyAbsences[$dateStr][$posName] = ($dailyAbsences[$dateStr][$posName] ?? 0) + 1;
                    } elseif (isset($empAttendances[$dateStr])) {
                        $cellStatus = 'PRESENT';
                        $cellLabel = 'Hadir';
                        $cellColor = 'success';
                    }
                }

                $empRow['days'][$dateStr] = [
                    'status' => $cellStatus,
                    'label' => $cellLabel,
                    'color' => $cellColor,
                ];
            }

            $matrix[] = $empRow;
        }

        // 6. Deteksi bentrok jadwal: personil pada posisi sama absen/pending >= 50% (jika total staff >= 2)
        $conflicts = [];
        foreach ($dailyAbsences as $dateStr => $positionsAbsence) {
            foreach ($positionsAbsence as $posName => $absentCount) {
                $totalStaff = $positionStaffCounts[$posName] ?? 1;
                if ($totalStaff >= 2 && $absentCount >= ceil($totalStaff * 0.5)) {
                    $conflicts[] = [
                        'date' => $dateStr,
                        'date_formatted' => Carbon::parse($dateStr)->translatedFormat('l, d F Y'),
                        'position' => $posName,
                        'absent_count' => $absentCount,
                        'total_count' => $totalStaff,
                        'percentage' => round(($absentCount / $totalStaff) * 100),
                    ];
                }
            }
        }

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'),
            'days' => $days,
            'matrix' => $matrix,
            'conflicts' => $conflicts,
            'total_employees' => $employees->count(),
        ];
    }
}
