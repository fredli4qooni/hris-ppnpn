<?php

namespace App\Filament\Resources\EmployeeViolations\Pages;

use App\Filament\Resources\EmployeeViolations\EmployeeViolationResource;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewEmployeeViolation extends ViewRecord
{
    protected static string $resource = EmployeeViolationResource::class;

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
