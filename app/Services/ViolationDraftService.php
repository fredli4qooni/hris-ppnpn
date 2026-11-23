<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeViolation;
use App\Models\MonthlyPeriodClosing;
use App\Models\User;
use App\Models\ViolationCatalog;
use App\Models\ViolationDraft;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ViolationDraftService
{
    /**
     * Scan attendance and discipline records to automatically generate violation drafts (FR-406 / US-503).
     */
    public function scanAndGenerateDrafts(?CarbonInterface $referenceDate = null): int
    {
        $refDate = $referenceDate ? Carbon::instance($referenceDate)->timezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');
        $createdCount = 0;

        // Retrieve catalog clauses
        $clauseAlpha = ViolationCatalog::where('clause_number', 'A12')->first() ?? ViolationCatalog::where('points', 20)->first();
        $clauseLupaAbsen = ViolationCatalog::where('clause_number', 'A14')->first() ?? ViolationCatalog::where('points', 3)->first();
        $clausePsw = ViolationCatalog::where('clause_number', 'A5')->first() ?? ViolationCatalog::where('clause_number', 'A2')->first();

        /** @var Collection<int, Employee> $employees */
        $employees = Employee::where('is_active', true)->get();

        foreach ($employees as $employee) {
            // TRIGGER 1: Consecutive Alpa (>= 2 days)
            $createdCount += $this->scanConsecutiveAlpha($employee, $refDate, $clauseAlpha);

            // TRIGGER 2: Rejected Attendance Corrections
            $createdCount += $this->scanRejectedAttendanceCorrections($employee, $clauseLupaAbsen ?? $clauseAlpha);

            // TRIGGER 3: Rejected Fallback Check-ins
            $createdCount += $this->scanRejectedFallbacks($employee, $clauseLupaAbsen ?? $clauseAlpha);

            // TRIGGER 4: Repeated PSW (>= 3 times in month)
            $createdCount += $this->scanRepeatedPsw($employee, $refDate, $clausePsw);
        }

        return $createdCount;
    }

    /**
     * Detect consecutive Alpa (>= 2 days) for an employee.
     */
    protected function scanConsecutiveAlpha(Employee $employee, Carbon $refDate, ?ViolationCatalog $clause): int
    {
        $startDate = $refDate->copy()->subDays(30)->startOfDay();
        $endDate = $refDate->copy()->endOfDay();

        $alpaAttendances = Attendance::query()
            ->where('employee_id', $employee->id)
            ->where('status', Attendance::STATUS_ALPHA)
            ->whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $endDate->toDateString())
            ->orderBy('date', 'asc')
            ->get();

        if ($alpaAttendances->count() < 2) {
            return 0;
        }

        $created = 0;
        $currentStreak = [];

        foreach ($alpaAttendances as $att) {
            $attDate = Carbon::parse($att->date)->timezone('Asia/Jakarta')->startOfDay();

            if (empty($currentStreak)) {
                $currentStreak[] = $attDate;

                continue;
            }

            $lastDate = end($currentStreak);
            // Check if dates are consecutive (skipping weekend)
            $cursor = $lastDate->copy()->addDay();
            while ($cursor->isWeekend()) {
                $cursor->addDay();
            }

            if ($cursor->equalTo($attDate)) {
                $currentStreak[] = $attDate;
            } else {
                if (count($currentStreak) >= 2) {
                    $created += $this->createAlphaDraftIfMissing($employee, $currentStreak, $clause);
                }
                $currentStreak = [$attDate];
            }
        }

        if (count($currentStreak) >= 2) {
            $created += $this->createAlphaDraftIfMissing($employee, $currentStreak, $clause);
        }

        return $created;
    }

    /**
     * Create consecutive alpa draft if not already drafted.
     *
     * @param  list<Carbon>  $streak
     */
    protected function createAlphaDraftIfMissing(Employee $employee, array $streak, ?ViolationCatalog $clause): int
    {
        $latestDate = end($streak)->toDateString();
        $count = count($streak);

        $exists = ViolationDraft::query()
            ->where('employee_id', $employee->id)
            ->where('trigger_type', ViolationDraft::TRIGGER_CONSECUTIVE_ALPHA)
            ->whereDate('incident_date', $latestDate)
            ->exists();

        if ($exists) {
            return 0;
        }

        $datesFormatted = implode(', ', array_map(fn ($d) => $d->format('d/m/Y'), $streak));

        ViolationDraft::create([
            'employee_id' => $employee->id,
            'violation_catalog_id' => $clause?->id,
            'trigger_type' => ViolationDraft::TRIGGER_CONSECUTIVE_ALPHA,
            'incident_date' => $latestDate,
            'auto_generated_notes' => "Terdeteksi Alpa berturut-turut sebanyak {$count} hari kerja ({$datesFormatted}) tanpa pengajuan cuti/izin sah.",
            'evidence_data' => [
                'consecutive_days' => $count,
                'dates' => array_map(fn ($d) => $d->toDateString(), $streak),
            ],
            'status' => ViolationDraft::STATUS_PENDING,
        ]);

        return 1;
    }

    /**
     * Detect rejected attendance corrections and create draft.
     */
    protected function scanRejectedAttendanceCorrections(Employee $employee, ?ViolationCatalog $clause): int
    {
        $rejectedCorrections = AttendanceCorrection::query()
            ->where('employee_id', $employee->id)
            ->where('status', AttendanceCorrection::STATUS_REJECTED)
            ->get();

        $created = 0;

        foreach ($rejectedCorrections as $corr) {
            $incidentDate = $corr->attendance_date->toDateString();

            $exists = ViolationDraft::query()
                ->where('employee_id', $employee->id)
                ->where('trigger_type', ViolationDraft::TRIGGER_REJECTED_ATTENDANCE_CORRECTION)
                ->whereDate('incident_date', $incidentDate)
                ->exists();

            if (! $exists) {
                $notes = "Pengajuan koreksi lupa absen tanggal {$corr->attendance_date->translatedFormat('d M Y')} ditolak.";
                if ($corr->atasan_notes) {
                    $notes .= " Catatan Penolakan: \"{$corr->atasan_notes}\"";
                } elseif ($corr->supervisor_notes) {
                    $notes .= " Catatan Penolakan: \"{$corr->supervisor_notes}\"";
                }

                ViolationDraft::create([
                    'employee_id' => $employee->id,
                    'violation_catalog_id' => $clause?->id,
                    'trigger_type' => ViolationDraft::TRIGGER_REJECTED_ATTENDANCE_CORRECTION,
                    'incident_date' => $incidentDate,
                    'auto_generated_notes' => $notes,
                    'evidence_data' => [
                        'correction_id' => $corr->id,
                        'attendance_date' => $incidentDate,
                        'work_summary' => $corr->work_summary,
                    ],
                    'status' => ViolationDraft::STATUS_PENDING,
                ]);

                $created++;
            }
        }

        return $created;
    }

    /**
     * Detect rejected fallback check-ins and create draft.
     */
    protected function scanRejectedFallbacks(Employee $employee, ?ViolationCatalog $clause): int
    {
        $rejectedFallbacks = Attendance::query()
            ->where('employee_id', $employee->id)
            ->where('is_fallback', true)
            ->where('fallback_status', Attendance::FALLBACK_REJECTED)
            ->get();

        $created = 0;

        foreach ($rejectedFallbacks as $att) {
            $incidentDate = $att->date->toDateString();

            $exists = ViolationDraft::query()
                ->where('employee_id', $employee->id)
                ->where('trigger_type', ViolationDraft::TRIGGER_REJECTED_FALLBACK)
                ->whereDate('incident_date', $incidentDate)
                ->exists();

            if (! $exists) {
                $notes = "Presensi darurat swafoto (fallback) tanggal {$att->date->translatedFormat('d M Y')} ditolak oleh Supervisor Lapangan.";
                if ($att->fallback_notes) {
                    $notes .= " Alasan Penolakan: \"{$att->fallback_notes}\"";
                }

                ViolationDraft::create([
                    'employee_id' => $employee->id,
                    'violation_catalog_id' => $clause?->id,
                    'trigger_type' => ViolationDraft::TRIGGER_REJECTED_FALLBACK,
                    'incident_date' => $incidentDate,
                    'auto_generated_notes' => $notes,
                    'evidence_data' => [
                        'attendance_id' => $att->id,
                        'attendance_date' => $incidentDate,
                        'fallback_reason' => $att->fallback_reason,
                        'photo_path' => $att->check_in_photo_path,
                    ],
                    'status' => ViolationDraft::STATUS_PENDING,
                ]);

                $created++;
            }
        }

        return $created;
    }

    /**
     * Detect repeated PSW (>= 3 times in month) and create draft.
     */
    protected function scanRepeatedPsw(Employee $employee, Carbon $refDate, ?ViolationCatalog $clause): int
    {
        $year = $refDate->year;
        $month = $refDate->month;

        $pswAttendances = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where(function ($q) {
                $q->where('check_out_status', Attendance::CHECK_OUT_PSW)
                    ->orWhere('early_leave_minutes', '>', 0);
            })
            ->orderBy('date', 'desc')
            ->get();

        $count = $pswAttendances->count();
        if ($count < 3) {
            return 0;
        }

        $latestDate = $pswAttendances->first()->date->toDateString();

        $exists = ViolationDraft::query()
            ->where('employee_id', $employee->id)
            ->where('trigger_type', ViolationDraft::TRIGGER_REPEATED_PSW)
            ->whereYear('incident_date', $year)
            ->whereMonth('incident_date', $month)
            ->exists();

        if ($exists) {
            return 0;
        }

        $monthName = MonthlyPeriodClosing::MONTH_NAMES[$month] ?? $refDate->translatedFormat('F');

        ViolationDraft::create([
            'employee_id' => $employee->id,
            'violation_catalog_id' => $clause?->id,
            'trigger_type' => ViolationDraft::TRIGGER_REPEATED_PSW,
            'incident_date' => $latestDate,
            'auto_generated_notes' => "Terdeteksi Pulang Sebelum Waktu (PSW) berulang sebanyak {$count} kali dalam bulan {$monthName} {$year} tanpa izin dinas sah.",
            'evidence_data' => [
                'psw_count' => $count,
                'month' => $month,
                'year' => $year,
            ],
            'status' => ViolationDraft::STATUS_PENDING,
        ]);

        return 1;
    }

    /**
     * Supervisor confirms draft into an active EmployeeViolation.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function confirmDraft(ViolationDraft $draft, User $supervisor, array $data = []): EmployeeViolation
    {
        if ($draft->status !== ViolationDraft::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => 'Draf pelanggaran ini sudah ditindaklanjuti dan tidak berstatus pending.',
            ]);
        }

        return DB::transaction(function () use ($draft, $supervisor, $data) {
            $catalogId = $data['violation_catalog_id'] ?? $draft->violation_catalog_id;
            /** @var ViolationCatalog $catalog */
            $catalog = ViolationCatalog::findOrFail($catalogId);

            $incidentDate = $data['incident_date'] ?? $draft->incident_date;
            $notes = $data['notes'] ?? $draft->auto_generated_notes;

            // Create active EmployeeViolation
            $violation = EmployeeViolation::create([
                'employee_id' => $draft->employee_id,
                'violation_catalog_id' => $catalog->id,
                'supervisor_id' => $supervisor->id,
                'incident_date' => $incidentDate,
                'notes' => $notes,
                'evidence_photo_path' => $data['evidence_photo_path'] ?? null,
                'points' => $catalog->points,
                'status' => EmployeeViolation::STATUS_ACTIVE,
            ]);

            // Update Draft
            $draft->update([
                'status' => ViolationDraft::STATUS_CONFIRMED,
                'confirmed_by_id' => $supervisor->id,
                'confirmed_at' => Carbon::now('Asia/Jakarta'),
                'employee_violation_id' => $violation->id,
            ]);

            AuditLog::create([
                'user_id' => $supervisor->id,
                'action' => 'VIOLATION_DRAFT_CONFIRMED',
                'entity_type' => ViolationDraft::class,
                'entity_id' => $draft->id,
                'new_values' => [
                    'employee_violation_id' => $violation->id,
                    'catalog_id' => $catalog->id,
                    'points' => $catalog->points,
                    'confirmed_by' => $supervisor->id,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $violation;
        });
    }

    /**
     * Supervisor dismisses draft with official reason.
     *
     * @throws ValidationException
     */
    public function dismissDraft(ViolationDraft $draft, User $supervisor, string $reason): ViolationDraft
    {
        if ($draft->status !== ViolationDraft::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => 'Draf pelanggaran ini sudah ditindaklanjuti dan tidak berstatus pending.',
            ]);
        }

        return DB::transaction(function () use ($draft, $supervisor, $reason) {
            $draft->update([
                'status' => ViolationDraft::STATUS_DISMISSED,
                'dismissed_by_id' => $supervisor->id,
                'dismissed_at' => Carbon::now('Asia/Jakarta'),
                'dismissal_reason' => $reason,
            ]);

            AuditLog::create([
                'user_id' => $supervisor->id,
                'action' => 'VIOLATION_DRAFT_DISMISSED',
                'entity_type' => ViolationDraft::class,
                'entity_id' => $draft->id,
                'new_values' => [
                    'dismissed_by' => $supervisor->id,
                    'reason' => $reason,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $draft;
        });
    }
}
