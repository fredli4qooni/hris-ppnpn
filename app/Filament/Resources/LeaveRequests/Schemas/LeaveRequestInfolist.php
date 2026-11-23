<?php

namespace App\Filament\Resources\LeaveRequests\Schemas;

use App\Models\LeaveRequest;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeaveRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pegawai & Jenis Permohonan')
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

                            TextEntry::make('leave_type')
                                ->label('Jenis Izin / Cuti')
                                ->badge()
                                ->formatStateUsing(fn (LeaveRequest $record): string => $record->getTypeLabel()),

                            TextEntry::make('total_days')
                                ->label('Durasi Hari Kerja')
                                ->badge()
                                ->color('info')
                                ->suffix(' Hari'),

                            TextEntry::make('status')
                                ->label('Status Keseluruhan')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    LeaveRequest::STATUS_APPROVED => 'success',
                                    LeaveRequest::STATUS_REJECTED => 'danger',
                                    LeaveRequest::STATUS_PENDING_ATASAN => 'info',
                                    LeaveRequest::STATUS_CANCELLED => 'gray',
                                    default => 'warning',
                                })
                                ->formatStateUsing(fn (LeaveRequest $record): string => $record->getStatusLabel()),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('start_date')
                                ->label('Tanggal Mulai')
                                ->date('d F Y'),

                            TextEntry::make('end_date')
                                ->label('Tanggal Selesai')
                                ->date('d F Y'),
                        ]),

                        TextEntry::make('reason')
                            ->label('Alasan / Keperluan Lengkap')
                            ->columnSpanFull(),

                        ImageEntry::make('attachment_path')
                            ->label('Bukti Lampiran (Surat Dokter / Dokumen)')
                            ->disk('public')
                            ->columnSpanFull()
                            ->visible(fn (?string $state): bool => ! empty($state)),
                    ]),

                Section::make('Pemantauan SLA Standar Pelayanan (FR-508)')
                    ->description('Batas waktu maksimal pemrosesan permohonan adalah 2 hari kerja.')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('sla_status')
                                ->label('Status Kepatuhan SLA')
                                ->badge()
                                ->color(fn (LeaveRequest $record): string => $record->getSlaBadgeColor())
                                ->state(fn (LeaveRequest $record): string => $record->getSlaStatusLabel()),

                            TextEntry::make('pending_working_days')
                                ->label('Lama Antrean Saat Ini')
                                ->state(fn (LeaveRequest $record): string => $record->getPendingWorkingDays().' Hari Kerja')
                                ->helperText('Target maksimal 2 hari kerja (FR-508).'),

                            TextEntry::make('sla_alert')
                                ->label('Peringatan Tindak Lanjut')
                                ->badge()
                                ->color(fn (LeaveRequest $record): string => $record->isOverdueSla() ? 'danger' : 'success')
                                ->state(fn (LeaveRequest $record): string => $record->isOverdueSla() ? '⚠️ Melebihi Batas SLA' : '✅ Dalam Batas SLA'),
                        ]),
                    ]),

                Section::make('Tahap 1: Verifikasi Faktual Supervisor')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('supervisor.name')
                                ->label('Supervisor Lapangan')
                                ->default('Belum ditentukan'),

                            TextEntry::make('supervisor_status')
                                ->label('Status Verifikasi')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    LeaveRequest::SUPERVISOR_STATUS_VERIFIED => 'success',
                                    LeaveRequest::SUPERVISOR_STATUS_REJECTED => 'danger',
                                    default => 'warning',
                                })
                                ->formatStateUsing(fn (LeaveRequest $record): string => $record->getSupervisorStatusLabel()),

                            TextEntry::make('supervisor_action_at')
                                ->label('Waktu Verifikasi')
                                ->dateTime('d F Y, H:i')
                                ->default('-'),
                        ]),

                        TextEntry::make('supervisor_notes')
                            ->label('Catatan Lapangan Supervisor')
                            ->default('Tidak ada catatan.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Tahap 2: Putusan Akhir Pejabat Penyetuju (Atasan / PPK)')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('atasan.name')
                                ->label('Pejabat Penyetuju')
                                ->default('Kasubbag Umum / PPK'),

                            TextEntry::make('status')
                                ->label('Putusan Akhir')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    LeaveRequest::STATUS_APPROVED => 'success',
                                    LeaveRequest::STATUS_REJECTED => 'danger',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    LeaveRequest::STATUS_APPROVED => 'Disetujui Sah',
                                    LeaveRequest::STATUS_REJECTED => 'Ditolak Atasan',
                                    LeaveRequest::STATUS_PENDING_ATASAN => 'Menunggu Putusan Akhir',
                                    default => 'Belum Sampai Tahap Atasan',
                                }),

                            TextEntry::make('approved_at')
                                ->label('Waktu Keputusan')
                                ->dateTime('d F Y, H:i')
                                ->default('-'),
                        ]),

                        TextEntry::make('atasan_notes')
                            ->label('Catatan Pejabat Penyetuju')
                            ->default('Tidak ada catatan.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
