<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\MonthlyPeriodClosing;
use App\Models\User;
use App\Models\ViolationSanction;
use Carbon\Carbon;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class MonthlyClosingService
{
    public function __construct(
        protected ViolationSanctionService $sanctionService
    ) {}

    /**
     * Calculate complete payroll, attendance, and deduction data for active employees (optionally filtered to one user) for a given month.
     *
     * @return array{
     *     year: int,
     *     month: int,
     *     month_name: string,
     *     period_label: string,
     *     is_locked: bool,
     *     closing: ?MonthlyPeriodClosing,
     *     selected_employee_id: ?int,
     *     employee_options: list<array{
     *         id: int,
     *         employee_code: string,
     *         name: string,
     *         position_name: string
     *     }>,
     *     daily_details: list<array{
     *         date: string,
     *         status: string,
     *         tl_category: string,
     *         check_in_at: string,
     *         check_out_at: string,
     *         late_minutes: int,
     *         deduction_percentage: float,
     *         deduction_amount: float,
     *         is_exempted: bool,
     *         notes: string
     *     }>,
     *     items: list<array{
     *         employee_id: int,
     *         employee_code: string,
     *         name: string,
     *         spk_number: string,
     *         position_code: string,
     *         position_name: string,
     *         base_salary: float,
     *         total_attendance_days: int,
     *         on_time_count: int,
     *         tolerance_count: int,
     *         tolerance_compensated_count: int,
     *         tl_1_count: int,
     *         tl_2_count: int,
     *         tl_3_count: int,
     *         leave_count: int,
     *         alpha_count: int,
     *         accumulation_1_tl_percentage: float,
     *         accumulation_1_tl_amount: float,
     *         accumulation_2_sanction_type: ?string,
     *         accumulation_2_sanction_percentage: float,
     *         accumulation_2_sanction_amount: float,
     *         total_deduction_percentage: float,
     *         total_deduction_amount: float,
     *         net_salary: float,
     *         is_termination_recommended: bool
     *     }>,
     *     totals: array{
     *         total_employees: int,
     *         total_base_salary: float,
     *         total_tl_deduction: float,
     *         total_sp_deduction: float,
     *         total_deductions_amount: float,
     *         total_net_salary: float
     *     }
     * }
     */
    public function getPayrollData(int $year, int $month, ?int $employeeId = null): array
    {
        $closing = MonthlyPeriodClosing::getPeriod($year, $month);
        $isLocked = $closing ? (bool) $closing->is_locked : false;

        $monthName = MonthlyPeriodClosing::MONTH_NAMES[$month] ?? (string) $month;
        $periodLabel = "{$monthName} {$year}";

        /** @var Collection<int, Employee> $allActiveEmployees */
        $allActiveEmployees = Employee::with(['position', 'user'])
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->get();

        $employeeOptions = $allActiveEmployees->map(fn (Employee $emp): array => [
            'id' => $emp->id,
            'employee_code' => $emp->employee_code,
            'name' => $emp->full_name,
            'position_name' => $emp->position?->name ?? 'PPNPN',
        ])->values()->all();

        $employees = $employeeId !== null
            ? $allActiveEmployees->where('id', $employeeId)->values()
            : $allActiveEmployees;

        if ($employeeId !== null && $employees->isEmpty()) {
            $employeeId = null;
            $employees = $allActiveEmployees;
        }

        $items = [];
        $dailyDetails = [];
        $totalBaseSalary = 0.00;
        $totalTlDeduction = 0.00;
        $totalSpDeduction = 0.00;
        $totalDeductionsAmount = 0.00;
        $totalNetSalary = 0.00;

        $startDate = Carbon::createFromDate($year, $month, 1, 'Asia/Jakarta')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $employeeIds = $employees->pluck('id')->all();

        // Batch load attendances for the entire month for all active employees (Optimized: eliminates N+1 queries)
        /** @var Collection<int, Collection<int, Attendance>> $allAttendances */
        $allAttendances = Attendance::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date', 'asc')
            ->get()
            ->groupBy('employee_id');

        // Batch load active sanctions for all active employees (Optimized: eliminates N+1 queries)
        /** @var Collection<int, Collection<int, ViolationSanction>> $allSanctions */
        $allSanctions = ViolationSanction::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('is_active', true)
            ->get()
            ->groupBy('employee_id');

        foreach ($employees as $employee) {
            $baseSalary = (float) ($employee->base_salary ?? 0.00);

            /** @var Collection<int, Attendance> $attendances */
            $attendances = $allAttendances->get($employee->id, collect());

            $onTimeCount = $attendances->where('status', Attendance::STATUS_ON_TIME)->count();
            $toleranceCount = $attendances->where('status', Attendance::STATUS_TOLERANCE)->count();
            $toleranceCompensated = $attendances->where('status', Attendance::STATUS_TOLERANCE)
                ->where('is_compensated', true)
                ->count();
            $tl1Count = $attendances->where('tl_category', Attendance::TL_1)->count();
            $tl2Count = $attendances->where('tl_category', Attendance::TL_2)->count();
            $tl3Count = $attendances->where('tl_category', Attendance::TL_3)->count();
            $leaveCount = $attendances->whereIn('status', [
                Attendance::STATUS_LEAVE,
                Attendance::STATUS_PERMIT,
                Attendance::STATUS_SICK,
            ])->count();
            $alphaCount = $attendances->where('status', Attendance::STATUS_ALPHA)->count();

            // Akumulasi 1 (Lampiran IV SPK: Keterlambatan Masuk TL 1-3, mengecualikan dispensasi kahar / FR-303)
            $tlPercentage = (float) $attendances->where('tl_deduction_exempted', false)->where('is_dispensation', false)->sum('deduction_percentage');
            $tlDeductionAmount = round(($baseSalary * $tlPercentage) / 100, 2);

            // Akumulasi 2 (Lampiran III SPK: Sanksi Poin Pelanggaran dari koleksi yang sudah di-load)
            /** @var Collection<int, ViolationSanction> $empSanctions */
            $empSanctions = $allSanctions->get($employee->id, collect());

            /** @var ViolationSanction|null $activeSanction */
            $activeSanction = $empSanctions
                ->filter(function (ViolationSanction $s) use ($startDate, $endDate): bool {
                    if (! $s->start_date || ! $s->end_date) {
                        return false;
                    }

                    $sStart = Carbon::parse($s->start_date);
                    $sEnd = Carbon::parse($s->end_date);

                    return $sStart->lte($endDate) && $sEnd->gte($startDate);
                })
                ->sortByDesc('deduction_percentage')
                ->first();

            $sanctionPercentage = $activeSanction ? (float) $activeSanction->deduction_percentage : 0.00;
            $sanctionDeductionAmount = round(($baseSalary * $sanctionPercentage) / 100, 2);

            $isTermination = $empSanctions->contains(fn (ViolationSanction $s): bool => (bool) $s->is_termination_recommended);

            // Combined calculations
            $totalPercentage = round($tlPercentage + $sanctionPercentage, 2);
            $empTotalDeduction = round($tlDeductionAmount + $sanctionDeductionAmount, 2);
            $netSalary = max(0.00, round($baseSalary - $empTotalDeduction, 2));

            $totalBaseSalary += $baseSalary;
            $totalTlDeduction += $tlDeductionAmount;
            $totalSpDeduction += $sanctionDeductionAmount;
            $totalDeductionsAmount += $empTotalDeduction;
            $totalNetSalary += $netSalary;

            $items[] = [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'name' => $employee->full_name,
                'spk_number' => $employee->spk_number ?? 'SPK-20/WPB.08/PPK/2025',
                'position_code' => $employee->position?->code ?? '-',
                'position_name' => $employee->position?->name ?? 'PPNPN',
                'base_salary' => $baseSalary,
                'total_attendance_days' => $attendances->count(),
                'on_time_count' => $onTimeCount,
                'tolerance_count' => $toleranceCount,
                'tolerance_compensated_count' => $toleranceCompensated,
                'tl_1_count' => $tl1Count,
                'tl_2_count' => $tl2Count,
                'tl_3_count' => $tl3Count,
                'leave_count' => $leaveCount,
                'alpha_count' => $alphaCount,
                'accumulation_1_tl_percentage' => $tlPercentage,
                'accumulation_1_tl_amount' => $tlDeductionAmount,
                'accumulation_2_sanction_type' => $activeSanction?->sanction_type,
                'accumulation_2_sanction_percentage' => $sanctionPercentage,
                'accumulation_2_sanction_amount' => $sanctionDeductionAmount,
                'total_deduction_percentage' => $totalPercentage,
                'total_deduction_amount' => $empTotalDeduction,
                'net_salary' => $netSalary,
                'is_termination_recommended' => $isTermination,
            ];

            if ($employeeId !== null) {
                $dailyDetails = $attendances->map(function (Attendance $att) use ($baseSalary): array {
                    $isExempted = (bool) ($att->tl_deduction_exempted || $att->is_dispensation);
                    $pct = $isExempted ? 0.0 : (float) $att->deduction_percentage;

                    return [
                        'date' => $att->date ? $att->date->translatedFormat('d M Y') : '-',
                        'status' => (string) $att->status,
                        'tl_category' => (string) $att->tl_category,
                        'check_in_at' => $att->check_in_at ? $att->check_in_at->timezone('Asia/Jakarta')->format('H:i:s').' WIB' : '-',
                        'check_out_at' => $att->check_out_at ? $att->check_out_at->timezone('Asia/Jakarta')->format('H:i:s').' WIB' : '-',
                        'late_minutes' => (int) $att->late_minutes,
                        'deduction_percentage' => $pct,
                        'deduction_amount' => round(($baseSalary * $pct) / 100, 2),
                        'is_exempted' => $isExempted,
                        'notes' => (string) ($att->notes ?? '-'),
                    ];
                })->values()->all();
            }
        }

        $totals = [
            'total_employees' => count($items),
            'total_base_salary' => round($totalBaseSalary, 2),
            'total_tl_deduction' => round($totalTlDeduction, 2),
            'total_sp_deduction' => round($totalSpDeduction, 2),
            'total_deductions_amount' => round($totalDeductionsAmount, 2),
            'total_net_salary' => round($totalNetSalary, 2),
        ];

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $monthName,
            'period_label' => $periodLabel,
            'is_locked' => $isLocked,
            'closing' => $closing,
            'selected_employee_id' => $employeeId,
            'employee_options' => $employeeOptions,
            'daily_details' => $dailyDetails,
            'items' => $items,
            'totals' => $totals,
        ];
    }

    /**
     * Lock and close a monthly period, capturing full payroll snapshots and logging to audit trail.
     */
    public function closePeriod(int $year, int $month, User $user, ?string $notes = null): MonthlyPeriodClosing
    {
        $existing = MonthlyPeriodClosing::getPeriod($year, $month);

        if ($existing && $existing->is_locked) {
            throw new DomainException("Periode {$year}-{$month} sudah dalam status terkunci.");
        }

        $payrollData = $this->getPayrollData($year, $month);
        $totals = $payrollData['totals'];

        $closing = MonthlyPeriodClosing::updateOrCreate(
            [
                'period_year' => $year,
                'period_month' => $month,
            ],
            [
                'is_locked' => true,
                'closed_by_admin_id' => $user->id,
                'closed_at' => Carbon::now('Asia/Jakarta'),
                'total_employees_processed' => $totals['total_employees'],
                'total_base_salary' => $totals['total_base_salary'],
                'total_deductions_amount' => $totals['total_deductions_amount'],
                'total_net_salary' => $totals['total_net_salary'],
                'notes' => $notes,
            ]
        );

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'MONTHLY_PERIOD_CLOSED',
            'entity_type' => MonthlyPeriodClosing::class,
            'entity_id' => $closing->id,
            'new_values' => [
                'year' => $year,
                'month' => $month,
                'totals' => $totals,
                'notes' => $notes,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => Carbon::now('Asia/Jakarta'),
        ]);

        return $closing;
    }

    /**
     * Re-open an already locked period with mandatory official reason and audit trail.
     * Restricted strictly to Super Admin.
     */
    public function reopenPeriod(int $year, int $month, User $user, string $reason): MonthlyPeriodClosing
    {
        if (! $user->hasRole('admin')) {
            throw new AuthorizationException('Hanya Super Admin yang berwenang membuka kunci periode bulanan.');
        }

        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw new DomainException('Alasan pembukaan kunci periode wajib diisi dengan jelas (minimal 5 karakter).');
        }

        $closing = MonthlyPeriodClosing::getPeriod($year, $month);

        if (! $closing || ! $closing->is_locked) {
            throw new DomainException("Periode {$year}-{$month} tidak dalam status terkunci.");
        }

        $closing->update([
            'is_locked' => false,
            'reopened_by_admin_id' => $user->id,
            'reopened_at' => Carbon::now('Asia/Jakarta'),
            'reopen_reason' => $reason,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'MONTHLY_PERIOD_REOPENED',
            'entity_type' => MonthlyPeriodClosing::class,
            'entity_id' => $closing->id,
            'old_values' => [
                'is_locked' => true,
            ],
            'new_values' => [
                'is_locked' => false,
                'reopen_reason' => $reason,
                'reopened_by' => $user->id,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => Carbon::now('Asia/Jakarta'),
        ]);

        return $closing;
    }
}
