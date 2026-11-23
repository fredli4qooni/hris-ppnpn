<?php

namespace App\Filament\Resources\ParallelRunReconciliations\Tables;

use App\Models\ParallelRunReconciliation;
use App\Services\ParallelRunReconciliationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ParallelRunReconciliationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('presence_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('employee.full_name')
                    ->label('Pegawai PPNPN')
                    ->description(fn (ParallelRunReconciliation $record): string => ($record->employee?->employee_code ?? '-').' • '.($record->employee?->position?->name ?? 'PPNPN'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('manual_check_in')
                    ->label('Manual')
                    ->formatStateUsing(fn (ParallelRunReconciliation $record): string => ($record->manual_check_in ?? '-').' ('.$record->manual_status.')')
                    ->fontFamily('mono')
                    ->alignCenter(),

                TextColumn::make('digital_check_in')
                    ->label('Digital HRIS')
                    ->formatStateUsing(fn (ParallelRunReconciliation $record): string => ($record->digital_check_in ?? '-').' ('.$record->digital_status.')')
                    ->fontFamily('mono')
                    ->alignCenter(),

                TextColumn::make('time_diff_minutes')
                    ->label('Deviasi')
                    ->suffix(' mnt')
                    ->color(fn (int $state): string => $state > 5 ? 'warning' : 'gray')
                    ->fontFamily('mono')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('discrepancy_type')
                    ->label('Klasifikasi')
                    ->badge()
                    ->color(fn (ParallelRunReconciliation $record): string => $record->getDiscrepancyColor())
                    ->formatStateUsing(fn (ParallelRunReconciliation $record): string => match ($record->discrepancy_type) {
                        ParallelRunReconciliation::TYPE_MATCH => 'MATCH',
                        ParallelRunReconciliation::TYPE_TIME_DRIFT => 'TIME DRIFT',
                        ParallelRunReconciliation::TYPE_STATUS_MISMATCH => 'STATUS MISMATCH',
                        ParallelRunReconciliation::TYPE_DIGITAL_MISSING => 'DIGITAL MISSING',
                        ParallelRunReconciliation::TYPE_MANUAL_MISSING => 'MANUAL MISSING',
                        default => $record->discrepancy_type,
                    })
                    ->sortable(),

                TextColumn::make('penalty_discrepancy_amount')
                    ->label('Selisih Honor')
                    ->money('IDR')
                    ->fontFamily('mono')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('resolution_status')
                    ->label('Resolusi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ParallelRunReconciliation::STATUS_AUTO_MATCHED => 'success',
                        ParallelRunReconciliation::STATUS_RESOLVED => 'info',
                        ParallelRunReconciliation::STATUS_PENDING => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ParallelRunReconciliation::STATUS_AUTO_MATCHED => 'AUTO MATCH',
                        ParallelRunReconciliation::STATUS_RESOLVED => 'RESOLVED (PPK)',
                        ParallelRunReconciliation::STATUS_PENDING => 'PENDING',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('resolution_notes')
                    ->label('Catatan PPK')
                    ->placeholder('-')
                    ->wrap()
                    ->limit(40),
            ])
            ->defaultSort('presence_date', 'desc')
            ->filters([
                SelectFilter::make('discrepancy_type')
                    ->label('Filter Klasifikasi')
                    ->options([
                        ParallelRunReconciliation::TYPE_MATCH => 'Match (Selaras)',
                        ParallelRunReconciliation::TYPE_TIME_DRIFT => 'Time Drift (> 5 Menit)',
                        ParallelRunReconciliation::TYPE_STATUS_MISMATCH => 'Status Mismatch (Beda Status)',
                        ParallelRunReconciliation::TYPE_DIGITAL_MISSING => 'Digital Missing (Lupa Absen)',
                        ParallelRunReconciliation::TYPE_MANUAL_MISSING => 'Manual Missing',
                    ]),

                SelectFilter::make('resolution_status')
                    ->label('Filter Status Resolusi')
                    ->options([
                        ParallelRunReconciliation::STATUS_AUTO_MATCHED => 'Auto Matched',
                        ParallelRunReconciliation::STATUS_RESOLVED => 'Resolved',
                        ParallelRunReconciliation::STATUS_PENDING => 'Pending',
                    ]),
            ])
            ->actions([
                Action::make('resolve')
                    ->label('Selesaikan Selisih')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('primary')
                    ->visible(fn (ParallelRunReconciliation $record): bool => $record->resolution_status === ParallelRunReconciliation::STATUS_PENDING)
                    ->modalHeading('Otorisasi Resolusi Selisih Data Pilot Run')
                    ->modalDescription(fn (ParallelRunReconciliation $record): string => "Pegawai: {$record->employee?->full_name} ({$record->presence_date?->format('d M Y')})")
                    ->schema([
                        Select::make('accepted_source')
                            ->label('Keputusan Sumber Data Sah')
                            ->options([
                                'DIGITAL' => 'Gunakan Data Digital HRIS (Standar Utama)',
                                'MANUAL' => 'Gunakan Catatan Manual Fisik (Dispensasi Khusus)',
                                'COMPROMISE' => 'Kebijakan Pengampunan PPK / Kasubbag Umum',
                            ])
                            ->default('DIGITAL')
                            ->required(),

                        Textarea::make('resolution_notes')
                            ->label('Catatan Berita Acara Resolusi (PPK / Pengawas)')
                            ->placeholder('Jelaskan alasan penetapan keputusan data sah...')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (ParallelRunReconciliation $record, array $data, ParallelRunReconciliationService $service): void {
                        $resolver = Auth::user();

                        if (! $resolver) {
                            return;
                        }

                        $service->resolveDiscrepancy(
                            $record,
                            $data['resolution_notes'],
                            $resolver,
                            $data['accepted_source']
                        );

                        Notification::make()
                            ->title('Selisih Data Berhasil Diselesaikan')
                            ->body("Rekonsiliasi {$record->employee?->full_name} tanggal {$record->presence_date?->format('d/m/Y')} telah disahkan oleh PPK.")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
