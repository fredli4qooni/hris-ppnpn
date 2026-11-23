<?php

namespace App\Filament\Resources\EmployeeViolations\Tables;

use App\Models\EmployeeViolation;
use App\Models\User;
use App\Models\ViolationCatalog;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EmployeeViolationsTable
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
                TextColumn::make('incident_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('employee.full_name')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (EmployeeViolation $record): string => $record->employee?->employee_code.' • '.($record->employee?->position?->name ?? 'PPNPN')),

                TextColumn::make('catalog.clause_number')
                    ->label('Klausul')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('catalog.description')
                    ->label('Uraian Butir Pelanggaran')
                    ->limit(50)
                    ->tooltip(fn (EmployeeViolation $record): string => $record->catalog?->description ?? '')
                    ->searchable(),

                TextColumn::make('points')
                    ->label('Poin')
                    ->badge()
                    ->color(fn (int $state): string => $state >= 50 ? 'danger' : ($state >= 20 ? 'warning' : 'primary'))
                    ->suffix(' Poin')
                    ->sortable(),

                TextColumn::make('supervisor.name')
                    ->label('Pencatat')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => $state === EmployeeViolation::STATUS_ACTIVE ? 'success' : 'gray')
                    ->formatStateUsing(fn (string $state): string => $state === EmployeeViolation::STATUS_ACTIVE ? 'Aktif' : 'Dibatalkan'),
            ])
            ->defaultSort('incident_date', 'desc')
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori Pelanggaran')
                    ->relationship('catalog', 'category')
                    ->options([
                        ViolationCatalog::CATEGORY_KEHADIRAN_A => 'Kategori A: Kehadiran',
                        ViolationCatalog::CATEGORY_KEWAJIBAN_B => 'Kategori B: Kewajiban',
                        ViolationCatalog::CATEGORY_KEDISIPLINAN_C => 'Kategori C: Kedisiplinan',
                    ]),

                SelectFilter::make('status')
                    ->label('Status Pelanggaran')
                    ->options([
                        EmployeeViolation::STATUS_ACTIVE => 'Aktif',
                        EmployeeViolation::STATUS_CANCELLED => 'Dibatalkan',
                    ]),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible($isAdmin),
                Action::make('cancelViolation')
                    ->label('Batalkan')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (EmployeeViolation $record): bool => $record->isActive())
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Catatan Pelanggaran')
                    ->modalDescription('Pembatalan pelanggaran akan menonaktifkan poin dari akumulasi sanksi pegawai.')
                    ->form([
                        Textarea::make('cancellation_reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->placeholder('Masukkan alasan resmi pembatalan catatan pelanggaran ini...'),
                    ])
                    ->action(function (EmployeeViolation $record, array $data): void {
                        /** @var int $userId */
                        $userId = Auth::id();
                        $record->cancel((string) $data['cancellation_reason'], $userId);

                        Notification::make()
                            ->title('Pelanggaran Berhasil Dibatalkan')
                            ->body('Poin pelanggaran telah dinonaktifkan dari akumulasi.')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible($isAdmin),
                    ForceDeleteBulkAction::make()
                        ->visible($isAdmin),
                    RestoreBulkAction::make()
                        ->visible($isAdmin),
                ]),
            ]);
    }
}
