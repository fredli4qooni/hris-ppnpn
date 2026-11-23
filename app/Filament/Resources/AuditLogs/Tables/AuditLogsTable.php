<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Models\AuditLog;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu (WIB)')
                    ->dateTime('d M Y, H:i:s')
                    ->description(fn (AuditLog $record): string => $record->created_at ? $record->created_at->diffForHumans() : '-')
                    ->icon(Heroicon::OutlinedClock)
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Aktor / Pengguna')
                    ->placeholder('Sistem Otomatis (Scheduler)')
                    ->description(fn (AuditLog $record): string => $record->user?->email ?? 'Daemon Service / Cron')
                    ->icon(Heroicon::OutlinedUser)
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('action')
                    ->label('Tipe Aksi')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'FAILED'),
                        str_contains($state, 'REJECTED'),
                        str_contains($state, 'INACTIVATED'),
                        str_contains($state, 'SANCTION') => 'danger',

                        str_contains($state, 'DISMISSED'),
                        str_contains($state, 'REOPENED'),
                        str_contains($state, 'REVIEW'),
                        str_contains($state, 'SUBMITTED') => 'warning',

                        str_contains($state, 'SUCCESS'),
                        str_contains($state, 'APPROVED'),
                        str_contains($state, 'CLOSED'),
                        str_contains($state, 'EXTENDED'),
                        str_contains($state, 'ENROLLED'),
                        str_contains($state, 'IMPORTED'),
                        str_contains($state, 'CONFIRMED'),
                        str_contains($state, 'LOGIN') => 'success',

                        default => 'info',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('entity_type')
                    ->label('Modul / Entitas')
                    ->formatStateUsing(function (?string $state, AuditLog $record): string {
                        if (! $state) {
                            return '-';
                        }

                        $baseName = class_basename($state);

                        return $record->entity_id ? "{$baseName} #{$record->entity_id}" : $baseName;
                    })
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('ip_address')
                    ->label('IP & Klien')
                    ->formatStateUsing(fn (?string $state): string => $state ?? '127.0.0.1 (CLI)')
                    ->description(fn (AuditLog $record): string => $record->user_agent ? Str::limit($record->user_agent, 32) : 'CLI System / Daemon')
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->placeholder('Pilih tanggal awal'),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->placeholder('Pilih tanggal akhir'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                SelectFilter::make('user_id')
                    ->label('Aktor / Pengguna')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('action')
                    ->label('Kategori Aksi')
                    ->options(function (): array {
                        $labels = [
                            'LOGIN' => 'LOGIN — Autentikasi Berhasil',
                            'LOGIN_FAILED' => 'LOGIN_FAILED — Gagal Masuk',
                            'LOGOUT' => 'LOGOUT — Keluar Sistem',
                            'CONTRACT_EXTENDED' => 'CONTRACT_EXTENDED — Pembaruan Kontrak PPNPN',
                            'CONTRACT_AUTO_INACTIVATED' => 'CONTRACT_AUTO_INACTIVATED — Penonaktifan Otomatis Kontrak',
                            'MONTHLY_PERIOD_CLOSED' => 'MONTHLY_PERIOD_CLOSED — Penutupan Periode Penggajian',
                            'MONTHLY_PERIOD_REOPENED' => 'MONTHLY_PERIOD_REOPENED — Pembukaan Kunci Periode',
                            'ATTENDANCE_CORRECTION_SUBMITTED' => 'ATTENDANCE_CORRECTION_SUBMITTED — Pengajuan Lupa Absen',
                            'ATTENDANCE_CORRECTION_SUPERVISOR_APPROVED' => 'ATTENDANCE_CORRECTION_SUPERVISOR_APPROVED — Telaah Supervisor',
                            'ATTENDANCE_CORRECTION_FINAL_APPROVED' => 'ATTENDANCE_CORRECTION_FINAL_APPROVED — Persetujuan Lupa Absen Sah',
                            'ATTENDANCE_CORRECTION_FINAL_REJECTED' => 'ATTENDANCE_CORRECTION_FINAL_REJECTED — Penolakan Lupa Absen',
                            'LATE_EXCEPTION_SUBMITTED' => 'LATE_EXCEPTION_SUBMITTED — Pengajuan Dispensasi Keterlambatan',
                            'LATE_EXCEPTION_SUPERVISOR_REVIEW' => 'LATE_EXCEPTION_SUPERVISOR_REVIEW — Telaah Dispensasi Supervisor',
                            'LATE_EXCEPTION_ATASAN_DECISION' => 'LATE_EXCEPTION_ATASAN_DECISION — Putusan Dispensasi Atasan',
                            'VIOLATION_DRAFT_CONFIRMED' => 'VIOLATION_DRAFT_CONFIRMED — Konfirmasi Sanksi Pelanggaran',
                            'VIOLATION_DRAFT_DISMISSED' => 'VIOLATION_DRAFT_DISMISSED — Pembatalan Draf Pelanggaran',
                            'EMPLOYEES_IMPORTED' => 'EMPLOYEES_IMPORTED — Impor Data Master Pegawai',
                            'FACE_TEMPLATE_ENROLLED' => 'FACE_TEMPLATE_ENROLLED — Perekaman Biometrik Wajah',
                        ];

                        $recorded = AuditLog::query()
                            ->whereNotNull('action')
                            ->distinct()
                            ->pluck('action')
                            ->filter()
                            ->values()
                            ->all();

                        $options = [];
                        foreach ($recorded as $action) {
                            $options[$action] = $labels[$action] ?? $action;
                        }

                        // Also include standard predefined list if not yet recorded
                        foreach ($labels as $key => $lbl) {
                            if (! isset($options[$key])) {
                                $options[$key] = $lbl;
                            }
                        }

                        return $options;
                    })
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('view_diff')
                    ->label('Buka Diff')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->color('primary')
                    ->tooltip('Periksa komparasi data sebelum vs sesudah')
                    ->modalHeading(fn (AuditLog $record): string => 'Inspeksi Rekaman Audit: '.$record->action)
                    ->modalDescription('Komparasi data sebelum (Old Values) dan data sesudah (New Values) demi akuntabilitas hukum dan integritas sistem (FR-902).')
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup Inspeksi')
                    ->modalContent(fn (AuditLog $record): View => view('filament.resources.audit-logs.modals.diff-viewer', [
                        'record' => $record,
                    ])),
            ])
            ->bulkActions([]);
    }
}
