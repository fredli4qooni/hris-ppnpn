<?php

namespace App\Notifications;

use App\Models\EmployeeViolation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ViolationRecordedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public EmployeeViolation $violation
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $clause = $this->violation->catalog?->clause_number ?? 'SPK';
        $title = $this->violation->catalog?->name ?? 'Pelanggaran Disiplin';
        $points = $this->violation->points;
        $totalActivePoints = $this->violation->employee?->calculateActiveViolationPoints() ?? $points;

        return [
            'title' => 'Catatan Pelanggaran Disiplin (Akumulasi 2)',
            'body' => "Pelanggaran butir {$clause} ({$title}) berbobot +{$points} poin telah dicatat pada akun Anda. Saldo aktif berjalan: {$totalActivePoints} poin.",
            'url' => '/mobile/discipline',
            'action_url' => '/mobile/discipline',
            'category' => 'VIOLATION',
            'color' => 'danger',
            'icon' => 'heroicon-o-exclamation-triangle',
            'format' => 'filament',
            'duration' => 'persistent',
        ];
    }
}
