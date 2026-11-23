<?php

namespace App\Filament\Resources\ViolationSanctions\Pages;

use App\Filament\Resources\ViolationSanctions\ViolationSanctionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListViolationSanctions extends ListRecords
{
    protected static string $resource = ViolationSanctionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
