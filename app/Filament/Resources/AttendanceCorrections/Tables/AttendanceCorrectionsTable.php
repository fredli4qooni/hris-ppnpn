<?php

namespace App\Filament\Resources\AttendanceCorrections\Tables;

use App\Models\AttendanceCorrection;
use App\Models\User;
use App\Services\AttendanceCorrectionService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AttendanceCorrectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tgl Pengajuan')
                    ->dateTime('d M Y')
                    ->sortable(),

                TextColumn::make('employee.full_name')
                    ->label('Pegawai')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (AttendanceCorrection $record): string => $record->employee?->employee_code.' • '.($record->employee?->position?->name ?? 'PPNPN')),

                TextColumn::make('attendance_date')
                    ->label('Tgl Lupa Absen')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('correction_type')
                    ->label('Jenis')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        AttendanceCorrection::TYPE_CHECK_IN => 'Masuk',
                        AttendanceCorrection::TYPE_CHECK_OUT => 'Pulang',
                        default => 'Masuk & Pulang',
                    })
                    ->sortable(),

                TextColumn::make('suggested_check_in_at')
                    ->label('Jam Masuk')
                    ->time('H:i')
                    ->placeholder('-'),

                TextColumn::make('suggested_check_out_at')
                    ->label('Jam Pulang')
                    ->time('H:i')
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        AttendanceCorrection::STATUS_APPROVED => 'success',
                        AttendanceCorrection::STATUS_APPROVED_SUPERVISOR => 'info',
                        AttendanceCorrection::STATUS_REJECTED => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        AttendanceCorrection::STATUS_APPROVED => 'Disetujui Sah',
                        AttendanceCorrection::STATUS_APPROVED_SUPERVISOR => 'Telaah Supervisor',
                        AttendanceCorrection::STATUS_REJECTED => 'Ditolak',
                        default => 'Menunggu Supervisor',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        AttendanceCorrection::STATUS_PENDING => 'Menunggu Supervisor',
                        AttendanceCorrection::STATUS_APPROVED_SUPERVISOR => 'Menunggu Atasan',
                        AttendanceCorrection::STATUS_APPROVED => 'Disetujui Sah',
                        AttendanceCorrection::STATUS_REJECTED => 'Ditolak',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                // Action 1: Supervisor Review (Tahap 1)
                Action::make('supervisor_review')
                    ->label('Telaah Supervisor')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('warning')
                    ->visible(function (AttendanceCorrection $record): bool {
                        /** @var User|null $user */
                        $user = Auth::user();
                        if (! $user) {
                            return false;
                        }

                        $canReview = $user->hasRole('supervisor') || $user->hasRole('admin');

                        return $canReview && $record->isPending();
                    })
                    ->modalHeading(fn (AttendanceCorrection $record): string => 'Telaah Supervisor: '.$record->employee?->full_name)
                    ->modalDescription(fn (AttendanceCorrection $record): string => 'Tanggal Lupa Absen: '.$record->attendance_date->translatedFormat('d F Y').' — '.$record->work_summary)
                    ->schema([
                        Radio::make('decision')
                            ->label('Keputusan Telaah')
                            ->options([
                                'APPROVE' => 'Setujui (Teruskan ke Atasan/Kasubbag)',
                                'REJECT' => 'Tolak Pengajuan',
                            ])
                            ->required()
                            ->default('APPROVE'),

                        Textarea::make('notes')
                            ->label('Catatan Telaah')
                            ->placeholder('Tambahkan catatan verifikasi aktivitas lapangan...')
                            ->rows(3),
                    ])
                    ->action(function (AttendanceCorrection $record, array $data): void {
                        /** @var User $user */
                        $user = Auth::user();
                        $isApproved = ($data['decision'] === 'APPROVE');

                        app(AttendanceCorrectionService::class)->reviewBySupervisor(
                            $record,
                            $user,
                            $isApproved,
                            $data['notes'] ?? null
                        );

                        Notification::make()
                            ->title($isApproved ? 'Pengajuan Disetujui Tahap 1' : 'Pengajuan Ditolak')
                            ->body($isApproved ? 'Pengajuan telah diteruskan ke Atasan (Kasubbag Umum/PPK).' : 'Pengajuan telah ditolak.')
                            ->color($isApproved ? 'success' : 'danger')
                            ->send();
                    }),

                // Action 2: Atasan Final Decision (Tahap 2)
                Action::make('atasan_decision')
                    ->label('Putusan Atasan')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->visible(function (AttendanceCorrection $record): bool {
                        /** @var User|null $user */
                        $user = Auth::user();
                        if (! $user) {
                            return false;
                        }

                        $isAtasan = $user->hasRole('atasan');
                        $isAdmin = $user->hasRole('admin');

                        if ($record->isApproved() || $record->isRejected()) {
                            return false;
                        }

                        return ($isAtasan && $record->isApprovedSupervisor()) || ($isAdmin && ($record->isApprovedSupervisor() || $record->isPending()));
                    })
                    ->modalHeading(fn (AttendanceCorrection $record): string => 'Putusan Final Atasan: '.$record->employee?->full_name)
                    ->modalDescription(fn (AttendanceCorrection $record): string => 'Tanggal: '.$record->attendance_date->translatedFormat('d F Y').' | Ringkasan: '.$record->work_summary)
                    ->schema([
                        Radio::make('decision')
                            ->label('Keputusan Final')
                            ->options([
                                'APPROVE' => 'Sahkan Kehadiran (Anulir Status Alpa)',
                                'REJECT' => 'Tolak Pengajuan',
                            ])
                            ->required()
                            ->default('APPROVE'),

                        Textarea::make('notes')
                            ->label('Catatan Putusan')
                            ->placeholder('Catatan pejabat penilai / Kasubbag Umum...')
                            ->rows(3),
                    ])
                    ->action(function (AttendanceCorrection $record, array $data): void {
                        /** @var User $user */
                        $user = Auth::user();
                        $isApproved = ($data['decision'] === 'APPROVE');

                        app(AttendanceCorrectionService::class)->decideByAtasan(
                            $record,
                            $user,
                            $isApproved,
                            $data['notes'] ?? null
                        );

                        Notification::make()
                            ->title($isApproved ? 'Kehadiran Resmi Disahkan' : 'Pengajuan Ditolak')
                            ->body($isApproved ? 'Status kehadiran telah diperbarui dan penandaan Alpa dianulir.' : 'Pengajuan ditolak secara final.')
                            ->color($isApproved ? 'success' : 'danger')
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
