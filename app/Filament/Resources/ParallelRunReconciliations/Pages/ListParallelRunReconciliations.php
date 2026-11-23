<?php

namespace App\Filament\Resources\ParallelRunReconciliations\Pages;

use App\Filament\Resources\ParallelRunReconciliations\ParallelRunReconciliationResource;
use App\Services\ParallelRunReconciliationService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListParallelRunReconciliations extends ListRecords
{
    protected static string $resource = ParallelRunReconciliationResource::class;

    protected ?string $heading = 'Mesin Rekonsiliasi Pilot Run Paralel (US-801)';

    protected ?string $subheading = 'Komparasi dan validasi presensi manual vs catatan digital HRIS selama masa uji coba 10 hari kerja menuju pengesahan cut-off operasional penuh.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('simulate_pilot')
                ->label('Simulasi Dataset Pilot Run (10 Hari)')
                ->icon(Heroicon::OutlinedPlay)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Generate Dataset Simulasi Pilot Run')
                ->modalDescription('Sistem akan mengevaluasi dan membuat log komparasi presensi manual vs digital selama 10 hari kerja untuk seluruh pegawai aktif.')
                ->action(function (ParallelRunReconciliationService $service): void {
                    $startDate = Carbon::now('Asia/Jakarta')->subDays(14)->startOfDay();
                    $records = $service->seedPilotDataset($startDate, 10);
                    $stats = $service->getConcordanceStatistics();

                    Notification::make()
                        ->title('Dataset Pilot Run Berhasil Dihasilkan')
                        ->body("Total {$records->count()} log terkomparasi. Tingkat Keselarasan Saat Ini: {$stats['concordance_rate']}%.")
                        ->success()
                        ->send();
                }),

            Action::make('print_pdf')
                ->label('Cetak Berita Acara Rekonsiliasi (PDF)')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('primary')
                ->url(fn (): string => route('admin.reconciliation.export-pdf'), shouldOpenInNewTab: true),
        ];
    }
}
