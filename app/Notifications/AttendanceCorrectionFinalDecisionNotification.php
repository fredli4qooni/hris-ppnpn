<?php

namespace App\Notifications;

use App\Models\AttendanceCorrection;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceCorrectionFinalDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public AttendanceCorrection $correction
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $isApproved = $this->correction->isApproved();
        $statusLabel = $isApproved ? 'DISETUJUI' : 'DITOLAK';
        $date = $this->correction->attendance_date?->format('d/m/Y');

        return [
            'title' => "Putusan Lupa Absen: {$statusLabel}",
            'body' => "Pengajuan koreksi lupa absen Anda untuk tanggal {$date} telah {$statusLabel} oleh pihak berwenang.",
            'url' => "/mobile/corrections/{$this->correction->id}",
            'action_url' => "/mobile/corrections/{$this->correction->id}",
            'category' => 'CORRECTION',
            'color' => $isApproved ? 'success' : 'danger',
            'icon' => $isApproved ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle',
            'format' => 'filament',
            'duration' => 'persistent',
        ];
    }
}
