<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestFinalDecisionNotification extends Notification
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
        $isApproved = $this->leaveRequest->isApproved();
        $statusLabel = $isApproved ? 'DISETUJUI' : 'DITOLAK';
        $leaveType = $this->leaveRequest->getTypeLabel();
        $start = $this->leaveRequest->start_date?->format('d/m/Y');
        $end = $this->leaveRequest->end_date?->format('d/m/Y');

        return [
            'title' => "Putusan Izin/Cuti: {$statusLabel}",
            'body' => "Pengajuan {$leaveType} Anda untuk tanggal {$start} s.d. {$end} telah {$statusLabel} oleh Pejabat Penyetuju.",
            'url' => "/mobile/leaves/{$this->leaveRequest->id}",
            'action_url' => "/mobile/leaves/{$this->leaveRequest->id}",
            'category' => 'LEAVE',
            'color' => $isApproved ? 'success' : 'danger',
            'icon' => $isApproved ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle',
            'format' => 'filament',
            'duration' => 'persistent',
        ];
    }
}
