<?php

namespace App\Filament\Resources\ViolationDrafts\Tables;

use App\Models\User;
use App\Models\ViolationCatalog;
use App\Models\ViolationDraft;
use App\Services\ViolationDraftService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ViolationDraftsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('incident_date')
                    ->label('Tgl Kejadian')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('employee.full_name')
                    ->label('Pegawai')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (ViolationDraft $record): string => $record->employee?->employee_code.' • '.($record->employee?->position?->name ?? 'PPNPN')),

                TextColumn::make('trigger_type')
                    ->label('Pemicu Sistem')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ViolationDraft::TRIGGER_CONSECUTIVE_ALPHA => 'danger',
                        ViolationDraft::TRIGGER_REPEATED_PSW => 'warning',
                        default => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ViolationDraft::TRIGGER_CONSECUTIVE_ALPHA => 'Alpa >= 2 Hari',
                        ViolationDraft::TRIGGER_REJECTED_ATTENDANCE_CORRECTION => 'Lupa Absen Ditolak',
                        ViolationDraft::TRIGGER_REJECTED_FALLBACK => 'Fallback Ditolak',
                        ViolationDraft::TRIGGER_REPEATED_PSW => 'PSW Berulang (>= 3x)',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('catalog.clause_number')
                    ->label('Butir SPK')
                    ->badge()
                    ->color('primary')
                    ->placeholder('-'),

                TextColumn::make('catalog.points')
                    ->label('Poin SP')
                    ->numeric()
                    ->suffix(' Poin')
                    ->weight('bold')
                    ->color(fn ($state): string => (int) $state >= 20 ? 'danger' : 'warning')
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ViolationDraft::STATUS_CONFIRMED => 'danger',
                        ViolationDraft::STATUS_DISMISSED => 'gray',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ViolationDraft::STATUS_CONFIRMED => 'Disahkan Resmi',
                        ViolationDraft::STATUS_DISMISSED => 'Dibatalkan',
                        default => 'Menunggu Telaah',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('trigger_type')
                    ->label('Pemicu')
                    ->options([
                        ViolationDraft::TRIGGER_CONSECUTIVE_ALPHA => 'Alpa Berturut-turut (>= 2 Hari)',
                        ViolationDraft::TRIGGER_REJECTED_ATTENDANCE_CORRECTION => 'Koreksi Lupa Absen Ditolak',
                        ViolationDraft::TRIGGER_REJECTED_FALLBACK => 'Presensi Fallback Ditolak',
                        ViolationDraft::TRIGGER_REPEATED_PSW => 'Pulang Sebelum Waktu (PSW) Berulang (>= 3x)',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        ViolationDraft::STATUS_PENDING => 'Menunggu Telaah',
                        ViolationDraft::STATUS_CONFIRMED => 'Disahkan Resmi',
                        ViolationDraft::STATUS_DISMISSED => 'Dibatalkan',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                // Action 1: Confirm Draft into Active Employee Violation
                Action::make('confirm_violation')
                    ->label('Sahkan Pelanggaran')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('danger')
                    ->visible(function (ViolationDraft $record): bool {
                        /** @var User|null $user */
                        $user = Auth::user();
                        if (! $user) {
                            return false;
                        }

                        $canConfirm = $user->hasRole('supervisor') || $user->hasRole('admin');

                        return $canConfirm && $record->isPending();
                    })
                    ->modalHeading(fn (ViolationDraft $record): string => 'Konfirmasi Penegakan Disiplin: '.$record->employee?->full_name)
                    ->modalDescription(fn (ViolationDraft $record): string => 'Pemicu: '.$record->getTriggerLabel().' | Tanggal: '.$record->incident_date->translatedFormat('d F Y'))
                    ->schema([
                        Select::make('violation_catalog_id')
                            ->label('Pasal Pelanggaran SPK (Lampiran II)')
                            ->options(fn () => ViolationCatalog::where('is_active', true)
                                ->orderBy('clause_number')
                                ->get()
                                ->mapWithKeys(fn (ViolationCatalog $c) => [$c->id => "{$c->clause_number}: {$c->description} ({$c->points} Poin)"]))
                            ->default(fn (ViolationDraft $record) => $record->violation_catalog_id)
                            ->searchable()
                            ->required(),

                        Textarea::make('notes')
                            ->label('Uraian Pelanggaran Resmi')
                            ->default(fn (ViolationDraft $record) => $record->auto_generated_notes)
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (ViolationDraft $record, array $data): void {
                        /** @var User $user */
                        $user = Auth::user();

                        /** @var ViolationDraftService $service */
                        $service = app(ViolationDraftService::class);
                        $violation = $service->confirmDraft($record, $user, $data);

                        Notification::make()
                            ->title('Pelanggaran Berhasil Disahkan')
                            ->body("Pelanggaran telah dicatat resmi dengan beban {$violation->points} poin disiplin. Akumulasi sanksi telah diperbarui.")
                            ->danger()
                            ->send();
                    }),

                // Action 2: Dismiss Draft
                Action::make('dismiss_draft')
                    ->label('Batalkan Draf')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('gray')
                    ->visible(function (ViolationDraft $record): bool {
                        /** @var User|null $user */
                        $user = Auth::user();
                        if (! $user) {
                            return false;
                        }

                        $canDismiss = $user->hasRole('supervisor') || $user->hasRole('admin');

                        return $canDismiss && $record->isPending();
                    })
                    ->modalHeading(fn (ViolationDraft $record): string => 'Batalkan Draf Pelanggaran: '.$record->employee?->full_name)
                    ->modalDescription('Apakah kejadian indisipliner ini memiliki izin atau penugasan dinas yang sah?')
                    ->schema([
                        Textarea::make('dismissal_reason')
                            ->label('Alasan Pembatalan Draf Pelanggaran')
                            ->required()
                            ->placeholder('Jelaskan alasan pembebasan/pembatalan (misal: telah menyerahkan memo tugas dinas luar dari atasan)...')
                            ->rows(3),
                    ])
                    ->action(function (ViolationDraft $record, array $data): void {
                        /** @var User $user */
                        $user = Auth::user();

                        /** @var ViolationDraftService $service */
                        $service = app(ViolationDraftService::class);
                        $service->dismissDraft($record, $user, $data['dismissal_reason']);

                        Notification::make()
                            ->title('Draf Pelanggaran Dibatalkan')
                            ->body('Draf pelanggaran telah dibatalkan dengan catatan alasan dinas.')
                            ->info()
                            ->send();
                    }),
            ]);
    }
}
