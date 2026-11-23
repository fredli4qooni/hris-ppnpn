<?php

namespace App\Filament\Pages;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\MonthlyPeriodClosing;
use App\Models\Position;
use App\Models\User;
use App\Models\ViolationSanction;
use App\Services\MonthlyClosingService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ExecutiveGovernanceDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static \UnitEnum|string|null $navigationGroup = 'Pusat Eksekutif & Analitik';

    protected static ?string $navigationLabel = 'Dasbor Eksekutif PPK';

    protected static ?string $title = 'Dasbor Tata Kelola Eksekutif & Analitik (PPK / Kasubbag Umum)';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.executive-governance-dashboard';

    public int $selectedYear;

    public int $selectedMonth;

    // Modal state for quick contract renewal (FR-703 / US-603)
    public ?int $extendingEmployeeId = null;

    public string $newSpkNumber = '';

    public string $newStartDate = '';

    public string $newEndDate = '';

    public ?float $newSalary = null;

    public ?string $extendNotes = '';

    public function mount(?int $year = null, ?int $month = null): void
    {
        $this->selectedYear = $year ?? (int) request('year', now()->year);
        $this->selectedMonth = $month ?? (int) request('month', now()->month);

        if ($this->selectedMonth < 1 || $this->selectedMonth > 12) {
            $this->selectedMonth = now()->month;
        }

        if ($this->selectedYear < 2024 || $this->selectedYear > 2030) {
            $this->selectedYear = now()->year;
        }
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('admin') || $user->hasRole('atasan');
    }

    /**
     * Compute division compliance rates for all positions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDivisionCompliance(): array
    {
        $positions = Position::orderBy('name')->get();
        $result = [];

        foreach ($positions as $pos) {
            $employeeIds = Employee::where('position_id', $pos->id)
                ->where('is_active', true)
                ->pluck('id');

            $attendances = Attendance::whereIn('employee_id', $employeeIds)
                ->whereYear('date', $this->selectedYear)
                ->whereMonth('date', $this->selectedMonth)
                ->get();

            $totalRecords = $attendances->count();
            $compliantCount = $attendances->whereIn('status', [Attendance::STATUS_ON_TIME, Attendance::STATUS_TOLERANCE])->count();
            $lateCount = $attendances->where('status', Attendance::STATUS_LATE_TL)->count();
            $alphaCount = $attendances->where('status', Attendance::STATUS_ALPHA)->count();

            $rate = $totalRecords > 0 ? round(($compliantCount / $totalRecords) * 100, 1) : 100.0;

            $result[] = [
                'position_id' => $pos->id,
                'name' => $pos->name,
                'code' => $pos->code,
                'employee_count' => $employeeIds->count(),
                'total_records' => $totalRecords,
                'compliant_count' => $compliantCount,
                'late_count' => $lateCount,
                'alpha_count' => $alphaCount,
                'compliance_rate' => $rate,
            ];
        }

        return $result;
    }

    /**
     * Get personnel undergoing active discipline sanctions (SP 1, SP 2, SP 3).
     *
     * @return Collection<int, ViolationSanction>
     */
    public function getActiveSanctions(): Collection
    {
        return ViolationSanction::with(['employee.position'])
            ->where('is_active', true)
            ->whereDate('end_date', '>=', now()->toDateString())
            ->orderBy('end_date', 'asc')
            ->get();
    }

    /**
     * Get employees whose contracts are expiring soon (H-90 to H-7).
     *
     * @return Collection<int, Employee>
     */
    public function getExpiringContracts(): Collection
    {
        $employees = Employee::with('position')
            ->where('is_active', true)
            ->whereNotNull('contract_end_date')
            ->get();

        return $employees->filter(function (Employee $emp) {
            $days = $emp->getDaysUntilContractExpiry();

            return $days !== null && $days <= 90;
        })->sortBy(function (Employee $emp) {
            return $emp->getDaysUntilContractExpiry();
        })->values();
    }

    /**
     * Summary of monthly budget efficiency (Akumulasi 1 TL & Akumulasi 2 SP).
     *
     * @return array<string, mixed>
     */
    public function getBudgetEfficiencySummary(): array
    {
        $payroll = app(MonthlyClosingService::class)->getPayrollData($this->selectedYear, $this->selectedMonth);
        $closing = MonthlyPeriodClosing::getPeriod($this->selectedYear, $this->selectedMonth);

        $totalBase = $payroll['totals']['total_base_salary'] ?? 0.0;
        $totalDeductions = $payroll['totals']['total_deductions_amount'] ?? 0.0;

        return [
            'total_base_salary' => $totalBase,
            'total_tl_deduction' => $payroll['totals']['total_tl_deduction'] ?? 0.0,
            'total_sp_deduction' => $payroll['totals']['total_sp_deduction'] ?? 0.0,
            'total_efficiency' => $totalDeductions,
            'total_net_salary' => $payroll['totals']['total_net_salary'] ?? 0.0,
            'efficiency_percentage' => $totalBase > 0 ? round(($totalDeductions / $totalBase) * 100, 2) : 0.0,
            'period_label' => $payroll['period'] ?? "{$this->selectedMonth}/{$this->selectedYear}",
            'is_locked' => $closing ? (bool) $closing->is_locked : false,
        ];
    }

    /**
     * Open inline contract renewal modal.
     */
    public function openExtendModal(int $employeeId): void
    {
        $employee = Employee::findOrFail($employeeId);
        $this->extendingEmployeeId = $employee->id;
        $this->newSpkNumber = 'SPK/KUM/'.date('Y').'/'.str_pad((string) $employee->id, 3, '0', STR_PAD_LEFT);
        $this->newStartDate = $employee->contract_end_date ? $employee->contract_end_date->copy()->addDay()->toDateString() : now()->toDateString();
        $this->newEndDate = Carbon::parse($this->newStartDate)->addYear()->subDay()->toDateString();
        $this->newSalary = (float) $employee->base_salary;
        $this->extendNotes = 'Perpanjangan kontrak kerja PPNPN hasil telaah kinerja eksekutif';
    }

    /**
     * Process contract extension and log audit trail.
     */
    public function processExtendContract(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $this->extendingEmployeeId) {
            return;
        }

        $employee = Employee::findOrFail($this->extendingEmployeeId);

        $employee->extendContract(
            newSpkNumber: $this->newSpkNumber,
            newStartDate: $this->newStartDate,
            newEndDate: $this->newEndDate,
            newSalary: $this->newSalary,
            newContractValue: $this->newSalary ? $this->newSalary * 12 : null,
            notes: $this->extendNotes,
            actor: $user
        );

        $this->extendingEmployeeId = null;

        Notification::make()
            ->title('Kontrak Berhasil Diperpanjang')
            ->body("Kontrak kerja {$employee->full_name} ({$this->newSpkNumber}) berhasil diperbarui.")
            ->success()
            ->send();
    }

    /**
     * Cancel extension modal.
     */
    public function cancelExtendModal(): void
    {
        $this->extendingEmployeeId = null;
    }
}
