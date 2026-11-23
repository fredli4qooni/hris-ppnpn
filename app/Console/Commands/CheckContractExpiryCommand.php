<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class CheckContractExpiryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hris:check-contract-expiry {--force : Periksa dan kirim peringatan untuk seluruh pegawai tanpa memandang ambang batas}';

    /**
     * The console command aliases.
     *
     * @var list<string>
     */
    protected $aliases = ['contracts:check-expiry'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa masa berlaku kontrak SPK pegawai aktif dan kirim peringatan jatuh tempo (H-90, H-60, H-30, H-7)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai pemeriksaan masa berlaku kontrak SPK pegawai PPNPN...');

        /** @var list<int> $thresholds */
        $thresholds = config('hris.contracts.alert_thresholds', [90, 60, 30, 7]);
        $isForce = (bool) $this->option('force');

        /** @var Collection<int, Employee> $employees */
        $employees = Employee::with(['user', 'supervisor', 'position'])
            ->where('is_active', true)
            ->whereNotNull('contract_end_date')
            ->get();

        if ($employees->isEmpty()) {
            $this->info('Tidak ada pegawai aktif dengan tanggal kontrak tercatat.');

            return self::SUCCESS;
        }

        /** @var Collection<int, User> $adminUsers */
        $adminUsers = User::role('admin')->get();

        $alertCount = 0;
        $tableData = [];

        foreach ($employees as $employee) {
            $days = $employee->getDaysUntilContractExpiry();

            if ($days === null) {
                continue;
            }

            $shouldAlert = $isForce || in_array($days, $thresholds, true) || $days <= 7;

            if ($shouldAlert) {
                $alertCount++;
                $statusLabel = $employee->getContractStatusLabel();
                $color = $employee->getContractStatusBadgeColor();

                $tableData[] = [
                    'NIP' => $employee->employee_code,
                    'Nama' => $employee->full_name,
                    'Posisi' => $employee->position?->name ?? 'PPNPN',
                    'Akhir Kontrak' => $employee->contract_end_date?->format('d/m/Y') ?? '-',
                    'Sisa Hari' => $days.' hari',
                    'Status' => $statusLabel,
                ];

                // Send database notifications to Admins
                foreach ($adminUsers as $admin) {
                    Notification::make()
                        ->title("Peringatan Kontrak: {$employee->full_name}")
                        ->body("Masa berlaku kontrak SPK ({$employee->spk_number}) akan berakhir dalam {$days} hari ({$employee->contract_end_date?->format('d M Y')}).")
                        ->color($color)
                        ->sendToDatabase($admin);
                }

                // Send notification to Supervisor if assigned
                if ($employee->supervisor) {
                    Notification::make()
                        ->title("Peringatan Kontrak Subordinat: {$employee->full_name}")
                        ->body("Masa berlaku kontrak SPK subordinat Anda akan berakhir dalam {$days} hari ({$employee->contract_end_date?->format('d M Y')}).")
                        ->color($color)
                        ->sendToDatabase($employee->supervisor);
                }

                // Send notification to Employee if user exists
                if ($employee->user) {
                    Notification::make()
                        ->title('Pemberitahuan Masa Kontrak SPK')
                        ->body("Masa berlaku kontrak kerja Anda berakhir pada {$employee->contract_end_date?->format('d M Y')} (Sisa {$days} hari). Silakan koordinasi dengan Bagian Umum/PPK.")
                        ->color($color)
                        ->sendToDatabase($employee->user);
                }

                // Record Audit Log entry
                AuditLog::create([
                    'user_id' => null,
                    'action' => 'CONTRACT_EXPIRY_ALERT',
                    'entity_type' => Employee::class,
                    'entity_id' => $employee->id,
                    'new_values' => [
                        'employee_code' => $employee->employee_code,
                        'full_name' => $employee->full_name,
                        'contract_end_date' => $employee->contract_end_date?->toDateString(),
                        'days_remaining' => $days,
                        'status' => $employee->getContractExpiryStatus(),
                    ],
                    'created_at' => Carbon::now('Asia/Jakarta'),
                ]);
            }
        }

        if (! empty($tableData)) {
            $this->table(['NIP', 'Nama', 'Posisi', 'Akhir Kontrak', 'Sisa Hari', 'Status'], $tableData);
            $this->warn("Terdapat {$alertCount} pegawai yang memicu notifikasi peringatan masa kontrak.");
        } else {
            $this->info('Seluruh pegawai berada dalam masa kontrak aman (tidak ada yang mencapai ambang jatuh tempo hari ini).');
        }

        return self::SUCCESS;
    }
}
