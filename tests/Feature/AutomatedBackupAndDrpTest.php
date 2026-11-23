<?php

namespace Tests\Feature;

use App\Filament\Resources\SystemBackupRecords\SystemBackupRecordResource;
use App\Models\SystemBackupRecord;
use App\Models\User;
use App\Services\BackupService;
use Carbon\Carbon;
use Database\Seeders\SampleFiveEmployeesSeeder;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class AutomatedBackupAndDrpTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $atasanUser;

    protected User $supervisorUser;

    protected User $karyawanUser;

    protected BackupService $backupService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->seed(SampleFiveEmployeesSeeder::class);

        $this->adminUser = User::where('username', 'admin')->firstOrFail();
        $this->atasanUser = User::where('username', 'atasan')->firstOrFail();
        $this->supervisorUser = User::where('username', 'supervisor')->firstOrFail();
        $this->karyawanUser = User::where('username', 'ppnpn01')->firstOrFail();
        $this->karyawanUser->update(['must_change_password' => false]);

        $this->backupService = app(BackupService::class);
    }

    protected function tearDown(): void
    {
        // Clean up created test backup archives
        $backupDir = storage_path('app/backups');
        if (File::exists($backupDir)) {
            $files = File::files($backupDir);
            foreach ($files as $file) {
                File::delete($file->getRealPath());
            }
        }

        parent::tearDown();
    }

    /**
     * Test BackupService creates valid zip archive, SQL dump, and DB record (US-802).
     */
    public function test_backup_service_creates_valid_zip_archive_and_database_record(): void
    {
        $record = $this->backupService->createBackup(SystemBackupRecord::TYPE_FULL, $this->adminUser);

        $this->assertInstanceOf(SystemBackupRecord::class, $record);
        $this->assertDatabaseHas('system_backup_records', [
            'id' => $record->id,
            'filename' => $record->filename,
            'backup_type' => SystemBackupRecord::TYPE_FULL,
        ]);

        $this->assertTrue($record->fileExistsOnDisk());
        $this->assertTrue(File::exists("{$record->file_path}.sha256"));
        $this->assertTrue($record->verifyChecksumIntegrity());

        // Verify contents of the zip archive
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($record->file_path) === true);
        $this->assertNotFalse($zip->locateName('database-dump.sql'));
        $this->assertNotFalse($zip->locateName('metadata.json'));

        $metadataContent = $zip->getFromName('metadata.json');
        $this->assertIsString($metadataContent);
        $metadata = json_decode($metadataContent, true);
        $this->assertEquals(SystemBackupRecord::TYPE_FULL, $metadata['backup_type']);
        $zip->close();

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'SYSTEM_BACKUP_CREATED',
            'entity_type' => SystemBackupRecord::class,
            'entity_id' => $record->id,
        ]);
    }

    /**
     * Test checksum verification detects tampered archive and aborts restoration (DRP Security).
     */
    public function test_checksum_verification_detects_tampered_archive(): void
    {
        $record = $this->backupService->createBackup(SystemBackupRecord::TYPE_FULL, $this->adminUser);
        $this->assertTrue($record->verifyChecksumIntegrity());

        // Tamper with the zip file by appending malicious bytes
        File::append($record->file_path, 'TAMPERED_MALICIOUS_DATA');

        $this->assertFalse($record->verifyChecksumIntegrity());

        // Attempting to restore must throw RuntimeException
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Checksum SHA-256 tidak valid');
        $this->backupService->restoreBackup($record->filename);
    }

    /**
     * Test backup service can restore database from backup archive.
     */
    public function test_backup_service_restores_database_successfully(): void
    {
        $record = $this->backupService->createBackup(SystemBackupRecord::TYPE_FULL, $this->adminUser);

        $result = $this->backupService->restoreBackup($record->filename, true);

        $this->assertEquals('SUCCESS', $result['status']);
        $this->assertTrue($result['checksum_verified']);
        $this->assertTrue($record->fresh()->is_verified);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'SYSTEM_BACKUP_RESTORED',
            'entity_type' => SystemBackupRecord::class,
            'entity_id' => $record->id,
        ]);
    }

    /**
     * Test cleanOldBackups purges archives older than retention threshold (30 days).
     */
    public function test_clean_old_backups_purges_archives_older_than_retention_days(): void
    {
        // Create an old backup record
        $oldRecord = $this->backupService->createBackup(SystemBackupRecord::TYPE_FULL, $this->adminUser);

        // Create a recent backup record
        $recentRecord = $this->backupService->createBackup(SystemBackupRecord::TYPE_FULL, $this->adminUser);

        // Manually set oldRecord created_at to 35 days ago after recent backup creation
        DB::table('system_backup_records')
            ->where('id', $oldRecord->id)
            ->update(['created_at' => Carbon::now('Asia/Jakarta')->subDays(35)->toDateTimeString()]);

        $purgedCount = $this->backupService->cleanOldBackups(30);

        $this->assertEquals(1, $purgedCount);
        $this->assertDatabaseMissing('system_backup_records', ['id' => $oldRecord->id]);
        $this->assertFalse(File::exists($oldRecord->file_path));

        $this->assertDatabaseHas('system_backup_records', ['id' => $recentRecord->id]);
        $this->assertTrue(File::exists($recentRecord->file_path));
    }

    /**
     * Test artisan hris:backup-system command executes successfully and sends admin notification.
     */
    public function test_artisan_backup_command_executes_successfully(): void
    {
        $this->artisan('hris:backup-system', ['--type' => 'FULL'])
            ->expectsOutputToContain('Pencadangan sistem selesai 100%')
            ->assertExitCode(0);

        $this->assertDatabaseHas('system_backup_records', [
            'backup_type' => 'FULL',
        ]);
    }

    /**
     * Test artisan hris:restore-backup command executes successfully with force flag.
     */
    public function test_artisan_restore_command_executes_successfully(): void
    {
        $record = $this->backupService->createBackup(SystemBackupRecord::TYPE_FULL, $this->adminUser);

        $this->artisan('hris:restore-backup', [
            'filename' => $record->filename,
            '--force' => true,
        ])
            ->expectsOutputToContain('Pemulihan sistem berhasil diselesaikan!')
            ->assertExitCode(0);

        $this->assertTrue($record->fresh()->is_verified);
    }

    /**
     * Test scheduled daily automated backup is registered at 02:00 WIB.
     */
    public function test_scheduled_backup_is_registered_at_0200_wib(): void
    {
        /** @var Schedule $schedule */
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $backupEvent = $events->first(function (Event $event) {
            return str_contains($event->command ?? '', 'hris:backup-system');
        });

        $this->assertNotNull($backupEvent, 'Perintah hris:backup-system belum terdaftar pada jadwal console schedule.');
        $this->assertEquals('0 2 * * *', $backupEvent->expression);
    }

    /**
     * Test backup download controller authorization (Strictly Admin).
     */
    public function test_backup_download_controller_authorization(): void
    {
        $record = $this->backupService->createBackup(SystemBackupRecord::TYPE_FULL, $this->adminUser);

        // Guest redirected
        $guestResponse = $this->get(route('admin.backups.download', $record));
        $guestResponse->assertRedirect(route('login'));

        // Karyawan forbidden
        $karyawanResponse = $this->actingAs($this->karyawanUser)->get(route('admin.backups.download', $record));
        $karyawanResponse->assertForbidden();

        // Supervisor forbidden
        $supervisorResponse = $this->actingAs($this->supervisorUser)->get(route('admin.backups.download', $record));
        $supervisorResponse->assertForbidden();

        // Admin allowed
        $adminResponse = $this->actingAs($this->adminUser)->get(route('admin.backups.download', $record));
        $adminResponse->assertStatus(200);
        $this->assertEquals('application/zip', $adminResponse->headers->get('Content-Type'));
    }

    /**
     * Test Filament SystemBackupRecordResource authorization.
     */
    public function test_filament_system_backup_resource_authorization(): void
    {
        // Admin
        $this->actingAs($this->adminUser);
        $this->assertTrue(SystemBackupRecordResource::canAccess());

        // Supervisor
        $this->actingAs($this->supervisorUser);
        $this->assertFalse(SystemBackupRecordResource::canAccess());

        // Atasan
        $this->actingAs($this->atasanUser);
        $this->assertFalse(SystemBackupRecordResource::canAccess());

        // Karyawan
        $this->actingAs($this->karyawanUser);
        $this->assertFalse(SystemBackupRecordResource::canAccess());
    }
}
