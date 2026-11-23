<?php

namespace App\Filament\Resources\SystemBackupRecords\Tables;

use App\Models\SystemBackupRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SystemBackupRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu Pencadangan')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),

                TextColumn::make('filename')
                    ->label('Nama Berkas Arsip')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('backup_type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        SystemBackupRecord::TYPE_FULL => 'success',
                        SystemBackupRecord::TYPE_DATABASE_ONLY => 'info',
                        SystemBackupRecord::TYPE_ATTACHMENTS_ONLY => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('formatted_size_bytes')
                    ->label('Ukuran Arsip')
                    ->fontFamily('mono')
                    ->alignCenter(),

                TextColumn::make('total_tables')
                    ->label('Tabel DB')
                    ->suffix(' Tabel')
                    ->fontFamily('mono')
                    ->alignCenter(),

                TextColumn::make('sha256_checksum')
                    ->label('Checksum SHA-256')
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyMessage('Checksum SHA-256 disalin ke clipboard')
                    ->limit(16),

                IconColumn::make('is_verified')
                    ->label('Terverifikasi')
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('creator.name')
                    ->label('Inisiator')
                    ->placeholder('Scheduler Otomatis (02:00 WIB)'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('backup_type')
                    ->label('Filter Tipe Backup')
                    ->options([
                        SystemBackupRecord::TYPE_FULL => 'Full (DB & Lampiran)',
                        SystemBackupRecord::TYPE_DATABASE_ONLY => 'Database Saja',
                        SystemBackupRecord::TYPE_ATTACHMENTS_ONLY => 'Lampiran Saja',
                    ]),
            ])
            ->actions([
                Action::make('verify_checksum')
                    ->label('Cek SHA-256')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->color('info')
                    ->action(function (SystemBackupRecord $record): void {
                        $isValid = $record->verifyChecksumIntegrity();

                        if ($isValid) {
                            $record->update(['is_verified' => true]);

                            Notification::make()
                                ->title('Verifikasi Checksum Berhasil (100% Cocok)')
                                ->body("Berkas {$record->filename} tervalidasi utuh sesuai rekaman SHA-256.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Peringatan: Checksum Tidak Cocok / Berkas Hilang!')
                                ->body("Berkas {$record->filename} gagal diverifikasi integritasnya.")
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('download')
                    ->label('Unduh (ZIP)')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('primary')
                    ->url(fn (SystemBackupRecord $record): string => route('admin.backups.download', $record), shouldOpenInNewTab: true),
            ]);
    }
}
