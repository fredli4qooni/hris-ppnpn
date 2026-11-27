<?php

namespace App\Filament\Pages;

use App\Models\MonthlyPeriodClosing;
use App\Models\User;
use App\Services\MonthlyClosingService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ManageMonthlyClosing extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static \UnitEnum|string|null $navigationGroup = 'Rekapitulasi & Penggajian';

    protected static ?string $navigationLabel = 'Kalkulator Rekapitulasi';

    protected static ?string $title = 'Pratinjau & Penguncian Rekapitulasi Penggajian';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.manage-monthly-closing';

    public int $selectedYear;

    public int $selectedMonth;

    public ?int $selectedEmployeeId = null;

    public function mount(): void
    {
        $this->selectedYear = (int) request('year', now()->year);
        $this->selectedMonth = (int) request('month', now()->month);
        $requestedEmployeeId = request('employee_id');
        $this->selectedEmployeeId = is_numeric($requestedEmployeeId) && (int) $requestedEmployeeId > 0
            ? (int) $requestedEmployeeId
            : null;

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
     * Get computed payroll data for the currently selected year, month, and optional employee filter.
     *
     * @return array<string, mixed>
     */
    public function getPayrollData(): array
    {
        return app(MonthlyClosingService::class)->getPayrollData($this->selectedYear, $this->selectedMonth, $this->selectedEmployeeId);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Unduh Excel (.csv)')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('success')
                ->url(fn (): string => route('admin.payroll.export-excel', [
                    'year' => $this->selectedYear,
                    'month' => $this->selectedMonth,
                ]))
                ->openUrlInNewTab(),

            Action::make('export_pdf')
                ->label('Cetak Dokumen PDF Resmi')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('primary')
                ->url(fn (): string => route('admin.payroll.export-pdf', [
                    'year' => $this->selectedYear,
                    'month' => $this->selectedMonth,
                ]))
                ->openUrlInNewTab(),

            Action::make('lock_period_action')
                ->label('Kunci & Tutup Periode')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Kunci & Tutup Periode Penggajian')
                ->modalDescription('Apakah Anda yakin ingin mengunci periode ini? Seluruh absensi, perizinan, dan pemotongan pada bulan ini akan berstatus READ ONLY permanen.')
                ->schema([
                    Textarea::make('notes')
                        ->label('Catatan Penutupan (Opsional)')
                        ->placeholder('Catatan pengesahan penutupan periode penggajian'),
                ])
                ->action(function (array $data): void {
                    /** @var User $user */
                    $user = Auth::user();
                    $service = app(MonthlyClosingService::class);

                    $closing = $service->closePeriod($this->selectedYear, $this->selectedMonth, $user, $data['notes'] ?? null);

                    Notification::make()
                        ->title('Periode Berhasil Dikunci')
                        ->body("Periode {$closing->getPeriodLabel()} resmi berstatus READ ONLY.")
                        ->success()
                        ->send();
                })
                ->visible(function (): bool {
                    $closing = MonthlyPeriodClosing::getPeriod($this->selectedYear, $this->selectedMonth);

                    return ! ($closing && $closing->is_locked);
                }),

            Action::make('reopen_period_action')
                ->label('Buka Kunci Periode')
                ->icon(Heroicon::OutlinedLockOpen)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Otorisasi Pembukaan Kunci Periode (Super Admin)')
                ->modalDescription('Membuka kunci periode akan memungkinkan perubahan absensi kembali. Tindakan ini wajib memiliki alasan kedinasan yang jelas dan akan dicatat pada audit log.')
                ->schema([
                    Textarea::make('reopen_reason')
                        ->label('Alasan Resmi Pembukaan Kunci')
                        ->required()
                        ->minLength(5)
                        ->placeholder('Tuliskan alasan resmi pembukaan kunci periode...'),
                ])
                ->action(function (array $data): void {
                    /** @var User $user */
                    $user = Auth::user();
                    $service = app(MonthlyClosingService::class);

                    $closing = $service->reopenPeriod($this->selectedYear, $this->selectedMonth, $user, $data['reopen_reason']);

                    Notification::make()
                        ->title('Periode Berhasil Dibuka Kembali')
                        ->body("Periode {$closing->getPeriodLabel()} sekarang berstatus terbuka untuk koreksi.")
                        ->success()
                        ->send();
                })
                ->visible(function (): bool {
                    /** @var User|null $user */
                    $user = Auth::user();
                    $closing = MonthlyPeriodClosing::getPeriod($this->selectedYear, $this->selectedMonth);

                    return (bool) ($closing && $closing->is_locked && $user?->hasRole('admin'));
                }),
        ];
    }
}
