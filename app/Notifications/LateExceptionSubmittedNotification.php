<?php

namespace App\Notifications;

use App\Models\LateException;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LateExceptionSubmittedNotification extends Notification
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
        $employeeName = $this->lateException->employee?->full_name ?? 'Pegawai PPNPN';
        $category = $this->lateException->getCategoryLabel();
        $date = $this->lateException->incident_date?->format('d/m/Y');

        return [
            'title' => 'Pengajuan Dispensasi Kahar (Force Majeure)',
            'body' => "{$employeeName} mengajukan permohonan dispensasi keterlambatan kahar ({$category}) tanggal {$date}.",
            'url' => "/admin/late-exceptions/{$this->lateException->id}",
            'action_url' => "/admin/late-exceptions/{$this->lateException->id}",
            'category' => 'LATE_EXCEPTION',
            'color' => 'warning',
            'icon' => 'heroicon-o-shield-exclamation',
            'format' => 'filament',
            'duration' => 'persistent',
        ];
    }
}
