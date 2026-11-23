<?php

namespace App\Filament\Resources\LeaveRequests\Schemas;

use App\Models\LeaveRequest;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeaveRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Pengajuan Izin / Cuti')
                    ->description('Detail permohonan izin/cuti pegawai dengan alur Two-Tier Approval.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('employee_id')
                                ->label('Pegawai')
                                ->relationship('employee', 'full_name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('leave_type')
                                ->label('Jenis Izin / Cuti')
                                ->options([
                                    LeaveRequest::TYPE_ANNUAL_LEAVE => 'Cuti Tahunan',
                                    LeaveRequest::TYPE_SICK_LEAVE => 'Izin Sakit',
                                    LeaveRequest::TYPE_SPECIAL_LEAVE => 'Izin Khusus / Di Luar Cuti',
                                    LeaveRequest::TYPE_DUTY_LEAVE => 'Izin Tugas Kedinasan',
                                    LeaveRequest::TYPE_EARLY_DEPARTURE => 'Izin Pulang Lebih Awal',
                                ])
                                ->required(),

                            DatePicker::make('start_date')
                                ->label('Mulai Tanggal')
                                ->required(),

                            DatePicker::make('end_date')
                                ->label('Sampai Tanggal')
                                ->required(),

                            TextInput::make('total_days')
                                ->label('Total Hari Kerja')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->maxValue(3)
                                ->helperText('Maksimal 3 hari kerja berturut-turut per pengajuan (Lampiran II.A SPK).'),

                            FileUpload::make('attachment_path')
                                ->label('Berkas Lampiran (Surat Dokter / Bukti)')
                                ->disk('public')
                                ->directory('leave-attachments')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->maxSize(2048),
                        ]),

                        Textarea::make('reason')
                            ->label('Alasan / Keperluan')
                            ->required()
                            ->rows(3),
                    ]),
            ]);
    }
}
