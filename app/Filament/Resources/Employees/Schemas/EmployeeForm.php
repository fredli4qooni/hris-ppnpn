<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Biodata Pegawai')
                    ->description('Informasi identitas dasar personil PPNPN.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('employee_code')
                                ->label('NIP / Kode Pegawai')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(50)
                                ->placeholder('Contoh: PPNPN-2025-001'),

                            TextInput::make('full_name')
                                ->label('Nama Lengkap')
                                ->required()
                                ->maxLength(150)
                                ->placeholder('Masukkan nama lengkap pegawai'),

                            Select::make('gender')
                                ->label('Jenis Kelamin')
                                ->options([
                                    'L' => 'Laki-laki',
                                    'P' => 'Perempuan',
                                ])
                                ->default('L')
                                ->required(),

                            TextInput::make('phone_number')
                                ->label('Nomor WhatsApp / HP')
                                ->tel()
                                ->maxLength(25)
                                ->placeholder('0812xxxxxxxx'),
                        ]),

                        FileUpload::make('photo_path')
                            ->label('Pas Foto Resmi')
                            ->image()
                            ->directory('employee-photos')
                            ->imageEditor()
                            ->columnSpanFull(),
                    ]),

                Section::make('Penugasan & Hirarki Kerja')
                    ->description('Penetapan posisi jabatan personil dan pejabat berwenang penyetuju perizinan.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('position_id')
                                ->label('Posisi Tugas / Jabatan')
                                ->relationship('position', 'name')
                                ->required()
                                ->preload()
                                ->searchable()
                                ->columnSpanFull(),

                            Select::make('supervisor_id')
                                ->label('Supervisor Lapangan')
                                ->options(fn () => User::role('supervisor')->pluck('name', 'id'))
                                ->searchable()
                                ->nullable()
                                ->helperText('Atasan langsung operasional di lapangan (Persetujuan Tahap 1)'),

                            Select::make('atasan_id')
                                ->label('Pejabat Penyetuju (Atasan)')
                                ->options(fn () => User::role('atasan')->pluck('name', 'id'))
                                ->searchable()
                                ->nullable()
                                ->helperText('Kepala Bagian Umum / Kasubbag TU (Persetujuan Tahap 2)'),
                        ]),
                    ]),

                Section::make('Kontrak Kerja & Hak Pegawai')
                    ->description('Data Surat Perjanjian Kerja (SPK), honor tetap, dan saldo cuti.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('spk_number')
                                ->label('Nomor SPK')
                                ->default('SPK-20/WPB.08/PPK/2025')
                                ->maxLength(100),

                            TextInput::make('base_salary')
                                ->label('Honorarium Tetap Bulanan')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(3200000)
                                ->required(),

                            TextInput::make('contract_value')
                                ->label('Nilai Kontrak Total SPK')
                                ->numeric()
                                ->prefix('Rp')
                                ->placeholder('Contoh: 38400000')
                                ->helperText('Total nilai kontrak kerja selama masa berlaku SPK'),

                            DatePicker::make('contract_start_date')
                                ->label('Mulai Kontrak')
                                ->displayFormat('d/m/Y'),

                            DatePicker::make('contract_end_date')
                                ->label('Berakhir Kontrak')
                                ->displayFormat('d/m/Y'),

                            TextInput::make('leave_quota_balance')
                                ->label('Sisa Kuota Cuti Tahunan')
                                ->numeric()
                                ->default(12)
                                ->suffix('hari')
                                ->required(),

                            Toggle::make('is_active')
                                ->label('Status Pegawai Aktif')
                                ->default(true)
                                ->inline(false),
                        ]),
                    ]),
            ]);
    }
}
