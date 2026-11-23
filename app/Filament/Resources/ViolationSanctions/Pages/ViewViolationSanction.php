<?php

namespace App\Filament\Resources\ViolationSanctions\Pages;

use App\Filament\Resources\ViolationSanctions\ViolationSanctionResource;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewViolationSanction extends ViewRecord
{
    protected static string $resource = ViolationSanctionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(function (): bool {
                    /** @var User|null $user */
                    $user = Auth::user();

                    return (bool) $user?->hasRole('admin');
                }),
        ];
    }
}
