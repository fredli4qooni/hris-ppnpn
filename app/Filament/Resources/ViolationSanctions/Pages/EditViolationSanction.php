<?php

namespace App\Filament\Resources\ViolationSanctions\Pages;

use App\Filament\Resources\ViolationSanctions\ViolationSanctionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditViolationSanction extends EditRecord
{
    protected static string $resource = ViolationSanctionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
