<?php

namespace App\Filament\Resources\LateExceptions\Schemas;

use App\Models\LateException;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LateExceptionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pegawai & Pengajuan')
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
                                    ->label('Tanggal Kejadian Kahar')
                                    ->date('d F Y'),

                                TextEntry::make('category')
                                    ->label('Kategori Kahar')
                                    ->badge()
                                    ->color('warning')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        LateException::CATEGORY_DISASTER => 'Bencana Alam',
                                        LateException::CATEGORY_ACCIDENT => 'Kecelakaan Lalu Lintas',
                                        LateException::CATEGORY_FAMILY_BEREAVEMENT => 'Musibah Keluarga',
                                        default => 'Kondisi Kahar Lainnya',
                                    }),

                                TextEntry::make('status')
                                    ->label('Status Persetujuan')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        LateException::STATUS_APPROVED => 'success',
                                        LateException::STATUS_APPROVED_SUPERVISOR => 'info',
                                        LateException::STATUS_REJECTED, LateException::STATUS_REJECTED_SUPERVISOR => 'danger',
                                        default => 'warning',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        LateException::STATUS_APPROVED => 'Disetujui Sah',
                                        LateException::STATUS_APPROVED_SUPERVISOR => 'Telaah Supervisor',
                                        LateException::STATUS_REJECTED, LateException::STATUS_REJECTED_SUPERVISOR => 'Ditolak',
                                        default => 'Menunggu Supervisor',
                                    }),
                            ]),
                    ]),

                Section::make('Uraian Kejadian & Bukti Dokumen Resmi')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('incident_time')
                                    ->label('Waktu Kejadian (WIB)')
                                    ->time('H:i')
                                    ->placeholder('-'),

                                TextEntry::make('proof_file_path')
                                    ->label('Dokumen Bukti Resmi')
                                    ->formatStateUsing(fn (?string $state): string => $state ? 'Unduh / Lihat Berkas Bukti' : 'Tidak Ada Lampiran')
                                    ->url(fn (LateException $record): ?string => $record->proof_file_path ? asset('storage/'.$record->proof_file_path) : null, true)
                                    ->color('primary'),
                            ]),

                        TextEntry::make('description')
                            ->label('Uraian Kronologis Kejadian Kahar')
                            ->columnSpanFull(),
                    ]),

                Section::make('Riwayat Persetujuan Dua Tingkat (Two-Tier)')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('supervisor.name')
                                    ->label('Supervisor Penelaah (Tahap 1)')
                                    ->placeholder('Belum ditelaah')
                                    ->description(fn (LateException $record): ?string => $record->supervisor_reviewed_at?->translatedFormat('d M Y, H:i')),

                                TextEntry::make('supervisor_notes')
                                    ->label('Catatan Hasil Verifikasi Lapangan')
                                    ->placeholder('-'),

                                TextEntry::make('atasan.name')
                                    ->label('Pejabat Pemutus / Kasubbag (Tahap 2)')
                                    ->placeholder('Belum diputuskan')
                                    ->description(fn (LateException $record): ?string => $record->atasan_decided_at?->translatedFormat('d M Y, H:i')),

                                TextEntry::make('atasan_notes')
                                    ->label('Dasar Pertimbangan Keputusan Pembebasan')
                                    ->placeholder('-'),
                            ]),
                    ]),
            ]);
    }
}
