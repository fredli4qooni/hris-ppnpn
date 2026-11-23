<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Models\Employee;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('⚠️ Early Warning System (EWS) - Peringatan Ambang Sanksi Berat')
                    ->description('Peringatan dini pencegahan sanksi berat berdasarkan Lampiran III SPK-20/WPB.08/PPK/2025.')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('ews_status_badge')
                                ->label('Status EWS')
                                ->getStateUsing(fn (Employee $record): string => $record->getEwsStatus()['label'])
                                ->badge()
                                ->color(fn (Employee $record): string => $record->getEwsStatus()['color']),

                            TextEntry::make('active_points_display')
                                ->label('Total Saldo Poin Pelanggaran Aktif')
                                ->getStateUsing(fn (Employee $record): string => $record->calculateActiveViolationPoints().' Poin')
                                ->weight('bold')
                                ->color('danger'),

                            TextEntry::make('bap_action')
                                ->label('Tindakan Lanjut Pembinaan')
                                ->default('Buka Berita Acara Pembinaan ↗')
                                ->url(fn (Employee $record): string => route('admin.employees.discipline-summary', $record), shouldOpenInNewTab: true)
                                ->color('primary'),

                            TextEntry::make('ews_description_display')
                                ->label('Arahan Pembinaan & Risiko Disiplin')
                                ->columnSpanFull()
                                ->getStateUsing(fn (Employee $record): string => $record->getEwsStatus()['description']),
                        ]),
                    ])
                    ->visible(fn (Employee $record): bool => $record->getEwsStatus()['is_alert']),

                Section::make('Biodata & Identitas')
                    ->schema([
                        Grid::make(3)->schema([
                            ImageEntry::make('photo_path')
                                ->label('Foto Pegawai')
                                ->circular()
                                ->disk('public'),

                            TextEntry::make('employee_code')
                                ->label('NIP / Kode Pegawai')
                                ->weight('bold'),

                            TextEntry::make('full_name')
                                ->label('Nama Lengkap'),

                            TextEntry::make('gender')
                                ->label('Jenis Kelamin')
                                ->formatStateUsing(fn ($state) => $state === 'L' ? 'Laki-laki' : 'Perempuan'),

                            TextEntry::make('phone_number')
                                ->label('Nomor Telepon')
                                ->placeholder('-'),

                            IconEntry::make('is_active')
                                ->label('Status Aktif')
                                ->boolean(),
                        ]),
                    ]),

                Section::make('Penugasan & Jam Kerja')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('position.name')
                                ->label('Posisi Tugas')
                                ->badge()
                                ->color('primary'),

                            TextEntry::make('supervisor.name')
                                ->label('Supervisor Lapangan')
                                ->placeholder('-'),

                            TextEntry::make('atasan.name')
                                ->label('Pejabat Penyetuju (Atasan)')
                                ->placeholder('-'),
                        ]),
                    ]),

                Section::make('Kontrak & Finansial')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('spk_number')
                                ->label('Nomor SPK'),

                            TextEntry::make('contract_start_date')
                                ->label('Mulai Kontrak')
                                ->date('d F Y'),

                            TextEntry::make('contract_end_date')
                                ->label('Berakhir Kontrak')
                                ->date('d F Y'),

                            TextEntry::make('base_salary')
                                ->label('Honor Tetap Bulanan')
                                ->money('IDR'),

                            TextEntry::make('contract_value')
                                ->label('Nilai Total Kontrak SPK')
                                ->money('IDR', locale: 'id')
                                ->placeholder('-'),

                            TextEntry::make('contract_expiry_status')
                                ->label('Status Masa Berlaku Kontrak')
                                ->getStateUsing(fn ($record) => $record->getContractStatusLabel())
                                ->badge()
                                ->color(fn ($record) => $record->getContractStatusBadgeColor()),

                            TextEntry::make('leave_quota_balance')
                                ->label('Sisa Kuota Cuti')
                                ->suffix(' hari'),
                        ]),
                    ]),
            ]);
    }
}
