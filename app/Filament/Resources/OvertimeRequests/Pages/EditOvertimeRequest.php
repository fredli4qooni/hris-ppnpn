<?php

namespace App\Filament\Resources\OvertimeRequests\Pages;

use App\Filament\Resources\OvertimeRequests\OvertimeRequestResource;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Services\OvertimeService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditOvertimeRequest extends EditRecord
{
    protected static string $resource = OvertimeRequestResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var OvertimeRequest $record */
        $record = $this->getRecord();
        $data['items'] = $record->items()
            ->get()
            ->map(fn ($item): array => [
                'employee_id' => $item->employee_id,
                'start_time' => $item->start_time,
                'end_time' => $item->end_time,
                'description' => $item->description,
            ])
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var OvertimeRequest $record */

        return app(OvertimeService::class)->updateRequest($record, $user, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
