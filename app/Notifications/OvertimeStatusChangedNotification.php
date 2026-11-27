<?php

namespace App\Notifications;

use App\Models\OvertimeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OvertimeStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public OvertimeRequest $overtimeRequest,
        public string $eventType,
        public ?string $spklReminder = null
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $code = $this->overtimeRequest->getDisplayCode();
        $dateRange = $this->overtimeRequest->getDateRangeLabel();
        $typeLabel = $this->overtimeRequest->getTypeLabel();

        [$title, $body, $color, $icon] = match ($this->eventType) {
            'SUBMITTED' => [
                "Pengajuan Lembur Baru ({$code})",
                "Terdapat pengajuan {$typeLabel} untuk tanggal {$dateRange} yang menunggu persetujuan."
                    .($this->spklReminder ? " Catatan: {$this->spklReminder}" : ''),
                'warning',
                'heroicon-o-clock',
            ],
            'APPROVED' => [
                "Lembur Disetujui ({$code})",
                "Pengajuan {$typeLabel} tanggal {$dateRange} telah disetujui secara resmi.",
                'success',
                'heroicon-o-check-circle',
            ],
            'RETURNED' => [
                "Lembur Dikembalikan untuk Perbaikan ({$code})",
                "Pengajuan {$typeLabel} tanggal {$dateRange} dikembalikan: "
                    .($this->overtimeRequest->approver_notes ?? 'Perlu perbaikan.'),
                'warning',
                'heroicon-o-arrow-uturn-left',
            ],
            'REJECTED' => [
                "Lembur Ditolak ({$code})",
                "Pengajuan {$typeLabel} tanggal {$dateRange} ditolak: "
                    .($this->overtimeRequest->approver_notes ?? '-'),
                'danger',
                'heroicon-o-x-circle',
            ],
            'CANCELLED' => [
                "Surat Lembur Dibatalkan ({$code})",
                "Pengajuan {$typeLabel} tanggal {$dateRange} dibatalkan: "
                    .($this->overtimeRequest->cancellation_reason ?? '-'),
                'danger',
                'heroicon-o-no-symbol',
            ],
            default => [
                "Pembaruan Status Lembur ({$code})",
                "Status pengajuan {$typeLabel} tanggal {$dateRange}: {$this->overtimeRequest->getStatusLabel()}.",
                'info',
                'heroicon-o-information-circle',
            ],
        };

        $url = method_exists($notifiable, 'hasRole') && $notifiable->hasRole('karyawan')
            ? "/mobile/overtime/{$this->overtimeRequest->id}"
            : "/admin/overtime-requests/{$this->overtimeRequest->id}";

        return [
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'action_url' => $url,
            'category' => 'OVERTIME',
            'color' => $color,
            'icon' => $icon,
            'format' => 'filament',
            'duration' => 'persistent',
        ];
    }
}
