<?php

namespace App\Filament\Resources\EmployeeViolations\Schemas;

use App\Models\Employee;
use App\Models\User;
use App\Models\ViolationCatalog;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class EmployeeViolationForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $schema
            ->components([
                Section::make('Identitas Pegawai & Butir Pelanggaran')
                    ->description('Pencatatan pelanggaran disiplin dan kinerja pegawai berdasar Lampiran II SPK.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('employee_id')
                                ->label('Pegawai Subordinat')
                                ->options(function () use ($user): array {
                                    $query = Employee::where('is_active', true);
                                    if ($user && $user->hasRole('supervisor') && ! $user->hasRole('admin')) {
                                        $query->where('supervisor_id', $user->id);
                                    }

                                    return $query->pluck('full_name', 'id')->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->helperText('Hanya pegawai subordinat langsung yang dapat dipilih sesuai ketentuan Lampiran II SPK.'),

                            DatePicker::make('incident_date')
                                ->label('Tanggal Peristiwa')
                                ->required()
                                ->default(now())
                                ->maxDate(now())
                                ->native(false),

                            Select::make('violation_catalog_id')
                                ->label('Butir Pelanggaran (Katalog Lampiran II SPK)')
                                ->options(function (): array {
                                    return ViolationCatalog::where('is_active', true)
                                        ->orderBy('clause_number')
                                        ->get()
                                        ->mapWithKeys(fn (ViolationCatalog $c): array => [
                                            $c->id => "[{$c->clause_number}] {$c->description} ({$c->points} Poin)",
                                        ])
                                        ->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set): void {
                                    if ($state) {
                                        $catalog = ViolationCatalog::find($state);
                                        $set('points', $catalog?->points ?? 0);
                                    }
                                })
                                ->columnSpanFull(),

                            TextInput::make('points')
                                ->label('Bobot Poin Pelanggaran')
                                ->numeric()
                                ->required()
                                ->readOnly()
                                ->dehydrated(true)
                                ->helperText('Poin terisi otomatis sesuai butir katalog Lampiran II yang dipilih.')
                                ->suffix('Poin'),
                        ]),
                    ]),

                Section::make('Bukti & Fakta Lapangan')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Uraian Fakta Lapangan')
                            ->required()
                            ->rows(3)
                            ->placeholder('Jelaskan kronologi dan fakta kejadian di lapangan secara objektif...')
                            ->columnSpanFull(),

                        FileUpload::make('evidence_photo_path')
                            ->label('Unggah Bukti Lapangan (Foto / Dokumen)')
                            ->disk('public')
                            ->directory('violations/evidence')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                            ->maxSize(2048)
                            ->helperText('Format JPG, PNG, atau PDF (maksimal 2 MB).')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
