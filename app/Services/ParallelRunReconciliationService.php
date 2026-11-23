<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\ParallelRunReconciliation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ParallelRunReconciliationService
{
    /**
     * Evaluate comparison between manual log and digital attendance record (US-801).
     *
     * @return array{
     *     discrepancy_type: string,
     *     time_diff_minutes: int,
     *     concordance_score: float,
     *     manual_penalty_rate: float,
     *     digital_penalty_rate: float,
     *     penalty_discrepancy_amount: float,
     *     resolution_status: string
     * }
     */
    public function evaluatePair(
        ?string $manualIn,
        ?string $manualOut,
        string $manualStatus,
        ?string $digitalIn,
        ?string $digitalOut,
        string $digitalStatus,
        float $salary = 3200000.00
    ): array {
        // Calculate penalty rates based on official rules
        $manualRate = $this->calculatePenaltyRate($manualStatus, $manualIn);
        $digitalRate = $this->calculatePenaltyRate($digitalStatus, $digitalIn);
        $penaltyDiffAmount = abs($manualRate - $digitalRate) / 100 * $salary;

        // 1. Missing in Digital
        if ($manualIn !== null && $digitalIn === null) {
            return [
                'discrepancy_type' => ParallelRunReconciliation::TYPE_DIGITAL_MISSING,
                'time_diff_minutes' => 0,
                'concordance_score' => 0.0,
                'manual_penalty_rate' => $manualRate,
                'digital_penalty_rate' => $digitalRate,
                'penalty_discrepancy_amount' => $penaltyDiffAmount,
                'resolution_status' => ParallelRunReconciliation::STATUS_PENDING,
            ];
        }

        // 2. Missing in Manual
        if ($manualIn === null && $digitalIn !== null) {
            return [
                'discrepancy_type' => ParallelRunReconciliation::TYPE_MANUAL_MISSING,
                'time_diff_minutes' => 0,
                'concordance_score' => 0.0,
                'manual_penalty_rate' => $manualRate,
                'digital_penalty_rate' => $digitalRate,
                'penalty_discrepancy_amount' => $penaltyDiffAmount,
                'resolution_status' => ParallelRunReconciliation::STATUS_PENDING,
            ];
        }

        // 3. Status Mismatch
        if (strtoupper($manualStatus) !== strtoupper($digitalStatus)) {
            return [
                'discrepancy_type' => ParallelRunReconciliation::TYPE_STATUS_MISMATCH,
                'time_diff_minutes' => 0,
                'concordance_score' => 50.0,
                'manual_penalty_rate' => $manualRate,
                'digital_penalty_rate' => $digitalRate,
                'penalty_discrepancy_amount' => $penaltyDiffAmount,
                'resolution_status' => ParallelRunReconciliation::STATUS_PENDING,
            ];
        }

        // 4. Time Drift Evaluation (both present)
        $diffMinutes = 0;
        if ($manualIn && $digitalIn) {
            try {
                $manualCarbon = Carbon::createFromFormat('H:i', substr($manualIn, 0, 5));
                $digitalCarbon = Carbon::createFromFormat('H:i', substr($digitalIn, 0, 5));
                $diffMinutes = abs($manualCarbon->diffInMinutes($digitalCarbon));
            } catch (\Throwable) {
                $diffMinutes = 0;
            }
        }

        if ($diffMinutes > 5) {
            return [
                'discrepancy_type' => ParallelRunReconciliation::TYPE_TIME_DRIFT,
                'time_diff_minutes' => $diffMinutes,
                'concordance_score' => 90.0,
                'manual_penalty_rate' => $manualRate,
                'digital_penalty_rate' => $digitalRate,
                'penalty_discrepancy_amount' => $penaltyDiffAmount,
                'resolution_status' => ParallelRunReconciliation::STATUS_PENDING,
            ];
        }

        // 5. Perfect Match
        return [
            'discrepancy_type' => ParallelRunReconciliation::TYPE_MATCH,
            'time_diff_minutes' => $diffMinutes,
            'concordance_score' => 100.0,
            'manual_penalty_rate' => $manualRate,
            'digital_penalty_rate' => $digitalRate,
            'penalty_discrepancy_amount' => 0.0,
            'resolution_status' => ParallelRunReconciliation::STATUS_AUTO_MATCHED,
        ];
    }

    /**
     * Compute penalty rate based on status and check-in time.
     */
    protected function calculatePenaltyRate(string $status, ?string $checkInTime): float
    {
        $status = strtoupper($status);

        if ($status === 'ALPA' || $status === Attendance::STATUS_ALPHA) {
            return 5.0; // 5% deduction
        }

        if ($status === 'IZIN' || $status === Attendance::STATUS_PERMIT) {
            return 2.5;
        }

        if ($status === 'SAKIT' || $status === Attendance::STATUS_SICK || $status === 'CUTI' || $status === Attendance::STATUS_LEAVE) {
            return 0.0;
        }

        if (! $checkInTime) {
            return 0.0;
        }

        // Standard work entry 07:30
        try {
            $entry = Carbon::createFromFormat('H:i', substr($checkInTime, 0, 5));
            $expected = Carbon::createFromFormat('H:i', '07:30');

            if ($entry->gt($expected)) {
                $lateMinutes = $entry->diffInMinutes($expected);

                // If late <= 15 minutes, standard tolerance applies (0% if replaced)
                if ($lateMinutes <= 15) {
                    return 0.0;
                }

                if ($lateMinutes <= 30) {
                    return 0.5; // TL 1
                }

                if ($lateMinutes <= 60) {
                    return 1.0; // TL 2
                }

                return 1.5; // TL 3
            }
        } catch (\Throwable) {
            return 0.0;
        }

        return 0.0;
    }

    /**
     * Reconcile single record for an employee on a specific date.
     */
    public function reconcileRecord(
        Employee $employee,
        Carbon|string $date,
        array $manualData,
        ?array $digitalData = null
    ): ParallelRunReconciliation {
        $presenceDate = is_string($date) ? Carbon::parse($date)->toDateString() : $date->toDateString();

        $manualIn = $manualData['check_in'] ?? null;
        $manualOut = $manualData['check_out'] ?? null;
        $manualStatus = $manualData['status'] ?? 'HADIR';

        $digitalIn = $digitalData['check_in'] ?? null;
        $digitalOut = $digitalData['check_out'] ?? null;
        $digitalStatus = $digitalData['status'] ?? 'HADIR';

        $salary = (float) ($employee->salary ?? 3200000.00);

        $eval = $this->evaluatePair(
            $manualIn,
            $manualOut,
            $manualStatus,
            $digitalIn,
            $digitalOut,
            $digitalStatus,
            $salary
        );

        return ParallelRunReconciliation::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'presence_date' => $presenceDate,
            ],
            [
                'manual_check_in' => $manualIn,
                'manual_check_out' => $manualOut,
                'manual_status' => $manualStatus,
                'digital_check_in' => $digitalIn,
                'digital_check_out' => $digitalOut,
                'digital_status' => $digitalStatus,
                'time_diff_minutes' => $eval['time_diff_minutes'],
                'discrepancy_type' => $eval['discrepancy_type'],
                'manual_penalty_rate' => $eval['manual_penalty_rate'],
                'digital_penalty_rate' => $eval['digital_penalty_rate'],
                'penalty_discrepancy_amount' => $eval['penalty_discrepancy_amount'],
                'concordance_score' => $eval['concordance_score'],
                'resolution_status' => $eval['resolution_status'],
            ]
        );
    }

    /**
     * Resolve discrepancy with official notes and authorized actor.
     */
    public function resolveDiscrepancy(
        ParallelRunReconciliation $record,
        string $notes,
        User $resolver,
        string $acceptedSource = 'DIGITAL'
    ): ParallelRunReconciliation {
        $oldValues = $record->toArray();

        $record->update([
            'resolution_status' => ParallelRunReconciliation::STATUS_RESOLVED,
            'resolution_notes' => $notes,
            'resolved_by' => $resolver->id,
            'resolved_at' => now(),
            'penalty_discrepancy_amount' => 0.0,
            'concordance_score' => 100.0,
        ]);

        // Record audit trail
        try {
            AuditLog::create([
                'user_id' => $resolver->id,
                'action' => 'PARALLEL_RUN_DISCREPANCY_RESOLVED',
                'entity_type' => ParallelRunReconciliation::class,
                'entity_id' => $record->id,
                'old_values' => json_encode(['status' => $oldValues['resolution_status']]),
                'new_values' => json_encode([
                    'accepted_source' => $acceptedSource,
                    'notes' => $notes,
                    'resolved_by' => $resolver->name,
                ]),
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'user_agent' => request()->userAgent() ?? 'HRIS Service',
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Keep graceful if audit log is disabled or mock
        }

        return $record;
    }

    /**
     * Calculate concordance statistics across all or filtered records.
     *
     * @return array{
     *     total_records: int,
     *     matched_records: int,
     *     drift_records: int,
     *     mismatch_records: int,
     *     missing_records: int,
     *     pending_discrepancies: int,
     *     resolved_discrepancies: int,
     *     concordance_rate: float,
     *     total_penalty_discrepancy_rupiah: float
     * }
     */
    public function getConcordanceStatistics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = ParallelRunReconciliation::query();

        if ($startDate && $endDate) {
            $query->whereBetween('presence_date', [$startDate->toDateString(), $endDate->toDateString()]);
        }

        $records = $query->get();
        $total = $records->count();

        if ($total === 0) {
            return [
                'total_records' => 0,
                'matched_records' => 0,
                'drift_records' => 0,
                'mismatch_records' => 0,
                'missing_records' => 0,
                'pending_discrepancies' => 0,
                'resolved_discrepancies' => 0,
                'concordance_rate' => 100.0,
                'total_penalty_discrepancy_rupiah' => 0.0,
            ];
        }

        $matched = $records->where('discrepancy_type', ParallelRunReconciliation::TYPE_MATCH)->count();
        $drift = $records->where('discrepancy_type', ParallelRunReconciliation::TYPE_TIME_DRIFT)->count();
        $mismatch = $records->where('discrepancy_type', ParallelRunReconciliation::TYPE_STATUS_MISMATCH)->count();
        $missing = $records->whereIn('discrepancy_type', [
            ParallelRunReconciliation::TYPE_DIGITAL_MISSING,
            ParallelRunReconciliation::TYPE_MANUAL_MISSING,
        ])->count();

        $pending = $records->where('resolution_status', ParallelRunReconciliation::STATUS_PENDING)->count();
        $resolved = $records->where('resolution_status', ParallelRunReconciliation::STATUS_RESOLVED)->count();

        $totalPenaltyDiff = (float) $records->where('resolution_status', ParallelRunReconciliation::STATUS_PENDING)->sum('penalty_discrepancy_amount');

        // Target concordance rate: matched + resolved / total
        $effectiveMatches = $matched + $resolved;
        $concordanceRate = round(($effectiveMatches / $total) * 100, 2);

        return [
            'total_records' => $total,
            'matched_records' => $matched,
            'drift_records' => $drift,
            'mismatch_records' => $mismatch,
            'missing_records' => $missing,
            'pending_discrepancies' => $pending,
            'resolved_discrepancies' => $resolved,
            'concordance_rate' => $concordanceRate,
            'total_penalty_discrepancy_rupiah' => $totalPenaltyDiff,
        ];
    }

    /**
     * Generate sample 10-day pilot dataset for active employees with high concordance (> 95%).
     */
    public function seedPilotDataset(Carbon $startDate, int $workDays = 10): Collection
    {
        $employees = Employee::where('is_active', true)->get();

        if ($employees->isEmpty()) {
            $employees = Employee::all();
        }

        $createdRecords = collect();
        $currentDate = $startDate->copy();
        $daysCounted = 0;

        while ($daysCounted < $workDays) {
            // Skip weekends
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();

                continue;
            }

            foreach ($employees as $index => $employee) {
                // Introduce 1 minor drift and 1 status mismatch deterministically for testing resolution
                if ($daysCounted === 2 && $index === 0) {
                    // Time drift of 8 minutes (Manual: 07:25, Digital: 07:33)
                    $rec = $this->reconcileRecord($employee, $currentDate, [
                        'check_in' => '07:25',
                        'check_out' => '17:00',
                        'status' => 'HADIR',
                    ], [
                        'check_in' => '07:33',
                        'check_out' => '17:00',
                        'status' => 'HADIR',
                    ]);
                } elseif ($daysCounted === 5 && $index === 1) {
                    // Status mismatch (Manual: HADIR, Digital: ALPA/Lupa Absen)
                    $rec = $this->reconcileRecord($employee, $currentDate, [
                        'check_in' => '07:28',
                        'check_out' => '17:05',
                        'status' => 'HADIR',
                    ], [
                        'check_in' => null,
                        'check_out' => null,
                        'status' => 'ALPA',
                    ]);
                } else {
                    // Perfect match
                    $in = sprintf('07:%02d', 20 + ($index % 8));
                    $rec = $this->reconcileRecord($employee, $currentDate, [
                        'check_in' => $in,
                        'check_out' => '17:00',
                        'status' => 'HADIR',
                    ], [
                        'check_in' => $in,
                        'check_out' => '17:00',
                        'status' => 'HADIR',
                    ]);
                }

                $createdRecords->push($rec);
            }

            $currentDate->addDay();
            $daysCounted++;
        }

        return $createdRecords;
    }
}
