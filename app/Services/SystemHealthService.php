<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\SystemBackupRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SystemHealthService
{
    /**
     * Gather comprehensive system health telemetry metrics (FR-901 / US-803).
     *
     * @return array<string, mixed>
     */
    public function getTelemetryMetrics(): array
    {
        $dbMetrics = $this->probeDatabase();
        $storageMetrics = $this->probeStorage();
        $runtimeMetrics = $this->probeRuntime();
        $schedulerMetrics = $this->probeScheduler();
        $operationalMetrics = $this->probeOperationalActivity();

        // Determine overall status
        $isCritical = $dbMetrics['status'] === 'OFFLINE' || $storageMetrics['status'] === 'CRITICAL';
        $isWarning = $dbMetrics['status'] === 'DEGRADED' || $storageMetrics['status'] === 'WARNING';

        if ($isCritical) {
            $overallStatus = 'CRITICAL';
            $overallLabel = 'Gangguan Operasional Kritis Terdeteksi';
            $overallColor = 'danger';
        } elseif ($isWarning) {
            $overallStatus = 'WARNING';
            $overallLabel = 'Peringatan Kapasitas / Latensi Terdeteksi';
            $overallColor = 'warning';
        } else {
            $overallStatus = 'HEALTHY';
            $overallLabel = 'Semua Sistem Beroperasi Normal (All Systems Operational)';
            $overallColor = 'success';
        }

        return [
            'overall_status' => $overallStatus,
            'overall_label' => $overallLabel,
            'overall_color' => $overallColor,
            'checked_at' => Carbon::now('Asia/Jakarta')->translatedFormat('d F Y, H:i:s').' WIB',
            'database' => $dbMetrics,
            'storage' => $storageMetrics,
            'runtime' => $runtimeMetrics,
            'scheduler' => $schedulerMetrics,
            'operational' => $operationalMetrics,
        ];
    }

    /**
     * Probe Database connectivity and response latency.
     *
     * @return array{
     *     connection: string,
     *     driver: string,
     *     status: string,
     *     latency_ms: float,
     *     status_color: string
     * }
     */
    public function probeDatabase(): array
    {
        $driver = (string) config('database.default', 'mysql');
        $startTime = hrtime(true);

        try {
            DB::select('SELECT 1');
            $endTime = hrtime(true);
            $latencyMs = round(($endTime - $startTime) / 1e6, 2);

            $status = $latencyMs > 500 ? 'DEGRADED' : 'ONLINE';
            $color = $latencyMs > 500 ? 'warning' : 'success';

            return [
                'connection' => $driver,
                'driver' => strtoupper($driver),
                'status' => $status,
                'latency_ms' => $latencyMs,
                'status_color' => $color,
            ];
        } catch (\Throwable $e) {
            return [
                'connection' => $driver,
                'driver' => strtoupper($driver),
                'status' => 'OFFLINE',
                'latency_ms' => 0.0,
                'status_color' => 'danger',
            ];
        }
    }

    /**
     * Probe disk storage space and public attachment volume.
     *
     * @return array{
     *     total_space_gb: float,
     *     free_space_gb: float,
     *     used_space_gb: float,
     *     used_percentage: float,
     *     public_files_count: int,
     *     public_size_mb: float,
     *     status: string,
     *     status_color: string
     * }
     */
    public function probeStorage(): array
    {
        $storagePath = storage_path();
        $totalBytes = (float) @disk_total_space($storagePath);
        $freeBytes = (float) @disk_free_space($storagePath);

        if ($totalBytes <= 0) {
            $totalBytes = 100 * 1073741824.0; // fallback 100 GB for virtualized envs
            $freeBytes = 80 * 1073741824.0;
        }

        $usedBytes = max(0.0, $totalBytes - $freeBytes);
        $usedPercentage = round(($usedBytes / $totalBytes) * 100, 1);

        $totalGb = round($totalBytes / 1073741824, 2);
        $freeGb = round($freeBytes / 1073741824, 2);
        $usedGb = round($usedBytes / 1073741824, 2);

        // Scan public attachments
        $publicPath = storage_path('app/public');
        $publicFilesCount = 0;
        $publicSizeBytes = 0;

        if (File::exists($publicPath)) {
            $files = File::allFiles($publicPath);
            $publicFilesCount = count($files);
            foreach ($files as $file) {
                $publicSizeBytes += $file->getSize();
            }
        }

        $publicSizeMb = round($publicSizeBytes / 1048576, 2);

        if ($usedPercentage >= 95.0) {
            $status = 'CRITICAL';
            $color = 'danger';
        } elseif ($usedPercentage >= 80.0) {
            $status = 'WARNING';
            $color = 'warning';
        } else {
            $status = 'HEALTHY';
            $color = 'success';
        }

        return [
            'total_space_gb' => $totalGb,
            'free_space_gb' => $freeGb,
            'used_space_gb' => $usedGb,
            'used_percentage' => $usedPercentage,
            'public_files_count' => $publicFilesCount,
            'public_size_mb' => $publicSizeMb,
            'status' => $status,
            'status_color' => $color,
        ];
    }

    /**
     * Probe PHP runtime version, framework, and memory footprint.
     *
     * @return array<string, mixed>
     */
    public function probeRuntime(): array
    {
        $currentMemoryMb = round(memory_get_usage(true) / 1048576, 2);
        $peakMemoryMb = round(memory_get_peak_usage(true) / 1048576, 2);

        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'os' => PHP_OS,
            'memory_usage_mb' => $currentMemoryMb,
            'memory_peak_mb' => $peakMemoryMb,
            'memory_limit' => ini_get('memory_limit') ?: '512M',
            'max_execution_time' => (ini_get('max_execution_time') ?: '30').'s',
            'status' => 'HEALTHY',
            'status_color' => 'success',
        ];
    }

    /**
     * Probe scheduled background jobs and scheduler daemon status.
     *
     * @return array{
     *     total_jobs: int,
     *     jobs: list<array{command: string, expression: string, description: string}>,
     *     status: string,
     *     status_color: string
     * }
     */
    public function probeScheduler(): array
    {
        /** @var Schedule $schedule */
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $jobs = $events->map(function (Event $event): array {
            $cmd = (string) ($event->command ?? $event->description ?? 'Closure');
            // Clean up php artisan prefix
            $cleanCmd = preg_replace('/^.*artisan[\'"]?\s+/', '', $cmd);

            return [
                'command' => $cleanCmd,
                'expression' => $event->expression,
                'description' => (string) ($event->description ?? 'Jadwal Sistem'),
            ];
        })->values()->all();

        return [
            'total_jobs' => count($jobs),
            'jobs' => $jobs,
            'status' => count($jobs) >= 3 ? 'HEALTHY' : 'WARNING',
            'status_color' => count($jobs) >= 3 ? 'success' : 'warning',
        ];
    }

    /**
     * Probe today's operational throughput (attendances, active employees, logs).
     *
     * @return array<string, mixed>
     */
    public function probeOperationalActivity(): array
    {
        $today = Carbon::today('Asia/Jakarta');

        $attendancesToday = Attendance::whereDate('date', $today)->count();
        $activeEmployees = Employee::where('is_active', true)->count();
        $auditLogs24h = AuditLog::where('created_at', '>=', Carbon::now()->subHours(24))->count();
        $totalBackups = SystemBackupRecord::count();

        $latestBackup = SystemBackupRecord::latest()->first();

        return [
            'attendances_today' => $attendancesToday,
            'active_employees' => $activeEmployees,
            'audit_logs_24h' => $auditLogs24h,
            'total_backups' => $totalBackups,
            'last_backup_time' => $latestBackup ? $latestBackup->created_at?->diffForHumans() : 'Belum Ada',
        ];
    }

    /**
     * Capture application exception and issue unique incident ticket (US-803).
     */
    public function captureIncident(\Throwable $e, ?User $user = null): string
    {
        $code = 'ERR-DJPB-'.strtoupper(Str::random(8));

        try {
            AuditLog::create([
                'user_id' => $user?->id ?? Auth::id(),
                'action' => 'SYSTEM_INCIDENT_CAPTURED',
                'entity_type' => get_class($e),
                'entity_id' => 0,
                'new_values' => json_encode([
                    'incident_code' => $code,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => request()->fullUrl(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]),
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'user_agent' => request()->userAgent() ?? 'System Exception Handler',
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Keep resilient
        }

        return $code;
    }

    /**
     * Look up incident log details by ticket code.
     */
    public function lookupIncident(string $ticketCode): ?AuditLog
    {
        $trimmed = trim(strtoupper($ticketCode));

        return AuditLog::where('action', 'SYSTEM_INCIDENT_CAPTURED')
            ->where('new_values', 'like', "%{$trimmed}%")
            ->latest()
            ->first();
    }
}
