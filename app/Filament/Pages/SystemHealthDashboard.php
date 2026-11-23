<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\SystemHealthService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class SystemHealthDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static \UnitEnum|string|null $navigationGroup = 'Pengaturan & Master Data';

    protected static ?string $navigationLabel = 'Telemetri Kesehatan Server';

    protected static ?string $title = 'Dasbor Telemetri Kesehatan Server & Pemantauan Sistem';

    protected static ?int $navigationSort = 12;

    protected string $view = 'filament.pages.system-health-dashboard';

    /**
     * @var array<string, mixed>
     */
    public array $metrics = [];

    public string $searchTicketCode = '';

    public ?array $foundIncident = null;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return (bool) $user->hasRole('admin');
    }

    public function mount(SystemHealthService $service): void
    {
        $this->metrics = $service->getTelemetryMetrics();
    }

    public function refreshMetrics(SystemHealthService $service): void
    {
        $this->metrics = $service->getTelemetryMetrics();

        Notification::make()
            ->title('Metrik Telemetri Berhasil Disegarkan')
            ->body("Latensi DB: {$this->metrics['database']['latency_ms']} ms • Penggunaan Disk: {$this->metrics['storage']['used_percentage']}% • Status: {$this->metrics['overall_status']}.")
            ->success()
            ->send();
    }

    public function searchIncident(SystemHealthService $service): void
    {
        $this->foundIncident = null;

        if (empty(trim($this->searchTicketCode))) {
            return;
        }

        $log = $service->lookupIncident($this->searchTicketCode);

        if ($log) {
            $details = json_decode((string) $log->new_values, true) ?? [];
            $this->foundIncident = [
                'ticket_code' => $details['incident_code'] ?? $this->searchTicketCode,
                'exception' => $details['exception'] ?? 'Exception',
                'message' => $details['message'] ?? '-',
                'file' => ($details['file'] ?? '-').':'.($details['line'] ?? '-'),
                'url' => $details['url'] ?? '-',
                'ip_address' => $details['ip_address'] ?? '-',
                'recorded_at' => $log->created_at?->translatedFormat('d F Y, H:i:s').' WIB',
            ];

            Notification::make()
                ->title('Tiket Insiden Ditemukan')
                ->body("Kode {$this->searchTicketCode} berhasil dilacak di catatan audit log.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Tiket Insiden Tidak Ditemukan')
                ->body("Tidak ditemukan rekaman audit log untuk kode tiket: {$this->searchTicketCode}.")
                ->warning()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh_probe')
                ->label('Segarkan Metrik (Live Probe)')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('primary')
                ->action(fn (SystemHealthService $service) => $this->refreshMetrics($service)),
        ];
    }
}
