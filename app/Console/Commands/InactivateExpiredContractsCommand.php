<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class InactivateExpiredContractsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hris:inactivate-expired-contracts {--date= : Tanggal evaluasi sistem dalam format YYYY-MM-DD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nonaktifkan akun pegawai yang masa kontrak SPK-nya telah berakhir tanpa adendum pembaruan (FR-604)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $evalDateStr = $this->option('date') ?: Carbon::now('Asia/Jakarta')->toDateString();
        $evalDate = Carbon::parse($evalDateStr, 'Asia/Jakarta')->startOfDay();

        $this->info("Menjalankan evaluasi penonaktifan kontrak kedaluwarsa untuk tanggal: {$evalDate->toDateString()} (WIB)...");

        /** @var Collection<int, Employee> $expiredEmployees */
        $expiredEmployees = Employee::with(['user', 'position', 'supervisor'])
            ->where('is_active', true)
            ->whereNotNull('contract_end_date')
            ->whereDate('contract_end_date', '<', $evalDate->toDateString())
            ->get();

        if ($expiredEmployees->isEmpty()) {
            $this->info('Nihil: Tidak ada pegawai aktif dengan kontrak kedaluwarsa pada tanggal ini.');

            return self::SUCCESS;
        }

        $inactivatedCount = 0;
        $tableData = [];

        /** @var Collection<int, User> $adminUsers */
        $adminUsers = User::role('admin')->get();

        foreach ($expiredEmployees as $employee) {
            $endDate = $employee->contract_end_date?->toDateString();
            $oldSpk = $employee->spk_number;

            // Inactivate employee record
            $employee->update([
                'is_active' => false,
            ]);

            // Inactivate user account so login is immediately blocked
            if ($employee->user) {
                $employee->user->update([
                    'is_active' => false,
                ]);
            }

            // Log immutable audit trail (FR-604, FR-902)
            AuditLog::create([
                'user_id' => null, // Automated system scheduler
                'action' => 'CONTRACT_AUTO_INACTIVATED',
                'entity_type' => Employee::class,
                'entity_id' => $employee->id,
                'old_values' => [
                    'is_active' => true,
                    'spk_number' => $oldSpk,
                    'contract_end_date' => $endDate,
                ],
                'new_values' => [
                    'is_active' => false,
                    'reason' => "Masa berlaku SPK ({$oldSpk}) telah berakhir pada {$endDate} tanpa adendum pembaruan aktif.",
                    'evaluated_at' => $evalDate->toIso8601String(),
                ],
                'ip_address' => '127.0.0.1 (System Console)',
                'user_agent' => 'Artisan Scheduler (hris:inactivate-expired-contracts)',
                'created_at' => Carbon::now('Asia/Jakarta'),
            ]);

            // Notify administrators via database notifications
            foreach ($adminUsers as $admin) {
                Notification::make()
                    ->title("Akun Dinonaktifkan: {$employee->full_name}")
                    ->body("Pegawai {$employee->full_name} ({$employee->employee_code}) telah dinonaktifkan otomatis karena masa kontrak SPK ({$oldSpk}) berakhir pada {$endDate}.")
                    ->color('danger')
                    ->icon('heroicon-o-user-minus')
                    ->sendToDatabase($admin);
            }

            $inactivatedCount++;

            $tableData[] = [
                'NIP' => $employee->employee_code,
                'Nama' => $employee->full_name,
                'Posisi' => $employee->position?->name ?? 'PPNPN',
                'SPK' => $oldSpk ?? '-',
                'Akhir Kontrak' => $endDate,
                'Status' => 'INACTIVE (Dinonaktifkan)',
            ];
        }

        $this->table(['NIP', 'Nama', 'Posisi', 'SPK', 'Akhir Kontrak', 'Status'], $tableData);
        $this->info("Berhasil menonaktifkan {$inactivatedCount} pegawai yang kontraknya telah berakhir.");

        return self::SUCCESS;
    }
}
