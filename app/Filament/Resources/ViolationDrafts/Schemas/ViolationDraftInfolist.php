<?php

namespace App\Filament\Resources\ViolationDrafts\Schemas;

use App\Models\ViolationDraft;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViolationDraftInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pegawai & Pemicu Sistem')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('employee.full_name')
                                    ->label('Nama Pegawai'),

                                TextEntry::make('employee.employee_code')
                                    ->label('NIP / ID PPNPN'),

                                TextEntry::make('employee.position.name')
                                    ->label('Posisi Tugas')
                                    ->badge()
                                    ->color('primary'),

                                TextEntry::make('incident_date')
                                    ->label('Tanggal Kejadian Terdeteksi')
                                    ->date('d F Y'),

                                TextEntry::make('trigger_type')
                                    ->label('Jenis Pemicu Otomatis')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        ViolationDraft::TRIGGER_CONSECUTIVE_ALPHA => 'danger',
                                        ViolationDraft::TRIGGER_REPEATED_PSW => 'warning',
                                        default => 'info',
                                    })
                                    ->formatStateUsing(fn (ViolationDraft $record): string => $record->getTriggerLabel()),

                                TextEntry::make('status')
                                    ->label('Status Draf')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        ViolationDraft::STATUS_CONFIRMED => 'danger',
                                        ViolationDraft::STATUS_DISMISSED => 'gray',
                                        default => 'warning',
                                    })
                                    ->formatStateUsing(fn (ViolationDraft $record): string => $record->getStatusLabel()),
                            ]),
                    ]),

                Section::make('Uraian Pelanggaran & Butir SPK')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('catalog.clause_number')
                                    ->label('Nomor Klausul SPK (Lampiran II)')
                                    ->badge()
                                    ->color('primary')
                                    ->placeholder('-'),

                                TextEntry::make('catalog.points')
                                    ->label('Beban Poin Sanksi')
                                    ->numeric()
                                    ->suffix(' Poin Disiplin')
                                    ->weight('bold')
                                    ->color(fn ($state): string => (int) $state >= 20 ? 'danger' : 'warning')
                                    ->placeholder('-'),
                            ]),

                        TextEntry::make('auto_generated_notes')
                            ->label('Catatan Deteksi Otomatis Sistem')
                            ->columnSpanFull(),
                    ]),

                Section::make('Riwayat Tindak Lanjut Supervisor')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('confirmedBy.name')
                                    ->label('Supervisor Pengesah')
                                    ->placeholder('Belum disahkan')
                                    ->description(fn (ViolationDraft $record): ?string => $record->confirmed_at?->translatedFormat('d M Y, H:i')),

                                TextEntry::make('employeeViolation.notes')
                                    ->label('Catatan Pelanggaran Resmi')
                                    ->placeholder('-'),

                                TextEntry::make('dismissedBy.name')
                                    ->label('Supervisor Pembatal')
                                    ->placeholder('Tidak dibatalkan')
                                    ->description(fn (ViolationDraft $record): ?string => $record->dismissed_at?->translatedFormat('d M Y, H:i')),

                                TextEntry::make('dismissal_reason')
                                    ->label('Alasan Pembatalan Draf')
                                    ->placeholder('-'),
                            ]),
                    ]),
            ]);
    }
}
