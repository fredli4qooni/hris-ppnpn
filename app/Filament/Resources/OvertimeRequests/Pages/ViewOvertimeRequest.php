<?php

namespace App\Filament\Resources\OvertimeRequests\Pages;

use App\Filament\Resources\OvertimeRequests\OvertimeRequestResource;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Services\OvertimeService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ViewOvertimeRequest extends ViewRecord
{
    protected static string $resource = OvertimeRequestResource::class;

    protected function getHeaderActions(): array
    {
        /** @var OvertimeRequest $record */
        $record = $this->getRecord();
        /** @var User|null $user */
        $user = Auth::user();
        $canApprove = (bool) ($user && ($user->hasRole('atasan') || $user->hasRole('admin')));

        return [
            EditAction::make()
                ->visible(fn (): bool => $record->isEditable()),

            Action::make('submit')
                ->label('Ajukan ke Atasan')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $record->isEditable())
                ->action(function () use ($record, $user): void {
                    $res = app(OvertimeService::class)->submit($record, $user);
                    $notif = Notification::make()
                        ->title('Berhasil Diajukan')
                        ->body("Pengajuan {$res['request']->getDisplayCode()} menunggu persetujuan.");
                    if ($res['spkl_warning']) {
                        $notif->warning()->body("Pengajuan dikirim. Pengingat: {$res['spkl_warning']}");
                    } else {
                        $notif->success();
                    }
                    $notif->send();
                }),

            Action::make('approve')
                ->label('Setujui & Terbitkan')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (): bool => $canApprove && $record->status === OvertimeRequest::STATUS_SUBMITTED)
                ->schema([
                    FileUpload::make('signed_pdf_path')
                        ->label('Unggah PDF Bertanda Tangan TTE (Opsional)')
                        ->disk('public')
                        ->directory('overtime/signed-sptpl')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(5120),
                    Textarea::make('notes')
                        ->label('Catatan Persetujuan')
                        ->rows(2),
                ])
                ->action(function (array $data) use ($record, $user): void {
                    $res = app(OvertimeService::class)->approve($record, $user, $data['notes'] ?? null, $data['signed_pdf_path'] ?? null);
                    $notif = Notification::make()
                        ->title('Pengajuan Disetujui')
                        ->body("Nomor resmi: {$res['request']->getDisplayCode()}.");
                    if ($res['spkl_warning']) {
                        $notif->warning()->body("Disetujui ({$res['request']->getDisplayCode()}). Pengingat: {$res['spkl_warning']}");
                    } else {
                        $notif->success();
                    }
                    $notif->send();
                }),

            Action::make('return_revision')
                ->label('Kembalikan')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('info')
                ->visible(fn (): bool => $canApprove && $record->status === OvertimeRequest::STATUS_SUBMITTED)
                ->schema([
                    Textarea::make('notes')->label('Catatan Perbaikan')->required()->rows(3),
                ])
                ->action(function (array $data) use ($record, $user): void {
                    app(OvertimeService::class)->returnForRevision($record, $user, $data['notes']);
                    Notification::make()->title('Dikembalikan untuk perbaikan')->info()->send();
                }),

            Action::make('reject')
                ->label('Tolak')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn (): bool => $canApprove && $record->status === OvertimeRequest::STATUS_SUBMITTED)
                ->schema([
                    Textarea::make('notes')->label('Alasan Penolakan')->required()->rows(3),
                ])
                ->action(function (array $data) use ($record, $user): void {
                    app(OvertimeService::class)->reject($record, $user, $data['notes']);
                    Notification::make()->title('Pengajuan Ditolak')->danger()->send();
                }),

            Action::make('cancel')
                ->label('Batalkan Surat')
                ->icon(Heroicon::OutlinedNoSymbol)
                ->color('danger')
                ->visible(fn (): bool => ! in_array($record->status, [OvertimeRequest::STATUS_CANCELLED, OvertimeRequest::STATUS_REJECTED], true))
                ->schema([
                    Textarea::make('cancellation_reason')->label('Alasan Pembatalan Wajib')->required()->minLength(5)->rows(3),
                ])
                ->action(function (array $data) use ($record, $user): void {
                    app(OvertimeService::class)->cancel($record, $user, $data['cancellation_reason']);
                    Notification::make()->title('Surat Lembur Dibatalkan')->danger()->send();
                }),

            Action::make('print_pdf')
                ->label('Cetak / PDF SPTPL')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('primary')
                ->visible(fn (): bool => $record->request_type === OvertimeRequest::TYPE_WITH_LETTER)
                ->url(fn (): string => route('admin.overtime.request.print', $record))
                ->openUrlInNewTab(),
        ];
    }
}
