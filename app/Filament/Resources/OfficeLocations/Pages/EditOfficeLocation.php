<?php

namespace App\Filament\Resources\OfficeLocations\Pages;

use App\Filament\Resources\OfficeLocations\OfficeLocationResource;
use App\Models\AuditLog;
use App\Models\OfficeLocation;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditOfficeLocation extends EditRecord
{
    protected static string $resource = OfficeLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        /** @var OfficeLocation $record */
        $record = $this->record;

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'OFFICE_LOCATION_UPDATED',
            'entity_type' => OfficeLocation::class,
            'entity_id' => $record->id,
            'new_values' => [
                'name' => $record->name,
                'latitude' => $record->latitude,
                'longitude' => $record->longitude,
                'radius_meters' => $record->radius_meters,
                'is_active' => $record->is_active,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
