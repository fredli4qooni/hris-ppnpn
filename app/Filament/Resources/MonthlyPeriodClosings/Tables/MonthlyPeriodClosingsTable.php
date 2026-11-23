<?php

namespace App\Filament\Resources\MonthlyPeriodClosings\Tables;

use App\Models\MonthlyPeriodClosing;
use App\Models\User;
use App\Services\MonthlyClosingService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MonthlyPeriodClosingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period_name')
                    ->label('Periode Bulan')
                    ->getStateUsing(fn (MonthlyPeriodClosing $record): string => $record->getPeriodLabel())
                    ->icon(Heroicon::OutlinedCalendar)
                    ->weight('bold')
                    ->sortable(['period_year', 'period_month']),

                TextColumn::make('is_locked')
                    ->label('Status Periode')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'danger' : 'warning')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Terkunci (READ ONLY)' : 'Terbuka (DRAF)'),

                TextColumn::make('total_employees_processed')
                    ->label('PPNPN')
                    ->suffix(' Orang')
                    ->alignCenter(),

                TextColumn::make('total_base_salary')
                    ->label('Total Gaji Pokok')
                    ->money('IDR', locale: 'id')
                    ->alignEnd(),

                TextColumn::make('total_deductions_amount')
                    ->label('Total Potongan')
                    ->money('IDR', locale: 'id')
                    ->color('warning')
                    ->alignEnd(),

                TextColumn::make('total_net_salary')
                    ->label('Total Honor Bersih')
                    ->money('IDR', locale: 'id')
                    ->color('success')
                    ->weight('bold')
                    ->alignEnd(),

                TextColumn::make('closed_at')
                    ->label('Waktu Penutupan')
                    ->dateTime('d M Y, H:i')
                    ->description(fn (MonthlyPeriodClosing $record): string => 'Oleh: '.($record->closedBy?->name ?? 'Admin'))
                    ->sortable(),

                TextColumn::make('reopened_info')
                    ->label('Riwayat Pembukaan')
                    ->getStateUsing(fn (MonthlyPeriodClosing $record): string => $record->reopened_at
                        ? $record->reopened_at->format('d M Y, H:i').' ('.$record->reopenedBy?->name.')'
                        : '-')
                    ->description(fn (MonthlyPeriodClosing $record): ?string => $record->reopen_reason ? 'Alasan: '.$record->reopen_reason : null)
                    ->wrap(),
            ])
            ->defaultSort('period_year', 'desc')
            ->recordActions([
                Action::make('view_summary')
                    ->label('Pratinjau Rekap')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (MonthlyPeriodClosing $record): string => route('filament.admin.pages.manage-monthly-closing', [
                        'year' => $record->period_year,
                        'month' => $record->period_month,
                    ])),

                Action::make('export_excel')
                    ->label('Excel')
                    ->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->color('success')
                    ->url(fn (MonthlyPeriodClosing $record): string => route('admin.payroll.export-excel', [
                        'year' => $record->period_year,
                        'month' => $record->period_month,
                    ]))
                    ->openUrlInNewTab(),

                Action::make('export_pdf')
                    ->label('Cetak PDF')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('primary')
                    ->url(fn (MonthlyPeriodClosing $record): string => route('admin.payroll.export-pdf', [
                        'year' => $record->period_year,
                        'month' => $record->period_month,
                    ]))
                    ->openUrlInNewTab(),

                Action::make('reopen_period')
                    ->label('Buka Kunci')
                    ->icon(Heroicon::OutlinedLockOpen)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Buka Kunci Periode Penggajian')
                    ->modalDescription('PERINGATAN: Membuka kunci periode akan mengaktifkan kembali mutasi absensi dan perizinan pada bulan terkait. Aksi ini wajib mencantumkan alasan kedinasan yang jelas dan akan dicatat pada audit log.')
                    ->schema([
                        Textarea::make('reopen_reason')
                            ->label('Alasan Resmi Pembukaan Kunci')
                            ->required()
                            ->minLength(5)
                            ->placeholder('Contoh: Koreksi data kehadiran pengemudi dinas luar kota sesuai nota dinas PPK'),
                    ])
                    ->action(function (MonthlyPeriodClosing $record, array $data): void {
                        /** @var User $user */
                        $user = Auth::user();
                        app(MonthlyClosingService::class)->reopenPeriod(
                            $record->period_year,
                            $record->period_month,
                            $user,
                            $data['reopen_reason']
                        );

                        Notification::make()
                            ->title('Periode Berhasil Dibuka Kembali')
                            ->body("Periode {$record->getPeriodLabel()} sekarang berstatus terbuka untuk koreksi.")
                            ->success()
                            ->send();
                    })
                    ->visible(function (MonthlyPeriodClosing $record): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return $record->is_locked && (bool) $user?->hasRole('admin');
                    }),
            ]);
    }
}
