<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\OvertimeService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class OvertimeRecapPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static \UnitEnum|string|null $navigationGroup = 'Manajemen Lembur';

    protected static ?string $navigationLabel = 'Rekap & Kalender Lembur';

    protected static ?string $title = 'Rekapitulasi & Kalender Lembur PPNPN';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.overtime-recap-page';

    public int $selectedYear;

    public int $selectedMonth;

    public ?int $selectedEmployeeId = null;

    public function mount(): void
    {
        $this->selectedYear = (int) request('year', now('Asia/Jakarta')->year);
        $this->selectedMonth = (int) request('month', now('Asia/Jakarta')->month);
        $reqEmp = request('employee_id');
        $this->selectedEmployeeId = is_numeric($reqEmp) && (int) $reqEmp > 0 ? (int) $reqEmp : null;

        if ($this->selectedMonth < 1 || $this->selectedMonth > 12) {
            $this->selectedMonth = now('Asia/Jakarta')->month;
        }
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) ($user && ($user->hasRole('admin') || $user->hasRole('atasan') || $user->hasRole('supervisor')));
    }

    /**
     * @return array<string, mixed>
     */
    public function getRecapData(): array
    {
        return app(OvertimeService::class)->getMonthlyOvertimeRecap(
            $this->selectedYear,
            $this->selectedMonth,
            $this->selectedEmployeeId
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_overtime_excel')
                ->label('Unduh Rekap Excel (.csv)')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('success')
                ->url(fn (): string => route('admin.overtime.recap.excel', [
                    'year' => $this->selectedYear,
                    'month' => $this->selectedMonth,
                ]))
                ->openUrlInNewTab(),

            Action::make('export_overtime_pdf')
                ->label('Cetak Rekap PDF')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('primary')
                ->url(fn (): string => route('admin.overtime.recap.pdf', [
                    'year' => $this->selectedYear,
                    'month' => $this->selectedMonth,
                ]))
                ->openUrlInNewTab(),
        ];
    }
}
