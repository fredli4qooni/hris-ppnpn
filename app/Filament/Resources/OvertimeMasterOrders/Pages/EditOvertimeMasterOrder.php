<?php

namespace App\Filament\Resources\OvertimeMasterOrders\Pages;

use App\Filament\Resources\OvertimeMasterOrders\OvertimeMasterOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOvertimeMasterOrder extends EditRecord
{
    protected static string $resource = OvertimeMasterOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
