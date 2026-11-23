<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeViolation;
use App\Models\ViolationSanction;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ViolationSanctionService
{
    /**
     * Point thresholds and sanction configuration based on Lampiran III SPK-20/WPB.08/PPK/2025.
     */
    public const THRESHOLD_TEGURAN_MIN = 15;

    public const THRESHOLD_TEGURAN_MAX = 20;

    public const THRESHOLD_SP1_MIN = 21;

    public const THRESHOLD_SP1_MAX = 40;

    public const THRESHOLD_SP2A_MIN = 41;

    public const THRESHOLD_SP2A_MAX = 60;

    public const THRESHOLD_SP2B_MIN = 61;

    public const THRESHOLD_SP2B_MAX = 80;

    public const THRESHOLD_SP2C_MIN = 81;

    public const THRESHOLD_SP2C_MAX = 99;

    public const THRESHOLD_SP3_MIN = 100;

    /**
     * Calculate total active violation points accumulated by the employee.
     */
    public function calculateActivePoints(Employee $employee): int
    {
        return (int) $employee->violations()
            ->where('status', EmployeeViolation::STATUS_ACTIVE)
            ->sum('points');
    }

    /**
     * Map active point total to the corresponding sanction definition.
     *
     * @return array{
     *     type: string,
     *     deduction_percentage: float,
     *     duration_months: int,
     *     is_termination: bool,
     *     notes: string
     * }|null
     */
    public function determineSanctionFromPoints(int $points): ?array
    {
        if ($points < self::THRESHOLD_TEGURAN_MIN) {
            return null;
        }

        if ($points <= self::THRESHOLD_TEGURAN_MAX) {
            return [
                'type' => ViolationSanction::TYPE_TEGURAN_LISAN,
                'deduction_percentage' => 5.00,
                'duration_months' => 1,
                'is_termination' => false,
                'notes' => 'Teguran lisan dengan pemotongan honor 5% selama 1 bulan.',
            ];
        }

        if ($points <= self::THRESHOLD_SP1_MAX) {
            return [
                'type' => ViolationSanction::TYPE_SP_1,
                'deduction_percentage' => 10.00,
                'duration_months' => 1,
                'is_termination' => false,
                'notes' => 'Surat Peringatan 1 (SP 1) dengan pemotongan honor 10% selama 1 bulan.',
            ];
        }

        if ($points <= self::THRESHOLD_SP2A_MAX) {
            return [
                'type' => ViolationSanction::TYPE_SP_2_A,
                'deduction_percentage' => 5.00,
                'duration_months' => 3,
                'is_termination' => false,
                'notes' => 'Surat Peringatan 2 Tingkat A (SP 2A) dengan pemotongan honor 5% selama 3 bulan.',
            ];
        }

        if ($points <= self::THRESHOLD_SP2B_MAX) {
            return [
                'type' => ViolationSanction::TYPE_SP_2_B,
                'deduction_percentage' => 10.00,
                'duration_months' => 3,
                'is_termination' => false,
                'notes' => 'Surat Peringatan 2 Tingkat B (SP 2B) dengan pemotongan honor 10% selama 3 bulan.',
            ];
        }

        if ($points <= self::THRESHOLD_SP2C_MAX) {
            return [
                'type' => ViolationSanction::TYPE_SP_2_C,
                'deduction_percentage' => 10.00,
                'duration_months' => 6,
                'is_termination' => false,
                'notes' => 'Surat Peringatan 2 Tingkat C (SP 2C) dengan pemotongan honor 10% selama 6 bulan.',
            ];
        }

        return [
            'type' => ViolationSanction::TYPE_SP_3,
            'deduction_percentage' => 0.00,
            'duration_months' => 0,
            'is_termination' => true,
            'notes' => 'Surat Peringatan 3 (SP 3) dengan rekomendasi pemutusan kontrak kerja secara sepihak.',
        ];
    }

    /**
     * Evaluate employee violation points and issue or sync corresponding sanction.
     */
    public function evaluateAndApplySanction(Employee $employee, ?CarbonInterface $referenceDate = null): ?ViolationSanction
    {
        $refDate = $referenceDate ? Carbon::instance($referenceDate) : Carbon::now('Asia/Jakarta');
        $totalPoints = $this->calculateActivePoints($employee);
        $definition = $this->determineSanctionFromPoints($totalPoints);

        if (! $definition) {
            return null;
        }

        // Check if employee already has an active sanction of this specific type
        $existingSanction = $employee->sanctions()
            ->where('sanction_type', $definition['type'])
            ->where('is_active', true)
            ->first();

        if ($existingSanction) {
            // Update point snapshot if changed
            if ($existingSanction->total_points_snapshot !== $totalPoints) {
                $existingSanction->update(['total_points_snapshot' => $totalPoints]);
            }

            return $existingSanction;
        }

        // Schedule deduction starts from the NEXT calendar month (M+1)
        $startDate = $refDate->copy()->addMonth()->startOfMonth();
        $endDate = null;

        if ($definition['duration_months'] > 0) {
            $endDate = $startDate->copy()->addMonths($definition['duration_months'] - 1)->endOfMonth();
        }

        return ViolationSanction::create([
            'employee_id' => $employee->id,
            'sanction_type' => $definition['type'],
            'total_points_snapshot' => $totalPoints,
            'deduction_percentage' => $definition['deduction_percentage'],
            'duration_months' => $definition['duration_months'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'issued_date' => $refDate->toDateString(),
            'is_active' => true,
            'is_termination_recommended' => $definition['is_termination'],
            'notes' => $definition['notes'],
        ]);
    }

    /**
     * Get active sanction deduction percentage effective for a specific month.
     */
    public function getActiveSanctionDeduction(Employee $employee, int $year, int $month): float
    {
        $targetStart = Carbon::createFromDate($year, $month, 1, 'Asia/Jakarta')->startOfMonth();
        $targetEnd = $targetStart->copy()->endOfMonth();

        /** @var ViolationSanction|null $sanction */
        $sanction = $employee->sanctions()
            ->where('is_active', true)
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('start_date', '<=', $targetEnd)
            ->where('end_date', '>=', $targetStart)
            ->orderBy('deduction_percentage', 'desc')
            ->first();

        return $sanction ? (float) $sanction->deduction_percentage : 0.00;
    }

    /**
     * Calculate dual accumulation combined deduction for payroll / reporting.
     * Enforces the separation of Accumulation 1 (TL) and Accumulation 2 (SP).
     *
     * @return array{
     *     employee_id: int,
     *     year: int,
     *     month: int,
     *     base_salary: float,
     *     accumulation_1_tl_percentage: float,
     *     accumulation_1_tl_amount: float,
     *     accumulation_2_sanction_percentage: float,
     *     accumulation_2_sanction_amount: float,
     *     total_deduction_percentage: float,
     *     total_deduction_amount: float,
     *     net_salary: float,
     *     is_termination_recommended: bool
     * }
     */
    public function calculateCombinedDeduction(Employee $employee, int $year, int $month): array
    {
        $baseSalary = (float) ($employee->base_salary ?? 0.00);

        // Akumulasi 1: Potongan Keterlambatan Masuk (TL 1-3) Lampiran IV SPK
        $latePercentage = (float) Attendance::where('employee_id', $employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum('deduction_percentage');

        $lateDeductionAmount = round(($baseSalary * $latePercentage) / 100, 2);

        // Akumulasi 2: Sanksi Pelanggaran Poin SPK (Teguran, SP 1-3) Lampiran III SPK
        $sanctionPercentage = $this->getActiveSanctionDeduction($employee, $year, $month);
        $sanctionDeductionAmount = round(($baseSalary * $sanctionPercentage) / 100, 2);

        // Check if termination is recommended (SP 3)
        $isTermination = $employee->sanctions()
            ->where('is_active', true)
            ->where('is_termination_recommended', true)
            ->exists();

        // Combined Net
        $totalPercentage = round($latePercentage + $sanctionPercentage, 2);
        $totalDeductionAmount = round($lateDeductionAmount + $sanctionDeductionAmount, 2);
        $netSalary = max(0.00, round($baseSalary - $totalDeductionAmount, 2));

        return [
            'employee_id' => $employee->id,
            'year' => $year,
            'month' => $month,
            'base_salary' => $baseSalary,
            'accumulation_1_tl_percentage' => $latePercentage,
            'accumulation_1_tl_amount' => $lateDeductionAmount,
            'accumulation_2_sanction_percentage' => $sanctionPercentage,
            'accumulation_2_sanction_amount' => $sanctionDeductionAmount,
            'total_deduction_percentage' => $totalPercentage,
            'total_deduction_amount' => $totalDeductionAmount,
            'net_salary' => $netSalary,
            'is_termination_recommended' => $isTermination,
        ];
    }
}
