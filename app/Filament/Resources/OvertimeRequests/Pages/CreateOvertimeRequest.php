<?php

namespace App\Filament\Resources\OvertimeRequests\Pages;

use App\Filament\Resources\OvertimeRequests\OvertimeRequestResource;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Services\OvertimeService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateOvertimeRequest extends CreateRecord
{
    protected static string $resource = OvertimeRequestResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $user */
        $user = Auth::user();

        return app(OvertimeService::class)->createRequest($user, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('apply_to_all_rows')
                ->label('Terapkan Waktu & Uraian ke Semua Baris')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->color('info')
                ->schema([
                    TextInput::make('start_time')->label('Jam Mulai')->type('time')->default('17:30')->required(),
                    TextInput::make('end_time')->label('Jam Selesai')->type('time')->default('19:30')->required(),
                    Textarea::make('description')->label('Uraian Kegiatan')->required()->rows(2),
                ])
                ->action(function (array $data): void {
                    $state = $this->form->getRawState();
                    $items = $state['items'] ?? [];
                    foreach ($items as $key => $row) {
                        $items[$key]['start_time'] = $data['start_time'];
                        $items[$key]['end_time'] = $data['end_time'];
                        $items[$key]['description'] = $data['description'];
                    }
                    $state['items'] = $items;
                    $this->form->fill($state);

                    Notification::make()->title('Waktu dan uraian diterapkan ke semua pegawai')->success()->send();
                }),

            Action::make('copy_previous_sptpl')
                ->label('Salin dari SPTPL Sebelumnya')
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->color('gray')
                ->action(function (): void {
                    $latest = OvertimeRequest::with('items')
                        ->where('request_type', OvertimeRequest::TYPE_WITH_LETTER)
                        ->latest('id')
                        ->first();

                    if (! $latest) {
                        Notification::make()->title('Belum ada SPTPL sebelumnya')->warning()->send();

                        return;
                    }

                    $state = $this->form->getRawState();
                    $state['items'] = $latest->items->map(fn ($item): array => [
                        'employee_id' => $item->employee_id,
                        'start_time' => $item->start_time,
                        'end_time' => $item->end_time,
                        'description' => $item->description,
                    ])->all();

                    $this->form->fill($state);

                    Notification::make()
                        ->title("Daftar pegawai disalin dari {$latest->getDisplayCode()}")
                        ->success()
                        ->send();
                }),
        ];
    }
}
