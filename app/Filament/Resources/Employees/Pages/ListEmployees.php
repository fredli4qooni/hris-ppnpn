<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\User;
use App\Services\EmployeeImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Pegawai'),

            Action::make('downloadTemplate')
                ->label('Unduh Format CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (EmployeeImportService $importService): StreamedResponse {
                    $csvContent = $importService->generateCsvTemplate();

                    return response()->streamDownload(
                        function () use ($csvContent) {
                            echo $csvContent;
                        },
                        'template_import_pegawai_ppnpn.csv',
                        [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => 'attachment; filename="template_import_pegawai_ppnpn.csv"',
                        ]
                    );
                }),

            Action::make('importEmployees')
                ->label('Impor CSV / Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Impor Data Master Pegawai PPNPN')
                ->modalDescription('Unggah berkas CSV atau Excel (.xlsx) untuk menambahkan data master pegawai secara massal.')
                ->modalSubmitActionLabel('Mulai Impor')
                ->modalWidth('lg')
                ->form([
                    FileUpload::make('import_file')
                        ->label('Berkas CSV / Excel (.xlsx)')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->disk('local')
                        ->directory('temp-imports')
                        ->required()
                        ->storeFiles(),

                    Toggle::make('strict_mode')
                        ->label('Mode Ketat (Rollback jika ada NIP Duplikat)')
                        ->helperText('Jika aktif, seluruh berkas akan dibatalkan (rollback) apabila terdapat NIP/Username yang duplikat.')
                        ->default(true),
                ])
                ->action(function (array $data, EmployeeImportService $importService): void {
                    /** @var string $relativePath */
                    $relativePath = $data['import_file'];
                    $absolutePath = Storage::disk('local')->path($relativePath);
                    $strict = (bool) ($data['strict_mode'] ?? true);

                    try {
                        /** @var User|null $user */
                        $user = Auth::user();
                        $result = $importService->importFile($absolutePath, $user?->id, $strict);

                        if ($result['imported'] > 0) {
                            $message = "Berhasil mengimpor {$result['imported']} pegawai dari total {$result['total']} baris.";
                            if ($result['skipped'] > 0) {
                                $message .= " ({$result['skipped']} data dilewati).";
                            }

                            Notification::make()
                                ->title('Impor Pegawai Berhasil')
                                ->body($message)
                                ->success()
                                ->send();
                        } else {
                            $errorMessage = "Gagal mengimpor berkas. {$result['skipped']} baris bermasalah (Rollback).";
                            if (! empty($result['errors'])) {
                                $errorMessage .= "\nContoh: ".implode(', ', array_slice($result['errors'], 0, 3));
                            }

                            Notification::make()
                                ->title('Impor Dibatalkan (Rollback)')
                                ->body($errorMessage)
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    } finally {
                        if (Storage::disk('local')->exists($relativePath)) {
                            Storage::disk('local')->delete($relativePath);
                        }
                    }
                }),
        ];
    }
}
