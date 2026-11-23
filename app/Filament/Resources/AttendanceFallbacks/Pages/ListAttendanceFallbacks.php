<?php

namespace App\Filament\Resources\AttendanceFallbacks\Pages;

use App\Filament\Resources\AttendanceFallbacks\AttendanceFallbackResource;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceFallbacks extends ListRecords
{
    protected static string $resource = AttendanceFallbackResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
