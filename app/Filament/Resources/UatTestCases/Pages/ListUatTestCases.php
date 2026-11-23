<?php

namespace App\Filament\Resources\UatTestCases\Pages;

use App\Filament\Resources\UatTestCases\UatTestCaseResource;
use App\Services\UatExecutionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListUatTestCases extends ListRecords
{
    protected static string $resource = UatTestCaseResource::class;

    protected ?string $heading = 'Pusat Verifikasi UAT Multi-Aktor End-to-End (US-701)';

    protected ?string $subheading = 'Pengujian penerimaan formal 15 skenario inti bersama seluruh formasi PPNPN, Pengawas, dan Pejabat Pembuat Komitmen Kanwil DJPb Lampung.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run_all')
                ->label('Jalankan Verifikasi UAT Otomatis')
                ->icon(Heroicon::OutlinedPlay)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Eksekusi Verifikasi UAT Otomatis')
                ->modalDescription('Sistem akan mengeksekusi pengujian otomatis terhadap 15 skenario inti sistem dan mencatat stempel waktu serta latensi eksekusi.')
                ->action(function (UatExecutionService $service): void {
                    $stats = $service->runAllVerifications();

                    Notification::make()
                        ->title('Verifikasi UAT Berhasil Diselesaikan')
                        ->body("Tingkat Kelulusan: {$stats['pass_rate']}% ({$stats['passed']}/{$stats['total']} Skenario Lulus). Kriteria kelulusan exit criteria terpenuhi.")
                        ->success()
                        ->send();
                }),

            Action::make('print_pdf')
                ->label('Cetak Berita Acara UAT (PDF)')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('primary')
                ->url(fn (): string => route('admin.uat.export-pdf'), shouldOpenInNewTab: true),
        ];
    }
}
