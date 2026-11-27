<?php

namespace App\Filament\Resources\OvertimeRequests\Schemas;

use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSetting;
use App\Services\OvertimePdfImportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class OvertimeRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        $settings = OvertimeSetting::current();

        return $schema
            ->components([
                Section::make('Informasi Surat & Tanggal Lembur')
                    ->description('Tanggal lembur mendukung 1 hari atau rentang beberapa hari. Pengajuan hanya diperbolehkan untuk hari ini atau tanggal mendatang.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('request_type')
                                ->label('Jenis Pengajuan')
                                ->options([
                                    OvertimeRequest::TYPE_WITH_LETTER => 'Dengan Surat Tugas (SPTPL)',
                                    OvertimeRequest::TYPE_WITHOUT_LETTER => 'Tanpa Surat (Maks. Hari H Sebelum Dilaksanakan)',
                                ])
                                ->default(OvertimeRequest::TYPE_WITH_LETTER)
                                ->required()
                                ->disabledOn('edit'),

                            TextInput::make('signer_name')
                                ->label('Penandatangan (Kabag Umum)')
                                ->default($settings->signer_name)
                                ->required(),
                        ]),

                        Grid::make(3)->schema([
                            DatePicker::make('start_date')
                                ->label('Tanggal Mulai Lembur')
                                ->default(now('Asia/Jakarta')->toDateString())
                                ->minDate(now('Asia/Jakarta')->toDateString())
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                    $end = $get('end_date');
                                    if ($state && (! $end || $end < $state)) {
                                        $set('end_date', $state);
                                    }
                                }),

                            DatePicker::make('end_date')
                                ->label('Tanggal Selesai Lembur')
                                ->default(now('Asia/Jakarta')->toDateString())
                                ->minDate(fn (Get $get): string => $get('start_date') ?: now('Asia/Jakarta')->toDateString())
                                ->required(),

                            DatePicker::make('letter_date')
                                ->label('Tanggal Surat')
                                ->default(now('Asia/Jakarta')->toDateString())
                                ->required(),
                        ]),

                        FileUpload::make('attachment_pdf_path')
                            ->label('Impor / Lampirkan PDF Surat Tugas (Opsional: Tanggal, Waktu & Keterangan Otomatis Terbaca)')
                            ->disk('public')
                            ->directory('overtime/attachments')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(5120)
                            ->live()
                            ->afterStateUpdated(function (mixed $state, Get $get, Set $set): void {
                                if (! is_string($state) || ! Storage::disk('public')->exists($state)) {
                                    return;
                                }

                                $binary = Storage::disk('public')->get($state);
                                $parsed = app(OvertimePdfImportService::class)->parsePdfBinary($binary);
                                $set('imported_pdf_summary', $parsed);

                                if ($parsed['start_date']) {
                                    $set('start_date', $parsed['start_date']);
                                    $set('end_date', $parsed['end_date'] ?? $parsed['start_date']);
                                }

                                $items = $get('items') ?? [];
                                if (is_array($items) && count($items) > 0) {
                                    foreach ($items as $key => $row) {
                                        if ($parsed['start_time']) {
                                            $items[$key]['start_time'] = $parsed['start_time'];
                                        }
                                        if ($parsed['end_time']) {
                                            $items[$key]['end_time'] = $parsed['end_time'];
                                        }
                                        if ($parsed['description'] && empty($items[$key]['description'])) {
                                            $items[$key]['description'] = $parsed['description'];
                                        }
                                    }
                                    $set('items', $items);
                                }

                                Notification::make()
                                    ->title($parsed['has_extractable_text'] ? 'PDF Berhasil Diimpor' : 'PDF Diunggah (Scan/Gambar)')
                                    ->body($parsed['has_extractable_text']
                                        ? 'Tanggal, waktu, dan keterangan dari PDF telah diterapkan ke formulir bila ditemukan.'
                                        : 'PDF tidak memiliki lapisan teks. Silakan lengkapi tanggal, waktu, dan uraian secara manual.')
                                    ->info()
                                    ->send();
                            })
                            ->columnSpanFull(),

                        Hidden::make('imported_pdf_summary'),
                        Hidden::make('signer_title')->default($settings->signer_title),
                        Hidden::make('signer_nip')->default($settings->signer_nip),
                        Hidden::make('replaces_overtime_request_id'),
                    ]),

                Section::make('Daftar Pegawai & Waktu Lembur')
                    ->description('Tambahkan satu atau beberapa pegawai PPNPN. Satu pegawai tidak boleh tercantum di pengajuan lain pada tanggal yang sama.')
                    ->schema([
                        Repeater::make('items')
                            ->label('Daftar Pegawai Lembur')
                            ->defaultItems(1)
                            ->minItems(1)
                            ->schema([
                                Grid::make(4)->schema([
                                    Select::make('employee_id')
                                        ->label('Nama Pegawai')
                                        ->options(fn (): array => Employee::query()
                                            ->where('is_active', true)
                                            ->orderBy('full_name')
                                            ->get()
                                            ->mapWithKeys(fn (Employee $emp): array => [
                                                $emp->id => "{$emp->full_name} ({$emp->employee_code})",
                                            ])
                                            ->all())
                                        ->searchable()
                                        ->required()
                                        ->columnSpan(2),

                                    TextInput::make('start_time')
                                        ->label('Jam Mulai')
                                        ->type('time')
                                        ->default('17:30')
                                        ->required(),

                                    TextInput::make('end_time')
                                        ->label('Jam Selesai')
                                        ->type('time')
                                        ->default('19:30')
                                        ->required(),
                                ]),

                                Textarea::make('description')
                                    ->label('Uraian Kegiatan Lembur')
                                    ->default('Menyelesaikan pekerjaan TURT')
                                    ->required()
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->addActionLabel('+ Tambah Pegawai')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
