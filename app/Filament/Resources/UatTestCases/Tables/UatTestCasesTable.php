<?php

namespace App\Filament\Resources\UatTestCases\Tables;

use App\Models\AuditLog;
use App\Models\UatTestCase;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UatTestCasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('test_case_code')
                    ->label('Kode Uji')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('module_group')
                    ->label('Kelompok Modul')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Judul Skenario UAT')
                    ->description(fn (UatTestCase $record): string => $record->scenario_description)
                    ->weight('semibold')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('actor_role')
                    ->label('Aktor Penguji')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status UAT')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'passed' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'passed' => 'LULUS (PASSED)',
                        'failed' => 'CACAT (FAILED)',
                        'pending' => 'MENUNGGU (PENDING)',
                        default => strtoupper($state),
                    })
                    ->sortable(),

                TextColumn::make('execution_duration_ms')
                    ->label('Durasi')
                    ->suffix(' ms')
                    ->placeholder('-')
                    ->alignCenter(),

                TextColumn::make('tested_at')
                    ->label('Waktu Verifikasi')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('Belum diuji')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('module_group')
                    ->label('Filter Modul')
                    ->options([
                        'Presensi & Biometrik Wajah' => 'Presensi & Biometrik Wajah',
                        'Geofencing GPS & Waktu Kerja' => 'Geofencing GPS & Waktu Kerja',
                        'Pengecualian & Izin Cuti' => 'Pengecualian & Izin Cuti',
                        'Penegakan Disiplin & Sanksi SP' => 'Penegakan Disiplin & Sanksi SP',
                        'Rekapitulasi Honor & Closing' => 'Rekapitulasi Honor & Closing',
                        'Dasbor Komando & Audit' => 'Dasbor Komando & Audit',
                    ]),

                SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'passed' => 'Lulus (Passed)',
                        'failed' => 'Cacat / Gagal (Failed)',
                        'pending' => 'Menunggu (Pending)',
                    ]),
            ])
            ->actions([
                Action::make('record_result')
                    ->label('Catat / Perbarui Hasil')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('primary')
                    ->modalHeading(fn (UatTestCase $record): string => "Verifikasi Hasil: {$record->test_case_code}")
                    ->modalDescription(fn (UatTestCase $record): string => $record->title)
                    ->schema([
                        Select::make('status')
                            ->label('Status UAT')
                            ->options([
                                'passed' => 'Lulus (Passed)',
                                'failed' => 'Cacat / Gagal (Failed)',
                                'pending' => 'Menunggu (Pending)',
                            ])
                            ->default(fn (UatTestCase $record): string => $record->status)
                            ->required(),

                        Textarea::make('actual_result')
                            ->label('Hasil Pengujian Aktual')
                            ->default(fn (UatTestCase $record): ?string => $record->actual_result)
                            ->rows(3)
                            ->required(),

                        Select::make('severity_if_failed')
                            ->label('Tingkat Keparahan Cacat (Jika Gagal)')
                            ->options([
                                'critical' => 'Critical (Blocker - SLA <= 4 Jam)',
                                'major' => 'Major (SLA <= 24 Jam)',
                                'minor' => 'Minor (Tampilan / Kosmetik)',
                                'enhancement' => 'Enhancement (Usulan Lanjutan)',
                            ])
                            ->nullable(),

                        Textarea::make('notes')
                            ->label('Catatan Auditor / Penguji')
                            ->default(fn (UatTestCase $record): ?string => $record->notes)
                            ->rows(2),
                    ])
                    ->action(function (UatTestCase $record, array $data): void {
                        $now = Carbon::now('Asia/Jakarta');
                        $userName = Auth::user()?->name ?? 'Auditor UAT';

                        $record->update([
                            'status' => $data['status'],
                            'actual_result' => $data['actual_result'],
                            'severity_if_failed' => $data['status'] === 'failed' ? $data['severity_if_failed'] : null,
                            'notes' => $data['notes'],
                            'tested_by' => $userName,
                            'tested_at' => $now,
                        ]);

                        AuditLog::create([
                            'user_id' => Auth::id(),
                            'action' => 'UAT_CASE_STATUS_UPDATED',
                            'details' => json_encode([
                                'test_case_code' => $record->test_case_code,
                                'status' => $data['status'],
                                'tested_by' => $userName,
                            ]),
                        ]);

                        Notification::make()
                            ->title('Status UAT Diperbarui')
                            ->body("Skenario {$record->test_case_code} berhasil diperbarui menjadi {$data['status']}.")
                            ->success()
                            ->send();
                    }),

                Action::make('resolve_defect')
                    ->label('Remediasi Cacat')
                    ->icon(Heroicon::OutlinedWrenchScrewdriver)
                    ->color('success')
                    ->visible(fn (UatTestCase $record): bool => $record->status === 'failed')
                    ->modalHeading('Remediasi Cacat UAT (SLA Resolusi Cepat)')
                    ->schema([
                        Textarea::make('resolution_notes')
                            ->label('Catatan Perbaikan / Resolusi')
                            ->placeholder('Jelaskan perbaikan kode / konfigurasi yang telah diterapkan...')
                            ->required(),
                    ])
                    ->action(function (UatTestCase $record, array $data): void {
                        $userName = Auth::user()?->name ?? 'Dewi Nadila (Lead Developer)';

                        $record->update([
                            'status' => 'passed',
                            'severity_if_failed' => null,
                            'notes' => "Remediasi Selesai: {$data['resolution_notes']}",
                            'tested_by' => $userName,
                            'tested_at' => Carbon::now('Asia/Jakarta'),
                        ]);

                        Notification::make()
                            ->title('Cacat Berhasil Diremediasi')
                            ->body("Skenario {$record->test_case_code} telah dinyatakan LULUS setelah perbaikan.")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
