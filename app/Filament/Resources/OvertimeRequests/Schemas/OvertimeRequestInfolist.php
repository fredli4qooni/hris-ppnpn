<?php

namespace App\Filament\Resources\OvertimeRequests\Schemas;

use App\Models\OvertimeRequest;
use App\Models\OvertimeRequestItem;
use App\Services\OvertimeService;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class OvertimeRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ringkasan Pengajuan Lembur')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('display_code')
                                ->label('Nomor Surat / Kode Draf')
                                ->state(fn (OvertimeRequest $record): string => $record->getDisplayCode())
                                ->weight('bold'),

                            TextEntry::make('request_type')
                                ->label('Jenis Pengajuan')
                                ->badge()
                                ->state(fn (OvertimeRequest $record): string => $record->getTypeLabel()),

                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    OvertimeRequest::STATUS_APPROVED => 'success',
                                    OvertimeRequest::STATUS_SUBMITTED => 'warning',
                                    OvertimeRequest::STATUS_RETURNED => 'info',
                                    OvertimeRequest::STATUS_REJECTED, OvertimeRequest::STATUS_CANCELLED => 'danger',
                                    default => 'gray',
                                })
                                ->state(fn (OvertimeRequest $record): string => $record->getStatusLabel()),
                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('date_range')
                                ->label('Tanggal Lembur')
                                ->state(fn (OvertimeRequest $record): string => $record->getDateRangeLabel()),

                            TextEntry::make('letter_date')
                                ->label('Tanggal Surat')
                                ->date('d M Y'),

                            TextEntry::make('spkl_reminder')
                                ->label('Status SPKL Bulanan')
                                ->state(fn (OvertimeRequest $record): string => app(OvertimeService::class)->checkSpklReminder($record) ?? 'SPKL Bulan Terkait Sudah Terbit')
                                ->badge()
                                ->color(fn (OvertimeRequest $record): string => app(OvertimeService::class)->checkSpklReminder($record) ? 'warning' : 'success'),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('proposer.name')
                                ->label('Pengusul'),

                            TextEntry::make('signer_name')
                                ->label('Penandatangan'),
                        ]),

                        TextEntry::make('approver_notes')
                            ->label('Catatan Penyetuju')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('cancellation_reason')
                            ->label('Alasan Pembatalan')
                            ->visible(fn (OvertimeRequest $record): bool => $record->status === OvertimeRequest::STATUS_CANCELLED)
                            ->columnSpanFull(),

                        TextEntry::make('attachment_url')
                            ->label('Lampiran PDF')
                            ->state(fn (OvertimeRequest $record): string => $record->attachment_pdf_path ? 'Buka PDF Lampiran' : '-')
                            ->url(fn (OvertimeRequest $record): ?string => $record->attachment_pdf_path ? Storage::disk('public')->url($record->attachment_pdf_path) : null, shouldOpenInNewTab: true),
                    ]),

                Section::make('Daftar Pegawai & Waktu Lembur')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Grid::make(4)->schema([
                                    TextEntry::make('employee.full_name')
                                        ->label('Nama Pegawai')
                                        ->weight('bold'),

                                    TextEntry::make('time_window')
                                        ->label('Waktu')
                                        ->state(fn (OvertimeRequestItem $item): string => "{$item->start_time} - {$item->end_time} WIB"),

                                    TextEntry::make('duration')
                                        ->label('Total Durasi')
                                        ->state(fn (OvertimeRequestItem $item): string => $item->getFormattedDuration().' ('.(float) $item->duration_hours.' jam)')
                                        ->badge()
                                        ->color('info'),

                                    TextEntry::make('description')
                                        ->label('Uraian Kegiatan'),
                                ]),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
