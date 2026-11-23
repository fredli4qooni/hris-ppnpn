<?php

namespace App\Filament\Resources\EmployeeViolations\Schemas;

use App\Models\EmployeeViolation;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeViolationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pegawai & Status Pelanggaran')
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

                            TextEntry::make('incident_date')
                                ->label('Tanggal Peristiwa')
                                ->date('d F Y'),

                            TextEntry::make('status')
                                ->label('Status Catatan')
                                ->badge()
                                ->color(fn (string $state): string => $state === EmployeeViolation::STATUS_ACTIVE ? 'success' : 'gray')
                                ->formatStateUsing(fn (string $state): string => $state === EmployeeViolation::STATUS_ACTIVE ? 'Aktif' : 'Dibatalkan'),

                            TextEntry::make('points')
                                ->label('Bobot Poin')
                                ->badge()
                                ->color(fn (int $state): string => $state >= 50 ? 'danger' : ($state >= 20 ? 'warning' : 'primary'))
                                ->suffix(' Poin'),
                        ]),
                    ]),

                Section::make('Rincian Klausul Lampiran II SPK')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('catalog.clause_number')
                                ->label('Nomor Klausul')
                                ->badge()
                                ->color('primary'),

                            TextEntry::make('catalog.category')
                                ->label('Kategori')
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'KEHADIRAN_A' => 'Kategori A: Kehadiran',
                                    'KEWAJIBAN_B' => 'Kategori B: Kewajiban',
                                    'KEDISIPLINAN_C' => 'Kategori C: Kedisiplinan',
                                    default => $state,
                                }),

                            TextEntry::make('catalog.description')
                                ->label('Uraian Ketentuan SPK')
                                ->columnSpanFull(),

                            TextEntry::make('notes')
                                ->label('Fakta & Kronologi Lapangan')
                                ->columnSpanFull(),
                        ]),
                    ]),

                Section::make('Bukti Lampiran & Audit')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('supervisor.name')
                                ->label('Pencatat (Supervisor)'),

                            TextEntry::make('created_at')
                                ->label('Waktu Pencatatan')
                                ->dateTime('d F Y, H:i:s T'),

                            ImageEntry::make('evidence_photo_path')
                                ->label('Bukti Berkas / Foto')
                                ->disk('public')
                                ->columnSpanFull(),

                            TextEntry::make('cancellation_reason')
                                ->label('Alasan Pembatalan')
                                ->visible(fn (EmployeeViolation $record): bool => $record->isCancelled())
                                ->columnSpanFull(),

                            TextEntry::make('cancelledBy.name')
                                ->label('Dibatalkan Oleh')
                                ->visible(fn (EmployeeViolation $record): bool => $record->isCancelled()),
                        ]),
                    ]),
            ]);
    }
}
