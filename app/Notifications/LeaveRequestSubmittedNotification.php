<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LeaveRequest $leaveRequest
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $employeeName = $this->leaveRequest->employee?->full_name ?? 'Pegawai PPNPN';
        $leaveType = $this->leaveRequest->getTypeLabel();
        $start = $this->leaveRequest->start_date?->format('d/m/Y');
        $end = $this->leaveRequest->end_date?->format('d/m/Y');

        return [
            'title' => 'Pengajuan Izin/Cuti Baru',
            'body' => "{$employeeName} mengajukan {$leaveType} ({$this->leaveRequest->total_days} hari) untuk tanggal {$start} s.d. {$end}.",
            'url' => "/admin/leave-requests/{$this->leaveRequest->id}",
            'action_url' => "/admin/leave-requests/{$this->leaveRequest->id}",
            'category' => 'LEAVE',
            'color' => 'primary',
            'icon' => 'heroicon-o-calendar',
            'format' => 'filament',
            'duration' => 'persistent',
        ];
    }
}
