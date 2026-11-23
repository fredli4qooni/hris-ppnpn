<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LateException;
use App\Models\MonthlyPeriodClosing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LateExceptionService
{
    /**
     * Submit a new late exception / force majeure dispensation request (FR-303 / US-502).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function createException(Employee $employee, array $data, UploadedFile $proofFile): LateException
    {
        $incidentDate = Carbon::parse($data['incident_date'])->timezone('Asia/Jakarta');
        $nowWib = Carbon::now('Asia/Jakarta');

        // 1. Prevent future dates
        if ($incidentDate->isAfter($nowWib->copy()->endOfDay())) {
            throw ValidationException::withMessages([
                'incident_date' => 'Pengajuan dispensasi keterlambatan kahar tidak dapat diajukan untuk tanggal di masa depan.',
            ]);
        }

        // 2. Prevent submission if payroll period is locked
        if (MonthlyPeriodClosing::isPeriodLocked($incidentDate->year, $incidentDate->month)) {
            throw ValidationException::withMessages([
                'incident_date' => 'Periode absensi bulan bersangkutan telah dikunci resmi (READ ONLY).',
            ]);
        }

        // 3. Find linked attendance if exists
        $attendance = null;
        if (! empty($data['attendance_id'])) {
            $attendance = Attendance::where('employee_id', $employee->id)->find($data['attendance_id']);
        }
        if (! $attendance) {
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $incidentDate->toDateString())
                ->first();
        }

        // 4. Duplicate pending check
        $existsPending = LateException::query()
            ->where('employee_id', $employee->id)
            ->whereDate('incident_date', $incidentDate->toDateString())
            ->whereIn('status', [LateException::STATUS_PENDING, LateException::STATUS_APPROVED_SUPERVISOR])
            ->exists();

        if ($existsPending) {
            throw ValidationException::withMessages([
                'incident_date' => 'Anda telah memiliki pengajuan dispensasi yang sedang dalam proses verifikasi untuk tanggal ini.',
            ]);
        }

        // 5. Store proof file with strict security sanitization & EXIF stripping (US-606)
        $proofPath = app(FileUploadSanitizerService::class)->sanitizeAndStore(
            $proofFile,
            'late_exceptions/proofs',
            'public',
            'proof_file'
        );

        $result = DB::transaction(function () use ($employee, $attendance, $incidentDate, $data, $proofPath) {
            $exception = LateException::create([
                'employee_id' => $employee->id,
                'attendance_id' => $attendance?->id,
                'incident_date' => $incidentDate->toDateString(),
                'category' => $data['category'] ?? LateException::CATEGORY_OTHER,
                'incident_time' => $data['incident_time'] ?? null,
                'description' => $data['description'],
                'proof_file_path' => $proofPath,
                'status' => LateException::STATUS_PENDING,
            ]);

            AuditLog::create([
                'user_id' => $employee->user_id,
                'action' => 'LATE_EXCEPTION_SUBMITTED',
                'entity_type' => LateException::class,
                'entity_id' => $exception->id,
                'new_values' => [
                    'employee_code' => $employee->employee_code,
                    'incident_date' => $exception->incident_date->toDateString(),
                    'category' => $exception->category,
                    'attendance_id' => $exception->attendance_id,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $exception;
        });

        app(InAppNotificationService::class)->notifyLateExceptionSubmitted($result);

        return $result;
    }

    /**
     * Stage 1: Review by Supervisor (Field Fact-checking).
     *
     * @throws ValidationException
     */
    public function reviewBySupervisor(
        LateException $exception,
        User $supervisor,
        bool $isApproved,
        string $notes
    ): LateException {
        if ($exception->status !== LateException::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => 'Pengajuan dispensasi ini sudah tidak berada dalam tahap telaah supervisor.',
            ]);
        }

        $nowWib = Carbon::now('Asia/Jakarta');
        $newStatus = $isApproved ? LateException::STATUS_APPROVED_SUPERVISOR : LateException::STATUS_REJECTED_SUPERVISOR;

        $result = DB::transaction(function () use ($exception, $supervisor, $newStatus, $notes, $nowWib) {
            $exception->update([
                'status' => $newStatus,
                'supervisor_id' => $supervisor->id,
                'supervisor_reviewed_at' => $nowWib,
                'supervisor_notes' => $notes,
            ]);

            AuditLog::create([
                'user_id' => $supervisor->id,
                'action' => 'LATE_EXCEPTION_SUPERVISOR_REVIEW',
                'entity_type' => LateException::class,
                'entity_id' => $exception->id,
                'new_values' => [
                    'status' => $newStatus,
                    'supervisor_id' => $supervisor->id,
                    'notes' => $notes,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $exception;
        });

        if (! $isApproved) {
            app(InAppNotificationService::class)->notifyLateExceptionFinalDecision($result);
        }

        return $result;
    }

    /**
     * Stage 2: Final Decision by Atasan / Kasubbag Umum / PPK.
     *
     * @throws ValidationException
     */
    public function decideByAtasan(
        LateException $exception,
        User $atasan,
        bool $isApproved,
        string $notes
    ): LateException {
        if (! in_array($exception->status, [LateException::STATUS_APPROVED_SUPERVISOR, LateException::STATUS_PENDING], true)) {
            throw ValidationException::withMessages([
                'status' => 'Pengajuan dispensasi ini sudah diputuskan dan tidak dapat diubah.',
            ]);
        }

        $nowWib = Carbon::now('Asia/Jakarta');
        $newStatus = $isApproved ? LateException::STATUS_APPROVED : LateException::STATUS_REJECTED;

        $result = DB::transaction(function () use ($exception, $atasan, $isApproved, $newStatus, $notes, $nowWib) {
            $exception->update([
                'status' => $newStatus,
                'atasan_id' => $atasan->id,
                'atasan_decided_at' => $nowWib,
                'atasan_notes' => $notes,
            ]);

            if ($isApproved) {
                // Find linked attendance and mark as exempted from TL deduction
                $attendance = $exception->attendance;
                if (! $attendance) {
                    $attendance = Attendance::where('employee_id', $exception->employee_id)
                        ->whereDate('date', $exception->incident_date->toDateString())
                        ->first();
                }

                if ($attendance) {
                    $attendance->update([
                        'is_dispensation' => true,
                        'tl_deduction_exempted' => true,
                        'dispensation_notes' => 'Dispensasi Kahar Disetujui ('.$exception->getCategoryLabel().'): '.$notes,
                    ]);
                }
            }

            AuditLog::create([
                'user_id' => $atasan->id,
                'action' => 'LATE_EXCEPTION_ATASAN_DECISION',
                'entity_type' => LateException::class,
                'entity_id' => $exception->id,
                'new_values' => [
                    'status' => $newStatus,
                    'atasan_id' => $atasan->id,
                    'is_approved' => $isApproved,
                    'notes' => $notes,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $exception;
        });

        app(InAppNotificationService::class)->notifyLateExceptionFinalDecision($result);

        return $result;
    }
}
