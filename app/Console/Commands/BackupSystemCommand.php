<?php

namespace App\Console\Commands;

use App\Models\SystemBackupRecord;
use App\Models\User;
use App\Services\BackupService;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class BackupSystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hris:backup-system 
                            {--type=FULL : Tipe pencadangan: FULL, DATABASE_ONLY, ATTACHMENTS_ONLY}
                            {--retention=30 : Ambang batas retensi penghapusan arsip usang dalam hari}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pencadangan otomatis harian basis data dan berkas lampiran sistem (Disaster Recovery Plan - US-802)';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $this->info('Memulai prosedur pencadangan sistem HRIS Kanwil DJPb Provinsi Lampung (DRP)...');

        $type = strtoupper((string) $this->option('type'));
        $retention = (int) $this->option('retention');

        if (! in_array($type, [SystemBackupRecord::TYPE_FULL, SystemBackupRecord::TYPE_DATABASE_ONLY, SystemBackupRecord::TYPE_ATTACHMENTS_ONLY], true)) {
            $this->error("Tipe backup tidak valid: {$type}. Pilihan: FULL, DATABASE_ONLY, ATTACHMENTS_ONLY.");

            return self::FAILURE;
        }

        try {
            $record = $backupService->createBackup($type);

            $this->table(
                ['Parameter', 'Keterangan'],
                [
                    ['Nama Berkas', $record->filename],
                    ['Ukuran Berkas', $record->formatted_size_bytes],
                    ['Tipe Backup', $record->backup_type],
                    ['Total Tabel Database', (string) $record->total_tables],
                    ['Checksum SHA-256', $record->sha256_checksum],
                    ['Lokasi Berkas', $record->file_path],
                ]
            );

            // Clean old archives
            $purged = $backupService->cleanOldBackups($retention);
            if ($purged > 0) {
                $this->info("Rotasi Retensi: Berhasil membersihkan {$purged} berkas arsip usang (> {$retention} hari).");
            }

            // In-app Filament notifications to admins
            /** @var Collection<int, User> $adminUsers */
            $adminUsers = User::role('admin')->get();
            if ($adminUsers->isNotEmpty()) {
                Notification::make()
                    ->title('Pencadangan Sistem Berhasil (DRP)')
                    ->body("Arsip {$record->filename} ({$record->formatted_size_bytes}) berhasil dibuat dengan integritas SHA-256 tervalidasi.")
                    ->success()
                    ->sendToDatabase($adminUsers);
            }

            $this->info('Pencadangan sistem selesai 100% dengan integritas data tervalidasi.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Pencadangan sistem gagal: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
