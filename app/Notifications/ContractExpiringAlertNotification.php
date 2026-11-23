<?php

namespace App\Notifications;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ContractExpiringAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Employee $employee,
        public int $daysRemaining
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $targetDate = $this->employee->contract_end_date?->format('d/m/Y') ?? '-';
        $severity = $this->daysRemaining <= 7 ? 'danger' : ($this->daysRemaining <= 30 ? 'warning' : 'info');

        return [
            'title' => 'Pemberitahuan Masa Berlaku Kontrak SPK',
            'body' => "Masa berlaku kontrak kerja SPK Anda akan berakhir dalam {$this->daysRemaining} hari (pada tanggal {$targetDate}). Silakan berkoordinasi dengan pengelola kepegawaian Subbagian Umum.",
            'url' => '/mobile/profile',
            'action_url' => '/mobile/profile',
            'category' => 'CONTRACT',
            'color' => $severity,
            'icon' => 'heroicon-o-document-text',
            'format' => 'filament',
            'duration' => 'persistent',
        ];
    }
}
