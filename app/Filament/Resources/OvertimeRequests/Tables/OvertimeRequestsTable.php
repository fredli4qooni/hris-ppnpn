<?php

namespace App\Filament\Resources\OvertimeRequests\Tables;

use App\Models\OvertimeRequest;
use App\Models\User;
use App\Services\OvertimeService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class OvertimeRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_code')
                    ->label('Nomor / Kode')
                    ->state(fn (OvertimeRequest $record): string => $record->getDisplayCode())
                    ->searchable(['letter_number', 'draft_code'])
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('request_type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => $state === OvertimeRequest::TYPE_WITH_LETTER ? 'primary' : 'gray')
                    ->formatStateUsing(fn (OvertimeRequest $record): string => $record->getTypeLabel()),

                TextColumn::make('date_range')
                    ->label('Tanggal Lembur')
                    ->state(fn (OvertimeRequest $record): string => $record->getDateRangeLabel())
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('start_date', $direction)),

                TextColumn::make('employees_summary')
                    ->label('Pegawai')
                    ->state(function (OvertimeRequest $record): string {
                        $names = $record->items->map(fn ($item): string => $item->employee?->full_name ?? '-')->all();
                        if (count($names) <= 2) {
                            return implode(', ', $names);
                        }

                        return $names[0].', '.$names[1].' (+'.(count($names) - 2).' lainnya)';
                    })
                    ->wrap(),

                TextColumn::make('total_hours_sum')
                    ->label('Total Jam')
                    ->state(fn (OvertimeRequest $record): string => ((float) $record->items->sum('duration_hours')).' Jam')
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        OvertimeRequest::STATUS_APPROVED => 'success',
                        OvertimeRequest::STATUS_SUBMITTED => 'warning',
                        OvertimeRequest::STATUS_RETURNED => 'info',
                        OvertimeRequest::STATUS_REJECTED, OvertimeRequest::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (OvertimeRequest $record): string => $record->getStatusLabel()),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        OvertimeRequest::STATUS_DRAFT => 'Draf',
                        OvertimeRequest::STATUS_SUBMITTED => 'Diajukan',
                        OvertimeRequest::STATUS_RETURNED => 'Dikembalikan',
                        OvertimeRequest::STATUS_APPROVED => 'Disetujui',
                        OvertimeRequest::STATUS_REJECTED => 'Ditolak',
                        OvertimeRequest::STATUS_CANCELLED => 'Dibatalkan',
                    ]),

                SelectFilter::make('request_type')
                    ->label('Jenis')
                    ->options([
                        OvertimeRequest::TYPE_WITH_LETTER => 'Surat Tugas (SPTPL)',
                        OvertimeRequest::TYPE_WITHOUT_LETTER => 'Tanpa Surat',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (OvertimeRequest $record): bool => $record->isEditable()),

                Action::make('submit_overtime')
                    ->label('Ajukan')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (OvertimeRequest $record): bool => $record->isEditable())
                    ->action(function (OvertimeRequest $record): void {
                        /** @var User $user */
                        $user = Auth::user();
                        $result = app(OvertimeService::class)->submit($record, $user);

                        $notif = Notification::make()
                            ->title('Pengajuan Lembur Dikirim')
                            ->body("Pengajuan {$result['request']->getDisplayCode()} menunggu persetujuan Atasan.");

                        if ($result['spkl_warning']) {
                            $notif->warning()->body($notif->getBody().' | Pengingat: '.$result['spkl_warning']);
                        } else {
                            $notif->success();
                        }

                        $notif->send();
                    }),

                Action::make('approve_overtime')
                    ->label('Setujui')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(function (OvertimeRequest $record): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return $record->status === OvertimeRequest::STATUS_SUBMITTED
                            && (bool) ($user && ($user->hasRole('atasan') || $user->hasRole('admin')));
                    })
                    ->schema([
                        FileUpload::make('signed_pdf_path')
                            ->label('Unggah PDF Bertanda Tangan TTE (Opsional)')
                            ->disk('public')
                            ->directory('overtime/signed-sptpl')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(5120),
                        Textarea::make('notes')
                            ->label('Catatan Persetujuan (Opsional)')
                            ->rows(2),
                    ])
                    ->action(function (OvertimeRequest $record, array $data): void {
                        /** @var User $user */
                        $user = Auth::user();
                        $result = app(OvertimeService::class)->approve(
                            $record,
                            $user,
                            $data['notes'] ?? null,
                            $data['signed_pdf_path'] ?? null
                        );

                        $notif = Notification::make()
                            ->title('Pengajuan Lembur Disetujui')
                            ->body("Nomor resmi: {$result['request']->getDisplayCode()}.");

                        if ($result['spkl_warning']) {
                            $notif->warning()->body("Nomor resmi: {$result['request']->getDisplayCode()}. Pengingat: {$result['spkl_warning']}");
                        } else {
                            $notif->success();
                        }

                        $notif->send();
                    }),

                Action::make('print_sptpl')
                    ->label('PDF')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('info')
                    ->visible(fn (OvertimeRequest $record): bool => $record->request_type === OvertimeRequest::TYPE_WITH_LETTER)
                    ->url(fn (OvertimeRequest $record): string => route('admin.overtime.request.print', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}
