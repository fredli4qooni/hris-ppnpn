<?php

namespace App\Filament\Resources\EmployeeViolations\Pages;

use App\Filament\Resources\EmployeeViolations\EmployeeViolationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeViolations extends ListRecords
{
    protected static string $resource = EmployeeViolationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
