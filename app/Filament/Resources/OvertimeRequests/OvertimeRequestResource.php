<?php

namespace App\Filament\Resources\OvertimeRequests;

use App\Filament\Resources\OvertimeRequests\Pages\CreateOvertimeRequest;
use App\Filament\Resources\OvertimeRequests\Pages\EditOvertimeRequest;
use App\Filament\Resources\OvertimeRequests\Pages\ListOvertimeRequests;
use App\Filament\Resources\OvertimeRequests\Pages\ViewOvertimeRequest;
use App\Filament\Resources\OvertimeRequests\Schemas\OvertimeRequestForm;
use App\Filament\Resources\OvertimeRequests\Schemas\OvertimeRequestInfolist;
use App\Filament\Resources\OvertimeRequests\Tables\OvertimeRequestsTable;
use App\Models\OvertimeRequest;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OvertimeRequestResource extends Resource
{
    protected static ?string $model = OvertimeRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static \UnitEnum|string|null $navigationGroup = 'Manajemen Lembur';

    protected static ?string $navigationLabel = 'Pengajuan Lembur & SPTPL';

    protected static ?string $modelLabel = 'Pengajuan Lembur';

    protected static ?string $pluralModelLabel = 'Pengajuan Lembur & SPTPL';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) ($user && ($user->hasRole('admin') || $user->hasRole('supervisor') || $user->hasRole('atasan')));
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) ($user && ($user->hasRole('admin') || $user->hasRole('supervisor') || $user->hasRole('atasan')));
    }

    public static function canEdit(Model $record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $record instanceof OvertimeRequest || ! $record->isEditable()) {
            return false;
        }

        return (bool) ($user && ($user->hasRole('admin') || $user->hasRole('supervisor')));
    }

    public static function canDelete(Model $record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $record instanceof OvertimeRequest || $record->isApproved()) {
            return false;
        }

        return (bool) $user?->hasRole('admin');
    }

    public static function form(Schema $schema): Schema
    {
        return OvertimeRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OvertimeRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OvertimeRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOvertimeRequests::route('/'),
            'create' => CreateOvertimeRequest::route('/create'),
            'view' => ViewOvertimeRequest::route('/{record}'),
            'edit' => EditOvertimeRequest::route('/{record}/edit'),
        ];
    }
}
