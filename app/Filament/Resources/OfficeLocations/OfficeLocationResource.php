<?php

namespace App\Filament\Resources\OfficeLocations;

use App\Filament\Resources\OfficeLocations\Pages\CreateOfficeLocation;
use App\Filament\Resources\OfficeLocations\Pages\EditOfficeLocation;
use App\Filament\Resources\OfficeLocations\Pages\ListOfficeLocations;
use App\Filament\Resources\OfficeLocations\Schemas\OfficeLocationForm;
use App\Filament\Resources\OfficeLocations\Tables\OfficeLocationsTable;
use App\Models\OfficeLocation;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OfficeLocationResource extends Resource
{
    protected static ?string $model = OfficeLocation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static \UnitEnum|string|null $navigationGroup = 'Pengaturan & Master Data';

    protected static ?string $navigationLabel = 'Titik Lokasi Kantor';

    protected static ?string $modelLabel = 'Titik Lokasi';

    protected static ?string $pluralModelLabel = 'Titik Lokasi & Geofence';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return OfficeLocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OfficeLocationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOfficeLocations::route('/'),
            'create' => CreateOfficeLocation::route('/create'),
            'edit' => EditOfficeLocation::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->can('manage-offices') || $user?->hasAnyRole(['admin', 'supervisor', 'atasan']);
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->can('manage-offices') || $user?->hasRole('admin');
    }

    public static function canEdit(Model $record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->can('manage-offices') || $user?->hasRole('admin');
    }

    public static function canDelete(Model $record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->can('manage-offices') || $user?->hasRole('admin');
    }
}
