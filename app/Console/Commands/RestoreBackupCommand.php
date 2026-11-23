<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class RestoreBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hris:restore-backup 
                            {filename : Nama berkas arsip zip backup (contoh: backup-hris-djpb-20261121_020000.zip)}
                            {--force : Lewati dialog konfirmasi interaktif (untuk pengujian otomatis)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pemulihan mandiri basis data dan berkas lampiran dari arsip backup cadangan (Disaster Recovery Plan - US-802)';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $filename = (string) $this->argument('filename');
        $force = (bool) $this->option('force');

        $this->warn('========================================================================');
        $this->warn(' PERINGATAN PEMULIHAN SISTEM (DISASTER RECOVERY PLAN - DRP)             ');
        $this->warn(' Proses ini akan menimpa data dan lampiran dokumen saat ini dengan data  ');
        $this->warn(" yang tersimpan di dalam arsip: {$filename}                             ");
        $this->warn('========================================================================');

        if (! $force && ! $this->confirm('Apakah Anda benar-benar yakin ingin melanjutkan prosedur pemulihan ini?')) {
            $this->info('Prosedur pemulihan sistem dibatalkan oleh pengguna.');

            return self::SUCCESS;
        }

        $this->info('Memulai verifikasi checksum SHA-256 dan ekstraksi arsip cadangan...');

        try {
            $result = $backupService->restoreBackup($filename, $force);

            $this->info('Pemulihan sistem berhasil diselesaikan!');
            $this->info("- Arsip: {$result['filename']}");
            $this->info('- Verifikasi Checksum SHA-256: VALID (100%)');
            $this->info("- Total Tabel Dipulihkan: {$result['tables_restored']}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Pemulihan sistem gagal: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
