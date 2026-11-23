<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\ViolationSanction;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HrisStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $today = now('Asia/Jakarta')->toDateString();

        $activeEmployees = Employee::where('is_active', true)->count();
        $attendancesToday = Attendance::whereDate('date', $today)->count();
        $pendingLeaves = LeaveRequest::whereIn('status', [
            LeaveRequest::STATUS_PENDING_SUPERVISOR,
            LeaveRequest::STATUS_PENDING_ATASAN,
        ])->count();
        $activeSanctions = ViolationSanction::where('is_active', true)->count();

        return [
            Stat::make('Pegawai PPNPN Aktif', "{$activeEmployees} Personil")
                ->description('Total personil PPNPN terdaftar aktif')
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('primary')
                ->url(route('filament.admin.resources.employees.index')),

            Stat::make('Presensi Hari Ini', "{$attendancesToday} Kehadiran")
                ->description('Log kehadiran terverifikasi tanggal ini')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success'),

            Stat::make('Pengajuan Cuti / Izin Pending', "{$pendingLeaves} Permohonan")
                ->description('Permohonan menunggu verifikasi atasan')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color($pendingLeaves > 0 ? 'warning' : 'gray')
                ->url(route('filament.admin.resources.leave-requests.index')),

            Stat::make('Sanksi & SPK Aktif', "{$activeSanctions} Kasus")
                ->description('Penegakan disiplin dalam masa berlaku')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($activeSanctions > 0 ? 'danger' : 'success')
                ->url(route('filament.admin.resources.violation-sanctions.index')),
        ];
    }
}
