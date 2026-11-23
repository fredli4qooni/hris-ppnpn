<?php

namespace App\Filament\Resources\LateExceptions\Tables;

use App\Models\LateException;
use App\Models\User;
use App\Services\LateExceptionService;
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

class LateExceptionsTable
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
                    ->description(fn (LateException $record): string => $record->employee?->employee_code.' • '.($record->employee?->position?->name ?? 'PPNPN')),

                TextColumn::make('incident_date')
                    ->label('Tgl Kejadian')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Kategori Kahar')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        LateException::CATEGORY_DISASTER => 'Bencana Alam',
                        LateException::CATEGORY_ACCIDENT => 'Kecelakaan Lalu Lintas',
                        LateException::CATEGORY_FAMILY_BEREAVEMENT => 'Musibah Keluarga',
                        default => 'Kondisi Kahar Lainnya',
                    })
                    ->sortable(),

                TextColumn::make('incident_time')
                    ->label('Waktu')
                    ->time('H:i')
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        LateException::STATUS_APPROVED => 'success',
                        LateException::STATUS_APPROVED_SUPERVISOR => 'info',
                        LateException::STATUS_REJECTED, LateException::STATUS_REJECTED_SUPERVISOR => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        LateException::STATUS_APPROVED => 'Disetujui Sah',
                        LateException::STATUS_APPROVED_SUPERVISOR => 'Telaah Supervisor',
                        LateException::STATUS_REJECTED, LateException::STATUS_REJECTED_SUPERVISOR => 'Ditolak',
                        default => 'Menunggu Supervisor',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori Kahar')
                    ->options([
                        LateException::CATEGORY_DISASTER => 'Bencana Alam',
                        LateException::CATEGORY_ACCIDENT => 'Kecelakaan Lalu Lintas',
                        LateException::CATEGORY_FAMILY_BEREAVEMENT => 'Musibah Keluarga',
                        LateException::CATEGORY_OTHER => 'Kondisi Kahar Lainnya',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        LateException::STATUS_PENDING => 'Menunggu Supervisor',
                        LateException::STATUS_APPROVED_SUPERVISOR => 'Menunggu Kasubag / PPK',
                        LateException::STATUS_APPROVED => 'Disetujui Sah',
                        LateException::STATUS_REJECTED => 'Ditolak',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                // Action 1: Supervisor Review (Tahap 1)
                Action::make('supervisor_review')
                    ->label('Telaah Supervisor')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('warning')
                    ->visible(function (LateException $record): bool {
                        /** @var User|null $user */
                        $user = Auth::user();
                        if (! $user) {
                            return false;
                        }

                        $canReview = $user->hasRole('supervisor') || $user->hasRole('admin');

                        return $canReview && $record->status === LateException::STATUS_PENDING;
                    })
                    ->modalHeading(fn (LateException $record): string => 'Telaah Supervisor: '.$record->employee?->full_name)
                    ->modalDescription(fn (LateException $record): string => 'Tanggal Kejadian Kahar: '.$record->incident_date->translatedFormat('d F Y').' — '.$record->getCategoryLabel())
                    ->schema([
                        Radio::make('decision')
                            ->label('Hasil Verifikasi Fakta Lapangan')
                            ->options([
                                'APPROVE' => 'Terverifikasi Sah (Teruskan ke Kasubbag Umum / PPK)',
                                'REJECT' => 'Tolak Permohonan (Fakta tidak sesuai / tidak valid)',
                            ])
                            ->required()
                            ->default('APPROVE'),

                        Textarea::make('notes')
                            ->label('Catatan Telaah Supervisor Lapangan')
                            ->required()
                            ->placeholder('Berikan keterangan hasil pemeriksaan fakta lapangan/kondisi lalu lintas/situasi musibah...')
                            ->rows(3),
                    ])
                    ->action(function (LateException $record, array $data): void {
                        /** @var User $user */
                        $user = Auth::user();
                        $isApproved = ($data['decision'] === 'APPROVE');

                        /** @var LateExceptionService $service */
                        $service = app(LateExceptionService::class);
                        $service->reviewBySupervisor($record, $user, $isApproved, $data['notes']);

                        Notification::make()
                            ->title($isApproved ? 'Dispensasi Kahar Diteruskan' : 'Dispensasi Kahar Ditolak')
                            ->body($isApproved ? 'Pengajuan berhasil diverifikasi dan diteruskan ke Kasubbag Umum.' : 'Pengajuan telah ditolak pada tahap telaah lapangan.')
                            ->color($isApproved ? 'info' : 'danger')
                            ->send();
                    }),

                // Action 2: Atasan Final Decision (Tahap 2)
                Action::make('atasan_decision')
                    ->label('Keputusan Pembebasan')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->color('success')
                    ->visible(function (LateException $record): bool {
                        /** @var User|null $user */
                        $user = Auth::user();
                        if (! $user) {
                            return false;
                        }

                        $canDecide = $user->hasRole('atasan') || $user->hasRole('admin');

                        return $canDecide && in_array($record->status, [
                            LateException::STATUS_APPROVED_SUPERVISOR,
                            LateException::STATUS_PENDING,
                        ], true);
                    })
                    ->modalHeading(fn (LateException $record): string => 'Penetapan Pembebasan Sanksi TL: '.$record->employee?->full_name)
                    ->modalDescription(fn (LateException $record): string => 'Kategori: '.$record->getCategoryLabel().' | Tanggal: '.$record->incident_date->translatedFormat('d F Y'))
                    ->schema([
                        Radio::make('decision')
                            ->label('Keputusan Pembebasan Sanksi Potongan TL')
                            ->options([
                                'APPROVE' => 'Setujui Pembebasan Sanksi (Potongan TL Dianulir 0%)',
                                'REJECT' => 'Tolak Pembebasan (Tetap Dikenai Potongan TL Normal)',
                            ])
                            ->required()
                            ->default('APPROVE'),

                        Textarea::make('notes')
                            ->label('Dasar Pertimbangan / Catatan Keputusan Kasubbag Umum')
                            ->required()
                            ->placeholder('Tuliskan dasar pertimbangan persetujuan/penolakan pembebasan sanksi...')
                            ->rows(3),
                    ])
                    ->action(function (LateException $record, array $data): void {
                        /** @var User $user */
                        $user = Auth::user();
                        $isApproved = ($data['decision'] === 'APPROVE');

                        /** @var LateExceptionService $service */
                        $service = app(LateExceptionService::class);
                        $service->decideByAtasan($record, $user, $isApproved, $data['notes']);

                        Notification::make()
                            ->title($isApproved ? 'Dispensasi Sanksi Kahar Disahkan' : 'Dispensasi Ditolak')
                            ->body($isApproved ? 'Potongan keterlambatan pegawai telah dianulir secara sah.' : 'Keputusan penolakan telah dicatat.')
                            ->color($isApproved ? 'success' : 'danger')
                            ->send();
                    }),
            ]);
    }
}
