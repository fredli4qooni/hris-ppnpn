<?php

namespace App\Filament\Resources\OfficeLocations\Pages;

use App\Filament\Resources\OfficeLocations\OfficeLocationResource;
use App\Models\AuditLog;
use App\Models\OfficeLocation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateOfficeLocation extends CreateRecord
{
    protected static string $resource = OfficeLocationResource::class;

    protected function afterCreate(): void
    {
        /** @var OfficeLocation $record */
        $record = $this->record;

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'OFFICE_LOCATION_CREATED',
            'entity_type' => OfficeLocation::class,
            'entity_id' => $record->id,
            'new_values' => [
                'name' => $record->name,
                'latitude' => $record->latitude,
                'longitude' => $record->longitude,
                'radius_meters' => $record->radius_meters,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
