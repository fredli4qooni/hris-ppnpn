<?php

namespace App\Filament\Resources\SystemBackupRecords\Pages;

use App\Filament\Resources\SystemBackupRecords\SystemBackupRecordResource;
use App\Models\SystemBackupRecord;
use App\Services\BackupService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ListSystemBackupRecords extends ListRecords
{
    protected static string $resource = SystemBackupRecordResource::class;

    protected ?string $heading = 'Pusat Pencadangan Sistem & Disaster Recovery Plan';

    protected ?string $subheading = 'Pencadangan basis data dan berkas lampiran otomatis terjadwal (02:00 WIB) dengan validasi integritas SHA-256 dan rotasi retensi 30 hari kalender.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_backup')
                ->label('Buat Cadangan Manual Sekarang')
                ->icon(Heroicon::OutlinedArchiveBoxArrowDown)
                ->color('success')
                ->modalHeading('Eksekusi Pencadangan Sistem Manual (DRP)')
                ->modalDescription('Pilih cakupan pencadangan yang akan dikompresi ke dalam arsip zip terenkripsi checksum SHA-256.')
                ->schema([
                    Select::make('backup_type')
                        ->label('Cakupan Pencadangan')
                        ->options([
                            SystemBackupRecord::TYPE_FULL => 'Penuh (Basis Data + Seluruh Berkas Lampiran)',
                            SystemBackupRecord::TYPE_DATABASE_ONLY => 'Basis Data Saja (SQL Dump)',
                            SystemBackupRecord::TYPE_ATTACHMENTS_ONLY => 'Berkas Lampiran Saja (Storage Public)',
                        ])
                        ->default(SystemBackupRecord::TYPE_FULL)
                        ->required(),
                ])
                ->action(function (array $data, BackupService $service): void {
                    $user = Auth::user();
                    $record = $service->createBackup($data['backup_type'], $user);

                    Notification::make()
                        ->title('Pencadangan Manual Berhasil')
                        ->body("Arsip {$record->filename} ({$record->formatted_size_bytes}) berhasil disimpan dengan checksum SHA-256 tervalidasi.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
