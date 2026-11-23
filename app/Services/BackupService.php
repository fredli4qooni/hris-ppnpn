<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\SystemBackupRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

class BackupService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');

        if (! File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Create automated or manual system backup archive (US-802).
     */
    public function createBackup(string $type = SystemBackupRecord::TYPE_FULL, ?User $user = null): SystemBackupRecord
    {
        $now = Carbon::now('Asia/Jakarta');
        $randomSuffix = substr(bin2hex(random_bytes(4)), 0, 4);
        $timestamp = $now->format('Ymd_His').'_'.$randomSuffix;
        $filename = "backup-hris-djpb-{$timestamp}.zip";
        $zipPath = "{$this->backupDir}/{$filename}";

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Gagal membuat berkas arsip zip pencadangan di: {$zipPath}");
        }

        $totalTables = 0;
        $dbDriver = (string) config('database.default', 'mysql');

        // 1. Export Database
        if ($type === SystemBackupRecord::TYPE_FULL || $type === SystemBackupRecord::TYPE_DATABASE_ONLY) {
            $dumpResult = $this->generateDatabaseDump();
            $zip->addFromString('database-dump.sql', $dumpResult['sql']);
            $totalTables = $dumpResult['tables_count'];
        }

        // 2. Export Storage Attachments
        if ($type === SystemBackupRecord::TYPE_FULL || $type === SystemBackupRecord::TYPE_ATTACHMENTS_ONLY) {
            $this->addDirectoryToZip(storage_path('app/public'), $zip, 'attachments');
        }

        // 3. Metadata manifest
        $manifest = [
            'app_name' => config('app.name', 'HRIS DJPb Lampung'),
            'backup_timestamp' => $now->toIso8601String(),
            'backup_type' => $type,
            'database_driver' => $dbDriver,
            'total_tables' => $totalTables,
            'created_by' => $user?->name ?? 'System Scheduler (02:00 WIB)',
        ];
        $zip->addFromString('metadata.json', json_encode($manifest, JSON_PRETTY_PRINT));

        $zip->close();

        // 4. Compute SHA-256 Checksum
        $sha256 = hash_file('sha256', $zipPath);
        if (! $sha256) {
            throw new \RuntimeException('Gagal menghitung checksum SHA-256 untuk berkas backup.');
        }

        // Save companion .sha256 file
        File::put("{$zipPath}.sha256", $sha256);

        $fileSize = (int) File::size($zipPath);

        // 5. Create SystemBackupRecord in DB
        $record = SystemBackupRecord::create([
            'filename' => $filename,
            'file_path' => $zipPath,
            'file_size_bytes' => $fileSize,
            'sha256_checksum' => $sha256,
            'backup_type' => $type,
            'database_driver' => $dbDriver,
            'total_tables' => $totalTables,
            'is_verified' => false,
            'created_by' => $user?->id,
            'notes' => 'Pencadangan otomatis Disaster Recovery Plan (DRP - US-802)',
        ]);

        // 6. Record Audit Log
        try {
            AuditLog::create([
                'user_id' => $user?->id,
                'action' => 'SYSTEM_BACKUP_CREATED',
                'entity_type' => SystemBackupRecord::class,
                'entity_id' => $record->id,
                'new_values' => json_encode([
                    'filename' => $filename,
                    'file_size_bytes' => $fileSize,
                    'sha256_checksum' => $sha256,
                    'type' => $type,
                ]),
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'user_agent' => request()->userAgent() ?? 'Artisan Scheduler',
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Keep resilient if audit log cannot run
        }

        // 7. Enforce 30-day retention rotation
        $this->cleanOldBackups(30);

        return $record;
    }

    /**
     * Restore database and attachments from backup archive (DRP - US-802).
     *
     * @return array{
     *     status: string,
     *     filename: string,
     *     tables_restored: int,
     *     checksum_verified: bool
     * }
     */
    public function restoreBackup(string $backupFilename, bool $force = false): array
    {
        $record = SystemBackupRecord::where('filename', $backupFilename)->first();
        $zipPath = $record ? $record->file_path : "{$this->backupDir}/{$backupFilename}";

        if (! File::exists($zipPath)) {
            throw new \RuntimeException("Berkas arsip cadangan '{$backupFilename}' tidak ditemukan di storage.");
        }

        // 1. Verify Checksum SHA-256
        $currentHash = hash_file('sha256', $zipPath);
        $expectedHash = $record?->sha256_checksum;

        if (! $expectedHash && File::exists("{$zipPath}.sha256")) {
            $expectedHash = trim(File::get("{$zipPath}.sha256"));
        }

        if ($expectedHash && ! hash_equals($expectedHash, (string) $currentHash)) {
            throw new \RuntimeException("Checksum SHA-256 tidak valid! Berkas arsip cadangan '{$backupFilename}' terindikasi rusak atau mengalami manipulasi.");
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("Tidak dapat membuka arsip zip '{$backupFilename}'.");
        }

        $tempExtractDir = storage_path('app/temp_restore_'.uniqid());
        File::makeDirectory($tempExtractDir, 0755, true);
        $zip->extractTo($tempExtractDir);
        $zip->close();

        $tablesRestored = 0;

        // 2. Restore SQL Dump if present
        $sqlFile = "{$tempExtractDir}/database-dump.sql";
        if (File::exists($sqlFile)) {
            $sql = File::get($sqlFile);
            if (! empty(trim($sql))) {
                $dbDriver = (string) config('database.default', 'mysql');

                try {
                    if ($dbDriver === 'sqlite') {
                        DB::statement('PRAGMA foreign_keys = OFF;');
                    } else {
                        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
                    }

                    // Execute dump statements safely
                    DB::unprepared($sql);
                } finally {
                    if ($dbDriver === 'sqlite') {
                        DB::statement('PRAGMA foreign_keys = ON;');
                    } else {
                        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
                    }
                }

                $tablesRestored = substr_count($sql, '-- TABLE:');
            }
        }

        // 3. Restore Attachments if present
        $attachmentsDir = "{$tempExtractDir}/attachments";
        if (File::exists($attachmentsDir)) {
            $publicStorage = storage_path('app/public');
            File::copyDirectory($attachmentsDir, $publicStorage);
        }

        // Clean up temporary extract folder
        File::deleteDirectory($tempExtractDir);

        // 4. Update verification status
        if ($record) {
            $record->update(['is_verified' => true]);
        }

        // 5. Record Audit Log
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'SYSTEM_BACKUP_RESTORED',
                'entity_type' => SystemBackupRecord::class,
                'entity_id' => $record?->id,
                'new_values' => json_encode([
                    'filename' => $backupFilename,
                    'sha256_verified' => true,
                    'tables_restored' => $tablesRestored,
                ]),
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'user_agent' => request()->userAgent() ?? 'DRP Restore Tool',
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Keep resilient
        }

        return [
            'status' => 'SUCCESS',
            'filename' => $backupFilename,
            'tables_restored' => $tablesRestored,
            'checksum_verified' => true,
        ];
    }

    /**
     * Purge backup records and files older than given calendar days (Rotasi 30 Hari).
     */
    public function cleanOldBackups(int $retentionDays = 30): int
    {
        $cutoffDate = Carbon::now('Asia/Jakarta')->subDays($retentionDays);
        $oldRecords = SystemBackupRecord::where('created_at', '<', $cutoffDate)->get();

        $deletedCount = 0;
        foreach ($oldRecords as $oldRecord) {
            if (File::exists($oldRecord->file_path)) {
                File::delete($oldRecord->file_path);
            }
            if (File::exists("{$oldRecord->file_path}.sha256")) {
                File::delete("{$oldRecord->file_path}.sha256");
            }
            $oldRecord->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    /**
     * Generate portable SQL dump of application tables.
     *
     * @return array{sql: string, tables_count: int}
     */
    protected function generateDatabaseDump(): array
    {
        $tables = Schema::getTableListing();
        $excludedTables = ['migrations', 'failed_jobs', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches'];
        $dbDriver = (string) config('database.default', 'mysql');

        $sql = "-- ========================================================\n";
        $sql .= "-- HRIS KANWIL DJPB PROVINSI LAMPUNG - DATABASE DUMP\n";
        $sql .= '-- Tanggal Dump: '.Carbon::now('Asia/Jakarta')->toDateTimeString()." WIB\n";
        $sql .= "-- ========================================================\n\n";

        $tablesCount = 0;
        $insertVerb = ($dbDriver === 'sqlite') ? 'INSERT OR REPLACE INTO' : 'REPLACE INTO';

        foreach ($tables as $table) {
            // Strip any schema prefix, e.g. "main.audit_logs" -> "audit_logs"
            $cleanTable = str_contains($table, '.') ? explode('.', $table)[1] : $table;

            if (in_array($cleanTable, $excludedTables, true)) {
                continue;
            }

            $tablesCount++;
            $sql .= "-- TABLE: {$cleanTable}\n";

            try {
                $rows = DB::table($cleanTable)->get();

                if ($rows->isNotEmpty()) {
                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        $columns = array_keys($rowArray);
                        $escapedColumns = array_map(fn ($col) => "`{$col}`", $columns);

                        $values = array_map(function ($val) {
                            if ($val === null) {
                                return 'NULL';
                            }

                            return "'".addslashes((string) $val)."'";
                        }, array_values($rowArray));

                        $sql .= sprintf(
                            "%s `%s` (%s) VALUES (%s);\n",
                            $insertVerb,
                            $cleanTable,
                            implode(', ', $escapedColumns),
                            implode(', ', $values)
                        );
                    }
                }
            } catch (\Throwable) {
                // Ignore any table read anomalies gracefully
            }

            $sql .= "\n";
        }

        return [
            'sql' => $sql,
            'tables_count' => $tablesCount,
        ];
    }

    /**
     * Recursively add a directory's files into a ZipArchive.
     */
    protected function addDirectoryToZip(string $sourcePath, ZipArchive $zip, string $zipSubdir = ''): void
    {
        if (! File::exists($sourcePath)) {
            return;
        }

        $files = File::allFiles($sourcePath);
        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $zipEntryPath = $zipSubdir ? "{$zipSubdir}/{$relativePath}" : $relativePath;
            $zip->addFile($file->getRealPath(), $zipEntryPath);
        }
    }
}
