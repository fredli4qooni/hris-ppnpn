<?php

namespace App\Filament\Resources\MonthlyPeriodClosings\Pages;

use App\Filament\Resources\MonthlyPeriodClosings\MonthlyPeriodClosingResource;
use App\Models\MonthlyPeriodClosing;
use App\Models\User;
use App\Services\MonthlyClosingService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ListMonthlyPeriodClosings extends ListRecords
{
    protected static string $resource = MonthlyPeriodClosingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('go_to_calculator')
                ->label('Kalkulator & Pratinjau Rekap')
                ->icon(Heroicon::OutlinedCalculator)
                ->color('gray')
                ->url(fn (): string => route('filament.admin.pages.manage-monthly-closing')),

            Action::make('close_period')
                ->label('Kunci Periode Bulanan')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Kunci & Tutup Periode Bulanan (FR-302)')
                ->modalDescription('Proses ini akan mengunci seluruh absensi, perizinan, dan potongan pada bulan kalender yang dipilih menjadi READ ONLY permanen. Data penggajian dan rekapitulasi akan disimpan sebagai rekaman resmi.')
                ->schema([
                    Select::make('period_year')
                        ->label('Tahun')
                        ->options([
                            2025 => '2025',
                            2026 => '2026',
                            2027 => '2027',
                        ])
                        ->default(now()->year)
                        ->required(),

                    Select::make('period_month')
                        ->label('Bulan')
                        ->options(MonthlyPeriodClosing::MONTH_NAMES)
                        ->default(now()->month)
                        ->required(),

                    Textarea::make('notes')
                        ->label('Catatan Penutupan (Opsional)')
                        ->placeholder('Contoh: Penutupan rutin honorarium PPNPN akhir bulan'),
                ])
                ->action(function (array $data): void {
                    /** @var User $user */
                    $user = Auth::user();
                    $service = app(MonthlyClosingService::class);

                    $closing = $service->closePeriod(
                        (int) $data['period_year'],
                        (int) $data['period_month'],
                        $user,
                        $data['notes'] ?? null
                    );

                    Notification::make()
                        ->title('Periode Berhasil Dikunci')
                        ->body("Periode {$closing->getPeriodLabel()} telah resmi ditutup dan berstatus READ ONLY.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
