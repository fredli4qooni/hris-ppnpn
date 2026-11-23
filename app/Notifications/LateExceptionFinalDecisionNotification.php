<?php

namespace App\Notifications;

use App\Models\LateException;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LateExceptionFinalDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LateException $lateException
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $isApproved = $this->lateException->isApproved();
        $statusLabel = $isApproved ? 'DISETUJUI' : 'DITOLAK';
        $date = $this->lateException->incident_date?->format('d/m/Y');

        return [
            'title' => "Putusan Dispensasi Kahar: {$statusLabel}",
            'body' => "Pengajuan dispensasi kahar Anda untuk tanggal {$date} telah {$statusLabel} oleh Pejabat Penyetuju (PPK).",
            'url' => "/mobile/late-exceptions/{$this->lateException->id}",
            'action_url' => "/mobile/late-exceptions/{$this->lateException->id}",
            'category' => 'LATE_EXCEPTION',
            'color' => $isApproved ? 'success' : 'danger',
            'icon' => $isApproved ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle',
            'format' => 'filament',
            'duration' => 'persistent',
        ];
    }
}
