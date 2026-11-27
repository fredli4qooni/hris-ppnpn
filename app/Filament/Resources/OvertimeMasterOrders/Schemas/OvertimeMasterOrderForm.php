<?php

namespace App\Filament\Resources\OvertimeMasterOrders\Schemas;

use App\Models\MonthlyPeriodClosing;
use App\Models\OvertimeMasterOrder;
use App\Models\OvertimeSetting;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OvertimeMasterOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        $settings = OvertimeSetting::current();

        return $schema
            ->components([
                Section::make('Identitas Surat Perintah Kerja Lembur (SPKL)')
                    ->description('Surat perintah payung di awal bulan yang membebani DIPA untuk seluruh SPTPL bulan terkait.')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('period_month')
                                ->label('Bulan')
                                ->options(MonthlyPeriodClosing::MONTH_NAMES)
                                ->default(now('Asia/Jakarta')->month)
                                ->required()
                                ->disabledOn('edit'),

                            Select::make('period_year')
                                ->label('Tahun')
                                ->options([2025 => '2025', 2026 => '2026', 2027 => '2027'])
                                ->default(now('Asia/Jakarta')->year)
                                ->required()
                                ->disabledOn('edit'),

                            DatePicker::make('letter_date')
                                ->label('Tanggal Surat')
                                ->default(now('Asia/Jakarta')->startOfMonth()->toDateString())
                                ->required(),
                        ]),

                        Grid::make(3)->schema([
                            TextInput::make('letter_number')
                                ->label('Nomor SPKL')
                                ->placeholder('Otomatis: SPKL-{urut}/WPB.0801/{tahun}')
                                ->disabledOn('edit'),

                            TextInput::make('signer_name')
                                ->label('Penandatangan (Kabag Umum)')
                                ->default($settings->signer_name)
                                ->required(),

                            TextInput::make('signer_title')
                                ->label('Jabatan Penandatangan')
                                ->default($settings->signer_title)
                                ->required(),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('signer_nip')
                                ->label('NIP Penandatangan')
                                ->default($settings->signer_nip),

                            Select::make('status')
                                ->label('Status SPKL')
                                ->options([
                                    OvertimeMasterOrder::STATUS_DRAFT => 'Draf',
                                    OvertimeMasterOrder::STATUS_PENDING_APPROVAL => 'Menunggu Persetujuan',
                                    OvertimeMasterOrder::STATUS_ISSUED => 'Terbit',
                                    OvertimeMasterOrder::STATUS_CANCELLED => 'Dibatalkan',
                                ])
                                ->default(OvertimeMasterOrder::STATUS_DRAFT)
                                ->required(),
                        ]),

                        Textarea::make('template_body')
                            ->label('Isi Teks Surat SPKL (Dapat Diedit)')
                            ->default(OvertimeSetting::defaultSpklTemplate())
                            ->rows(6)
                            ->required()
                            ->columnSpanFull(),

                        FileUpload::make('signed_pdf_path')
                            ->label('Unggah PDF SPKL Bertanda Tangan (TTE)')
                            ->disk('public')
                            ->directory('overtime/spkl')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(5120)
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
