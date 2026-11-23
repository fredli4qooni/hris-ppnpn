<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestApprovedTier1Notification extends Notification
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

        return [
            'title' => 'Persetujuan Cuti Tahap 1 (Supervisor)',
            'body' => "Supervisor telah menyetujui pengajuan {$leaveType} atas nama {$employeeName}. Menunggu putusan akhir Anda.",
            'url' => "/admin/leave-requests/{$this->leaveRequest->id}",
            'action_url' => "/admin/leave-requests/{$this->leaveRequest->id}",
            'category' => 'LEAVE',
            'color' => 'warning',
            'icon' => 'heroicon-o-check-badge',
            'format' => 'filament',
            'duration' => 'persistent',
        ];
    }
}
