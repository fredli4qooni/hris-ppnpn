<?php

namespace App\Notifications;

use App\Models\AttendanceCorrection;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceCorrectionSubmittedNotification extends Notification
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
        $employeeName = $this->correction->employee?->full_name ?? 'Pegawai PPNPN';
        $date = $this->correction->attendance_date?->format('d/m/Y');
        $type = $this->correction->getTypeLabel();

        return [
            'title' => 'Pengajuan Lupa Absen / Koreksi Presensi',
            'body' => "{$employeeName} mengajukan permohonan koreksi lupa absen ({$type}) untuk tanggal {$date}.",
            'url' => "/admin/attendance-corrections/{$this->correction->id}",
            'action_url' => "/admin/attendance-corrections/{$this->correction->id}",
            'category' => 'CORRECTION',
            'color' => 'primary',
            'icon' => 'heroicon-o-pencil-square',
            'format' => 'filament',
            'duration' => 'persistent',
        ];
    }
}
