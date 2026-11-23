<?php

namespace App\Filament\Resources\AttendanceCorrections\Schemas;

use App\Models\AttendanceCorrection;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceCorrectionInfolist
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

                                TextEntry::make('attendance_date')
                                    ->label('Tanggal Lupa Absen')
                                    ->date('d F Y'),

                                TextEntry::make('correction_type')
                                    ->label('Jenis Koreksi')
                                    ->badge()
                                    ->color('info')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        AttendanceCorrection::TYPE_CHECK_IN => 'Lupa Masuk',
                                        AttendanceCorrection::TYPE_CHECK_OUT => 'Lupa Pulang',
                                        default => 'Lupa Masuk & Pulang',
                                    }),

                                TextEntry::make('status')
                                    ->label('Status Persetujuan')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        AttendanceCorrection::STATUS_APPROVED => 'success',
                                        AttendanceCorrection::STATUS_APPROVED_SUPERVISOR => 'info',
                                        AttendanceCorrection::STATUS_REJECTED => 'danger',
                                        default => 'warning',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        AttendanceCorrection::STATUS_APPROVED => 'Disetujui Sah',
                                        AttendanceCorrection::STATUS_APPROVED_SUPERVISOR => 'Telaah Supervisor',
                                        AttendanceCorrection::STATUS_REJECTED => 'Ditolak',
                                        default => 'Menunggu Supervisor',
                                    }),
                            ]),
                    ]),

                Section::make('Uraian Tugas & Jam Usulan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('suggested_check_in_at')
                                    ->label('Usulan Jam Masuk')
                                    ->time('H:i')
                                    ->placeholder('-'),

                                TextEntry::make('suggested_check_out_at')
                                    ->label('Usulan Jam Pulang')
                                    ->time('H:i')
                                    ->placeholder('-'),
                            ]),

                        TextEntry::make('work_summary')
                            ->label('Uraian Pekerjaan Dinas')
                            ->columnSpanFull(),

                        TextEntry::make('proof_document_path')
                            ->label('Berkas Lampiran')
                            ->formatStateUsing(fn (?string $state): string => $state ? 'Unduh Berkas Lampiran' : 'Tidak ada berkas')
                            ->url(fn (AttendanceCorrection $record): ?string => $record->proof_document_path ? asset('storage/'.$record->proof_document_path) : null, true)
                            ->color('primary')
                            ->columnSpanFull(),
                    ]),

                Section::make('Riwayat Persetujuan Dua Tingkat (Two-Tier)')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('supervisor.name')
                                    ->label('Supervisor Penelaah')
                                    ->placeholder('Menunggu telaah'),

                                TextEntry::make('supervisor_reviewed_at')
                                    ->label('Waktu Telaah Supervisor')
                                    ->dateTime('d M Y, H:i')
                                    ->placeholder('-'),

                                TextEntry::make('supervisor_notes')
                                    ->label('Catatan Supervisor')
                                    ->placeholder('-')
                                    ->columnSpanFull(),

                                TextEntry::make('atasan.name')
                                    ->label('Pejabat Penyetuju (Kasubbag / PPK)')
                                    ->placeholder('Menunggu putusan final'),

                                TextEntry::make('atasan_decided_at')
                                    ->label('Waktu Putusan Final')
                                    ->dateTime('d M Y, H:i')
                                    ->placeholder('-'),

                                TextEntry::make('atasan_notes')
                                    ->label('Catatan Atasan')
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
