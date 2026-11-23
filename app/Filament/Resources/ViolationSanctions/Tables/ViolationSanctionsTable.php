<?php

namespace App\Filament\Resources\ViolationSanctions\Tables;

use App\Models\User;
use App\Models\ViolationSanction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ViolationSanctionsTable
{
    public static function configure(Table $table): Table
    {
        $isAdmin = function (): bool {
            /** @var User|null $user */
            $user = Auth::user();

            return (bool) $user?->hasRole('admin');
        };

        return $table
            ->columns([
                TextColumn::make('issued_date')
                    ->label('Tanggal Terbit')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('employee.full_name')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (ViolationSanction $record): string => $record->employee?->employee_code.' • '.($record->employee?->position?->name ?? 'PPNPN').($record->employee?->getEwsStatus()['is_alert'] ? ' • ['.$record->employee->getEwsStatus()['badge_label'].']' : '')),

                TextColumn::make('sanction_type')
                    ->label('Tingkat Sanksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ViolationSanction::TYPE_SP_3 => 'danger',
                        ViolationSanction::TYPE_SP_2_A, ViolationSanction::TYPE_SP_2_B, ViolationSanction::TYPE_SP_2_C => 'warning',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (ViolationSanction $record): string => $record->getTypeLabel())
                    ->sortable(),

                TextColumn::make('total_points_snapshot')
                    ->label('Akumulasi Poin')
                    ->badge()
                    ->color('danger')
                    ->suffix(' Poin')
                    ->sortable(),

                TextColumn::make('deduction_percentage')
                    ->label('Konsekuensi Potongan')
                    ->formatStateUsing(fn (ViolationSanction $record): string => $record->deduction_percentage > 0
                        ? "{$record->deduction_percentage}% ({$record->duration_months} Bulan)"
                        : ($record->is_termination_recommended ? 'Pemutusan Kontrak' : '-'))
                    ->badge()
                    ->color(fn (ViolationSanction $record): string => $record->is_termination_recommended ? 'danger' : ($record->deduction_percentage > 0 ? 'warning' : 'gray')),

                TextColumn::make('period')
                    ->label('Jadwal Pemotongan')
                    ->getStateUsing(fn (ViolationSanction $record): string => ($record->start_date && $record->end_date)
                        ? $record->start_date->format('M Y').' s.d. '.$record->end_date->format('M Y')
                        : ($record->is_termination_recommended ? 'Tindak Lanjut Pemutusan' : '-')),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->defaultSort('issued_date', 'desc')
            ->filters([
                SelectFilter::make('sanction_type')
                    ->label('Tingkat Sanksi')
                    ->options([
                        ViolationSanction::TYPE_TEGURAN_LISAN => 'Teguran Lisan (15-20 Poin)',
                        ViolationSanction::TYPE_SP_1 => 'Surat Peringatan 1 (21-40 Poin)',
                        ViolationSanction::TYPE_SP_2_A => 'SP 2A (41-60 Poin)',
                        ViolationSanction::TYPE_SP_2_B => 'SP 2B (61-80 Poin)',
                        ViolationSanction::TYPE_SP_2_C => 'SP 2C (81-99 Poin)',
                        ViolationSanction::TYPE_SP_3 => 'SP 3 (≥ 100 Poin - Putus Kontrak)',
                    ]),

                TernaryFilter::make('is_termination_recommended')
                    ->label('Rekomendasi Pemutusan Kontrak'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('disciplineSummary')
                    ->label('Berita Acara')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color(fn (ViolationSanction $record) => $record->employee?->getEwsStatus()['is_critical'] ? 'danger' : ($record->employee?->getEwsStatus()['is_warning'] ? 'warning' : 'primary'))
                    ->tooltip('Unduh / Cetak Berita Acara Rekam Jejak Pelanggaran PPNPN')
                    ->url(fn (ViolationSanction $record) => $record->employee ? route('admin.employees.discipline-summary', $record->employee) : null)
                    ->openUrlInNewTab()
                    ->visible(fn (ViolationSanction $record) => (bool) $record->employee_id),
                ViewAction::make(),
                EditAction::make()
                    ->visible($isAdmin),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible($isAdmin),
                    ForceDeleteBulkAction::make()
                        ->visible($isAdmin),
                    RestoreBulkAction::make()
                        ->visible($isAdmin),
                ]),
            ]);
    }
}
