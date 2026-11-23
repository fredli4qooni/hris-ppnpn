<?php

namespace App\Filament\Resources\AttendanceFallbacks\Tables;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AttendanceFallbacksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('employee.full_name')
                    ->label('Pegawai')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Attendance $record): string => $record->employee?->employee_code.' • '.($record->position?->name ?? 'PPNPN')),

                TextColumn::make('check_in_at')
                    ->label('Jam Masuk')
                    ->time('H:i')
                    ->description(fn (Attendance $record): string => $record->status)
                    ->sortable(),

                TextColumn::make('fallback_reason')
                    ->label('Alasan Kendala')
                    ->wrap()
                    ->limit(50),

                TextColumn::make('check_in_distance_meters')
                    ->label('Jarak GPS')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' m')
                    ->badge()
                    ->color(fn ($state): string => (float) $state <= 100.0 ? 'success' : 'danger'),

                ImageColumn::make('check_in_photo_path')
                    ->label('Swafoto')
                    ->disk('public')
                    ->circular(),

                TextColumn::make('fallback_status')
                    ->label('Status Verifikasi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Attendance::FALLBACK_APPROVED => 'success',
                        Attendance::FALLBACK_REJECTED => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Attendance::FALLBACK_APPROVED => 'Disetujui Sah',
                        Attendance::FALLBACK_REJECTED => 'Ditolak',
                        default => 'Menunggu Verifikasi',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('fallback_status')
                    ->label('Status')
                    ->options([
                        Attendance::FALLBACK_PENDING => 'Menunggu Verifikasi',
                        Attendance::FALLBACK_APPROVED => 'Disetujui Sah',
                        Attendance::FALLBACK_REJECTED => 'Ditolak',
                    ]),
            ])
            ->recordActions([
                // Approve Fallback Action
                Action::make('approve_fallback')
                    ->label('Setujui')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pengesahan Presensi Darurat (Fallback)')
                    ->modalDescription(fn (Attendance $record): string => 'Apakah Anda yakin ingin mengesahkan presensi darurat '.$record->employee?->full_name.' pada '.$record->date->translatedFormat('d F Y').'?')
                    ->visible(fn (Attendance $record): bool => $record->fallback_status === Attendance::FALLBACK_PENDING)
                    ->action(function (Attendance $record): void {
                        /** @var User $user */
                        $user = Auth::user();

                        $record->update([
                            'fallback_status' => Attendance::FALLBACK_APPROVED,
                            'fallback_verified_by' => $user->id,
                            'fallback_verified_at' => Carbon::now('Asia/Jakarta'),
                            'notes' => trim(($record->notes ?? '').' | Disahkan oleh '.$user->name),
                        ]);

                        AuditLog::create([
                            'user_id' => $user->id,
                            'action' => 'ATTENDANCE_FALLBACK_APPROVED',
                            'entity_type' => Attendance::class,
                            'entity_id' => $record->id,
                            'new_values' => [
                                'fallback_status' => Attendance::FALLBACK_APPROVED,
                                'verified_by' => $user->id,
                            ],
                            'ip_address' => request()?->ip(),
                            'user_agent' => request()?->userAgent(),
                        ]);

                        Notification::make()
                            ->title('Presensi Fallback Disahkan')
                            ->body('Presensi darurat pegawai telah sah dan terverifikasi.')
                            ->success()
                            ->send();
                    }),

                // Reject Fallback Action
                Action::make('reject_fallback')
                    ->label('Tolak')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->visible(fn (Attendance $record): bool => $record->fallback_status === Attendance::FALLBACK_PENDING)
                    ->schema([
                        Textarea::make('notes')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->placeholder('Jelaskan mengapa presensi darurat ditolak...'),
                    ])
                    ->action(function (Attendance $record, array $data): void {
                        /** @var User $user */
                        $user = Auth::user();

                        $record->update([
                            'fallback_status' => Attendance::FALLBACK_REJECTED,
                            'fallback_verified_by' => $user->id,
                            'fallback_verified_at' => Carbon::now('Asia/Jakarta'),
                            'fallback_notes' => $data['notes'],
                            'status' => Attendance::STATUS_ALPHA,
                            'notes' => 'Presensi Fallback Ditolak: '.$data['notes'],
                        ]);

                        AuditLog::create([
                            'user_id' => $user->id,
                            'action' => 'ATTENDANCE_FALLBACK_REJECTED',
                            'entity_type' => Attendance::class,
                            'entity_id' => $record->id,
                            'new_values' => [
                                'fallback_status' => Attendance::FALLBACK_REJECTED,
                                'notes' => $data['notes'],
                            ],
                            'ip_address' => request()?->ip(),
                            'user_agent' => request()?->userAgent(),
                        ]);

                        Notification::make()
                            ->title('Presensi Fallback Ditolak')
                            ->body('Presensi ditandai sebagai tidak sah (Alpa).')
                            ->danger()
                            ->send();
                    }),
            ])
            ->defaultSort('date', 'desc');
    }
}
