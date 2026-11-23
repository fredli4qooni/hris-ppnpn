<?php

namespace App\Filament\Resources\LeaveRequests\Tables;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveRequestService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LeaveRequestsTable
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
                TextColumn::make('created_at')
                    ->label('Tgl Pengajuan')
                    ->dateTime('d M Y')
                    ->sortable(),

                TextColumn::make('employee.full_name')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (LeaveRequest $record): string => $record->employee?->employee_code.' • '.($record->employee?->position?->name ?? 'PPNPN')),

                TextColumn::make('leave_type')
                    ->label('Jenis Izin/Cuti')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (LeaveRequest $record): string => $record->getTypeLabel())
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Rentang Tanggal')
                    ->formatStateUsing(function (LeaveRequest $record): string {
                        $start = Carbon::parse($record->start_date)->translatedFormat('d M');
                        $end = Carbon::parse($record->end_date)->translatedFormat('d M Y');

                        return $record->start_date === $record->end_date ? $end : "{$start} - {$end}";
                    })
                    ->sortable(),

                TextColumn::make('total_days')
                    ->label('Durasi')
                    ->badge()
                    ->color('info')
                    ->suffix(' Hari')
                    ->sortable(),

                TextColumn::make('supervisor_status')
                    ->label('Tahap 1: Supervisor')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        LeaveRequest::SUPERVISOR_STATUS_VERIFIED => 'success',
                        LeaveRequest::SUPERVISOR_STATUS_REJECTED => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (LeaveRequest $record): string => $record->getSupervisorStatusLabel())
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status Putusan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        LeaveRequest::STATUS_APPROVED => 'success',
                        LeaveRequest::STATUS_REJECTED => 'danger',
                        LeaveRequest::STATUS_PENDING_ATASAN => 'info',
                        LeaveRequest::STATUS_CANCELLED => 'gray',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (LeaveRequest $record): string => $record->getStatusLabel())
                    ->sortable(),

                TextColumn::make('sla_status')
                    ->label('SLA Pelayanan')
                    ->badge()
                    ->color(fn (LeaveRequest $record): string => $record->getSlaBadgeColor())
                    ->formatStateUsing(fn (LeaveRequest $record): string => $record->getSlaStatusLabel())
                    ->description(fn (LeaveRequest $record): ?string => $record->isOverdueSla() ? '⚠️ Menunggu >2 Hari' : null)
                    ->sortable(false),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Pengajuan')
                    ->options([
                        LeaveRequest::STATUS_PENDING_SUPERVISOR => 'Menunggu Verifikasi Supervisor (Tahap 1)',
                        LeaveRequest::STATUS_PENDING_ATASAN => 'Menunggu Putusan Akhir Atasan (Tahap 2)',
                        LeaveRequest::STATUS_APPROVED => 'Disetujui',
                        LeaveRequest::STATUS_REJECTED => 'Ditolak',
                        LeaveRequest::STATUS_CANCELLED => 'Dibatalkan',
                    ]),

                SelectFilter::make('sla_status')
                    ->label('Kepatuhan SLA (FR-508)')
                    ->options([
                        'overdue' => 'Melebihi SLA (> 2 Hari Kerja)',
                        'warning' => 'Batas SLA (2 Hari Kerja)',
                        'on_time' => 'Sesuai SLA (≤ 1 Hari Kerja)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! isset($data['value']) || empty($data['value'])) {
                            return $query;
                        }

                        $pendingRecords = (clone $query)
                            ->whereIn('status', [LeaveRequest::STATUS_PENDING_SUPERVISOR, LeaveRequest::STATUS_PENDING_ATASAN])
                            ->get();

                        $matchingIds = [];
                        foreach ($pendingRecords as $record) {
                            $days = $record->getPendingWorkingDays();
                            if ($data['value'] === 'overdue' && $days > LeaveRequest::SLA_MAX_WORKING_DAYS) {
                                $matchingIds[] = $record->id;
                            } elseif ($data['value'] === 'warning' && $days === LeaveRequest::SLA_MAX_WORKING_DAYS) {
                                $matchingIds[] = $record->id;
                            } elseif ($data['value'] === 'on_time' && $days < LeaveRequest::SLA_MAX_WORKING_DAYS) {
                                $matchingIds[] = $record->id;
                            }
                        }

                        return $query->whereIn('id', $matchingIds);
                    }),

                SelectFilter::make('leave_type')
                    ->label('Jenis Izin / Cuti')
                    ->options([
                        LeaveRequest::TYPE_ANNUAL_LEAVE => 'Cuti Tahunan',
                        LeaveRequest::TYPE_SICK_LEAVE => 'Izin Sakit',
                        LeaveRequest::TYPE_SPECIAL_LEAVE => 'Izin Khusus / Di Luar Cuti',
                        LeaveRequest::TYPE_DUTY_LEAVE => 'Izin Tugas Kedinasan',
                        LeaveRequest::TYPE_EARLY_DEPARTURE => 'Izin Pulang Lebih Awal',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                // Action Tahap 1: Verifikasi Supervisor Lapangan
                Action::make('verifySupervisor')
                    ->label('Verifikasi')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('warning')
                    ->visible(fn (LeaveRequest $record): bool => $record->canBeVerifiedBy(Auth::user()))
                    ->modalHeading('Verifikasi Faktual Operasional (Tahap 1)')
                    ->modalDescription('Lakukan pemeriksaan faktual atas permohonan subordinat sebelum diteruskan ke Pejabat Penyetuju.')
                    ->form([
                        Radio::make('decision')
                            ->label('Keputusan Verifikasi')
                            ->options([
                                'VERIFIED' => 'Diverifikasi Faktual (Lanjutkan ke Pejabat Penyetuju)',
                                'REJECTED' => 'Tolak Permohonan (Operasional Terganggu / Alasan Tidak Sesuai)',
                            ])
                            ->default('VERIFIED')
                            ->required(),

                        Textarea::make('notes')
                            ->label('Catatan Hasil Verifikasi Lapangan')
                            ->placeholder('Masukkan catatan faktual atau alasan penolakan...')
                            ->rows(3),
                    ])
                    ->action(function (LeaveRequest $record, array $data): void {
                        /** @var User $currentUser */
                        $currentUser = Auth::user();
                        $isApproved = $data['decision'] === 'VERIFIED';

                        app(LeaveRequestService::class)->verifyBySupervisor(
                            $record,
                            $currentUser,
                            $isApproved,
                            $data['notes'] ?? null
                        );

                        Notification::make()
                            ->title($isApproved ? 'Permohonan Berhasil Diverifikasi' : 'Permohonan Ditolak')
                            ->body($isApproved ? 'Status kini diteruskan ke Pejabat Penyetuju (Atasan).' : 'Status permohonan telah ditandai ditolak.')
                            ->success()
                            ->send();
                    }),

                // Action Tahap 2: Putusan Akhir Pejabat Penyetuju / Atasan
                Action::make('approveAtasan')
                    ->label('Putusan Akhir')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->color('success')
                    ->visible(fn (LeaveRequest $record): bool => $record->canBeApprovedBy(Auth::user()))
                    ->modalHeading('Putusan Final Pejabat Penyetuju (Tahap 2)')
                    ->modalDescription('Persetujuan final berkekuatan kedinasan akan menyinkronkan status hadir pegawai dan memotong saldo cuti tahunan.')
                    ->form([
                        Radio::make('decision')
                            ->label('Putusan Akhir')
                            ->options([
                                'APPROVED' => 'Setujui Secara Sah (Terbitkan Izin / Cuti)',
                                'REJECTED' => 'Tolak Permohonan',
                            ])
                            ->default('APPROVED')
                            ->required(),

                        Textarea::make('notes')
                            ->label('Catatan Pejabat Penyetuju')
                            ->placeholder('Masukkan catatan keputusan kedinasan...')
                            ->rows(3),
                    ])
                    ->action(function (LeaveRequest $record, array $data): void {
                        /** @var User $currentUser */
                        $currentUser = Auth::user();
                        $isApproved = $data['decision'] === 'APPROVED';

                        app(LeaveRequestService::class)->approveByAtasan(
                            $record,
                            $currentUser,
                            $isApproved,
                            $data['notes'] ?? null
                        );

                        Notification::make()
                            ->title($isApproved ? 'Permohonan Disetujui Secara Sah' : 'Permohonan Ditolak')
                            ->body($isApproved ? 'Presensi kalender telah disinkronkan dan saldo kuota disesuaikan.' : 'Status permohonan telah ditolak.')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible($isAdmin),
                ]),
            ]);
    }
}
