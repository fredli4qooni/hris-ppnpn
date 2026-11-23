<?php

namespace App\Filament\Resources\ViolationSanctions\Schemas;

use App\Models\ViolationSanction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViolationSanctionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Pegawai & Sanksi SPK')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('employee.full_name')
                                ->label('Nama Pegawai')
                                ->weight('bold'),

                            TextEntry::make('employee.employee_code')
                                ->label('NIP / Kode Pegawai'),

                            TextEntry::make('employee.position.name')
                                ->label('Posisi Tugas')
                                ->badge()
                                ->color('primary'),

                            TextEntry::make('sanction_type')
                                ->label('Tingkat Sanksi')
                                ->badge()
                                ->formatStateUsing(fn (ViolationSanction $record): string => $record->getTypeLabel())
                                ->color(fn (string $state): string => match ($state) {
                                    ViolationSanction::TYPE_SP_3 => 'danger',
                                    ViolationSanction::TYPE_SP_2_A, ViolationSanction::TYPE_SP_2_B, ViolationSanction::TYPE_SP_2_C => 'warning',
                                    default => 'primary',
                                }),

                            TextEntry::make('total_points_snapshot')
                                ->label('Akumulasi Poin Saat Terbit')
                                ->badge()
                                ->color('danger')
                                ->suffix(' Poin'),

                            TextEntry::make('issued_date')
                                ->label('Tanggal Penerbitan')
                                ->date('d F Y'),

                            IconEntry::make('is_active')
                                ->label('Status Aktif')
                                ->boolean(),
                        ]),
                    ]),

                Section::make('Jadwal Konsekuensi Pemotongan Honor (Akumulasi 2)')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('deduction_percentage')
                                ->label('Persentase Potongan')
                                ->suffix('%')
                                ->weight('bold'),

                            TextEntry::make('duration_months')
                                ->label('Durasi Sanksi')
                                ->suffix(' Bulan'),

                            TextEntry::make('start_date')
                                ->label('Bulan Mulai Potongan')
                                ->date('F Y')
                                ->placeholder('-'),

                            TextEntry::make('end_date')
                                ->label('Bulan Berakhir Potongan')
                                ->date('F Y')
                                ->placeholder('-'),

                            IconEntry::make('is_termination_recommended')
                                ->label('Rekomendasi Pemutusan Kontrak')
                                ->boolean(),

                            TextEntry::make('notes')
                                ->label('Keterangan & Catatan Pertimbangan')
                                ->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }
}
