<?php

namespace App\Filament\Resources\OvertimeMasterOrders\Tables;

use App\Models\OvertimeMasterOrder;
use App\Models\User;
use App\Services\OvertimeService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class OvertimeMasterOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('letter_number')
                    ->label('Nomor SPKL')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('period_label')
                    ->label('Periode')
                    ->state(fn (OvertimeMasterOrder $record): string => $record->getPeriodLabel()),

                TextColumn::make('letter_date')
                    ->label('Tanggal Surat')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('signer_name')
                    ->label('Penandatangan'),

                TextColumn::make('approved_sptpl_count')
                    ->label('Lampiran SPTPL Disetujui')
                    ->state(fn (OvertimeMasterOrder $record): int => $record->overtimeRequests()
                        ->where('status', 'APPROVED')
                        ->count())
                    ->suffix(' Surat')
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        OvertimeMasterOrder::STATUS_ISSUED => 'success',
                        OvertimeMasterOrder::STATUS_PENDING_APPROVAL => 'warning',
                        OvertimeMasterOrder::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (OvertimeMasterOrder $record): string => $record->getStatusLabel()),
            ])
            ->defaultSort('period_year', 'desc')
            ->recordActions([
                Action::make('print_spkl')
                    ->label('PDF SPKL')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('info')
                    ->url(fn (OvertimeMasterOrder $record): string => route('admin.overtime.spkl.print', $record))
                    ->openUrlInNewTab(),

                Action::make('issue_spkl')
                    ->label('Terbitkan')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->visible(function (OvertimeMasterOrder $record): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return ! $record->isIssued() && (bool) ($user && ($user->hasRole('atasan') || $user->hasRole('admin')));
                    })
                    ->schema([
                        FileUpload::make('signed_pdf_path')
                            ->label('Unggah PDF TTE (Opsional)')
                            ->disk('public')
                            ->directory('overtime/spkl')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(5120),
                    ])
                    ->action(function (OvertimeMasterOrder $record, array $data): void {
                        /** @var User $user */
                        $user = Auth::user();
                        app(OvertimeService::class)->issueMasterOrder($record, $user, $data['signed_pdf_path'] ?? null);

                        Notification::make()
                            ->title('SPKL Diterbitkan')
                            ->body("SPKL {$record->letter_number} resmi berstatus Terbit.")
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
            ]);
    }
}
