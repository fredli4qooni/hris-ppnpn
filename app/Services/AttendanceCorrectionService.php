<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\MonthlyPeriodClosing;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceCorrectionService
{
    /**
     * Submit a new attendance correction request for an employee.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function createCorrection(Employee $employee, array $data, ?UploadedFile $proofFile = null): AttendanceCorrection
    {
        $attendanceDate = Carbon::parse($data['attendance_date'])->timezone('Asia/Jakarta');
        $nowWib = Carbon::now('Asia/Jakarta');

        // 1. Cannot submit for future dates
        if ($attendanceDate->isAfter($nowWib->copy()->endOfDay())) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Pengajuan koreksi presensi tidak dapat diajukan untuk tanggal di masa depan.',
            ]);
        }

        // 2. Validate maximum H+2 working days window (FR-210)
        if (! $this->isWithinAllowedWindow($attendanceDate, $nowWib)) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Batas waktu pengajuan koreksi lupa absen maksimal H+2 hari kerja setelah tanggal kejadian.',
            ]);
        }

        // 3. Period closing check
        if (MonthlyPeriodClosing::isPeriodLocked($attendanceDate->year, $attendanceDate->month)) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Periode absensi bulan bersangkutan telah dikunci resmi (READ ONLY).',
            ]);
        }

        // 4. Duplicate pending check
        $existsPending = AttendanceCorrection::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $attendanceDate->toDateString())
            ->where('status', AttendanceCorrection::STATUS_PENDING)
            ->exists();

        if ($existsPending) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Anda telah memiliki pengajuan koreksi yang sedang menunggu verifikasi untuk tanggal ini.',
            ]);
        }

        // 5. Handle file upload if present with strict security sanitization (US-606)
        $proofPath = null;
        if ($proofFile) {
            $proofPath = app(FileUploadSanitizerService::class)->sanitizeAndStore(
                $proofFile,
                'attendance_corrections/proofs',
                'public',
                'proof_file'
            );
        }

        $result = DB::transaction(function () use ($employee, $attendanceDate, $data, $proofPath) {
            $correction = AttendanceCorrection::create([
                'employee_id' => $employee->id,
                'attendance_date' => $attendanceDate->toDateString(),
                'correction_type' => $data['correction_type'] ?? AttendanceCorrection::TYPE_BOTH,
                'suggested_check_in_at' => $data['suggested_check_in_at'] ?? null,
                'suggested_check_out_at' => $data['suggested_check_out_at'] ?? null,
                'work_summary' => $data['work_summary'],
                'proof_document_path' => $proofPath,
                'status' => AttendanceCorrection::STATUS_PENDING,
            ]);

            AuditLog::create([
                'user_id' => $employee->user_id,
                'action' => 'ATTENDANCE_CORRECTION_SUBMITTED',
                'entity_type' => AttendanceCorrection::class,
                'entity_id' => $correction->id,
                'new_values' => [
                    'employee_code' => $employee->employee_code,
                    'attendance_date' => $correction->attendance_date->toDateString(),
                    'correction_type' => $correction->correction_type,
                    'work_summary' => $correction->work_summary,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $correction;
        });

        app(InAppNotificationService::class)->notifyCorrectionSubmitted($result);

        return $result;
    }

    /**
     * Check if the date is within the H+2 working days window.
     */
    public function isWithinAllowedWindow(CarbonInterface $attendanceDate, CarbonInterface $nowWib): bool
    {
        $attDate = Carbon::instance($attendanceDate)->startOfDay();
        $today = Carbon::instance($nowWib)->startOfDay();

        if ($attDate->equalTo($today)) {
            return true;
        }

        // Count working days between attendanceDate and today (excluding weekends)
        $workingDaysPassed = 0;
        $cursor = $attDate->copy()->addDay();

        while ($cursor->lessThanOrEqualTo($today)) {
            if (! $cursor->isWeekend()) {
                $workingDaysPassed++;
            }
            $cursor->addDay();
        }

        return $workingDaysPassed <= 2;
    }

    /**
     * Stage 1: Review by Supervisor.
     */
    public function reviewBySupervisor(
        AttendanceCorrection $correction,
        User $supervisor,
        bool $approved,
        ?string $notes = null
    ): AttendanceCorrection {
        if (! $correction->isPending()) {
            throw new \DomainException('Pengajuan koreksi ini tidak lagi berstatus menunggu verifikasi awal.');
        }

        $now = Carbon::now('Asia/Jakarta');

        $result = DB::transaction(function () use ($correction, $supervisor, $approved, $notes, $now) {
            $correction->update([
                'status' => $approved ? AttendanceCorrection::STATUS_APPROVED_SUPERVISOR : AttendanceCorrection::STATUS_REJECTED,
                'supervisor_id' => $supervisor->id,
                'supervisor_reviewed_at' => $now,
                'supervisor_notes' => $notes,
            ]);

            AuditLog::create([
                'user_id' => $supervisor->id,
                'action' => $approved ? 'ATTENDANCE_CORRECTION_SUPERVISOR_APPROVED' : 'ATTENDANCE_CORRECTION_SUPERVISOR_REJECTED',
                'entity_type' => AttendanceCorrection::class,
                'entity_id' => $correction->id,
                'new_values' => [
                    'status' => $correction->status,
                    'notes' => $notes,
                    'reviewed_at' => $now->toIso8601String(),
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $correction;
        });

        if (! $approved) {
            app(InAppNotificationService::class)->notifyCorrectionFinalDecision($result);
        }

        return $result;
    }

    /**
     * Stage 2: Final Decision by Atasan (Kasubbag Umum/PPK) or Admin.
     */
    public function decideByAtasan(
        AttendanceCorrection $correction,
        User $atasan,
        bool $approved,
        ?string $notes = null
    ): AttendanceCorrection {
        if ($correction->isRejected()) {
            throw new \DomainException('Pengajuan yang telah ditolak tidak dapat diproses lagi.');
        }

        $now = Carbon::now('Asia/Jakarta');

        $result = DB::transaction(function () use ($correction, $atasan, $approved, $notes, $now) {
            $newStatus = $approved ? AttendanceCorrection::STATUS_APPROVED : AttendanceCorrection::STATUS_REJECTED;

            $createdAttendanceId = null;

            if ($approved) {
                $createdAttendanceId = $this->applyCorrectionToAttendance($correction, $atasan);
            }

            $correction->update([
                'status' => $newStatus,
                'atasan_id' => $atasan->id,
                'atasan_decided_at' => $now,
                'atasan_notes' => $notes,
                'created_attendance_id' => $createdAttendanceId ?? $correction->created_attendance_id,
            ]);

            AuditLog::create([
                'user_id' => $atasan->id,
                'action' => $approved ? 'ATTENDANCE_CORRECTION_FINAL_APPROVED' : 'ATTENDANCE_CORRECTION_FINAL_REJECTED',
                'entity_type' => AttendanceCorrection::class,
                'entity_id' => $correction->id,
                'new_values' => [
                    'status' => $newStatus,
                    'notes' => $notes,
                    'attendance_id' => $createdAttendanceId,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $correction;
        });

        app(InAppNotificationService::class)->notifyCorrectionFinalDecision($result);

        return $result;
    }

    /**
     * Apply approved correction by creating or updating Attendance record.
     */
    protected function applyCorrectionToAttendance(AttendanceCorrection $correction, User $decider): int
    {
        $employee = $correction->employee()->with('position')->firstOrFail();
        $dateStr = $correction->attendance_date->toDateString();

        $existing = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $dateStr)
            ->first();

        $defaultIn = $correction->suggested_check_in_at
            ? Carbon::parse($dateStr.' '.$correction->suggested_check_in_at->format('H:i:s'), 'Asia/Jakarta')
            : Carbon::parse($dateStr.' 07:30:00', 'Asia/Jakarta');

        $defaultOut = $correction->suggested_check_out_at
            ? Carbon::parse($dateStr.' '.$correction->suggested_check_out_at->format('H:i:s'), 'Asia/Jakarta')
            : Carbon::parse($dateStr.' 16:30:00', 'Asia/Jakarta');

        if ($existing) {
            $updates = [
                'status' => Attendance::STATUS_ON_TIME,
                'notes' => trim(($existing->notes ?? '').' | Koreksi Lupa Absen Disetujui (Ref #'.$correction->id.')'),
            ];

            if (in_array($correction->correction_type, [AttendanceCorrection::TYPE_CHECK_IN, AttendanceCorrection::TYPE_BOTH])) {
                $updates['check_in_at'] = $defaultIn;
            }

            if (in_array($correction->correction_type, [AttendanceCorrection::TYPE_CHECK_OUT, AttendanceCorrection::TYPE_BOTH])) {
                $updates['check_out_at'] = $defaultOut;
                $updates['check_out_status'] = Attendance::CHECK_OUT_ON_TIME;
            }

            $existing->update($updates);

            return $existing->id;
        }

        // Create new attendance
        $newAttendance = Attendance::create([
            'employee_id' => $employee->id,
            'position_id' => $employee->position_id,
            'office_location_id' => $employee->office_location_id,
            'date' => $dateStr,
            'check_in_at' => in_array($correction->correction_type, [AttendanceCorrection::TYPE_CHECK_IN, AttendanceCorrection::TYPE_BOTH]) ? $defaultIn : null,
            'check_out_at' => in_array($correction->correction_type, [AttendanceCorrection::TYPE_CHECK_OUT, AttendanceCorrection::TYPE_BOTH]) ? $defaultOut : null,
            'status' => Attendance::STATUS_ON_TIME,
            'check_out_status' => in_array($correction->correction_type, [AttendanceCorrection::TYPE_CHECK_OUT, AttendanceCorrection::TYPE_BOTH]) ? Attendance::CHECK_OUT_ON_TIME : null,
            'notes' => 'Koreksi Lupa Absen Disahkan (Ref #'.$correction->id.') oleh '.$decider->name,
        ]);

        return $newAttendance->id;
    }
}
