<?php

namespace App\Filament\Resources\ViolationSanctions\Schemas;

use App\Models\Employee;
use App\Models\ViolationSanction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViolationSanctionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Sanksi Disiplin SPK')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('employee_id')
                                ->label('Pegawai')
                                ->options(Employee::where('is_active', true)->pluck('full_name', 'id')->toArray())
                                ->searchable()
                                ->required(),

                            Select::make('sanction_type')
                                ->label('Tingkat Sanksi')
                                ->options([
                                    ViolationSanction::TYPE_TEGURAN_LISAN => 'Teguran Lisan (15-20 Poin)',
                                    ViolationSanction::TYPE_SP_1 => 'Surat Peringatan 1 (21-40 Poin)',
                                    ViolationSanction::TYPE_SP_2_A => 'SP 2A (41-60 Poin)',
                                    ViolationSanction::TYPE_SP_2_B => 'SP 2B (61-80 Poin)',
                                    ViolationSanction::TYPE_SP_2_C => 'SP 2C (81-99 Poin)',
                                    ViolationSanction::TYPE_SP_3 => 'SP 3 (≥ 100 Poin - Rekomendasi Pemutusan)',
                                ])
                                ->required(),

                            TextInput::make('total_points_snapshot')
                                ->label('Poin Snapshot')
                                ->numeric()
                                ->required(),

                            DatePicker::make('issued_date')
                                ->label('Tanggal Terbit')
                                ->required()
                                ->default(now()),

                            TextInput::make('deduction_percentage')
                                ->label('Persentase Potongan (%)')
                                ->numeric()
                                ->suffix('%'),

                            TextInput::make('duration_months')
                                ->label('Durasi Bulan')
                                ->numeric()
                                ->suffix('Bulan'),

                            DatePicker::make('start_date')
                                ->label('Mulai Berlaku'),

                            DatePicker::make('end_date')
                                ->label('Selesai Berlaku'),

                            Toggle::make('is_active')
                                ->label('Status Aktif')
                                ->default(true),

                            Toggle::make('is_termination_recommended')
                                ->label('Rekomendasi Pemutusan Kontrak'),

                            Textarea::make('notes')
                                ->label('Catatan')
                                ->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }
}
