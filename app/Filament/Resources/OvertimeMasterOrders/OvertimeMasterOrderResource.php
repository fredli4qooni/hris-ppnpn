<?php

namespace App\Filament\Resources\OvertimeMasterOrders;

use App\Filament\Resources\OvertimeMasterOrders\Pages\CreateOvertimeMasterOrder;
use App\Filament\Resources\OvertimeMasterOrders\Pages\EditOvertimeMasterOrder;
use App\Filament\Resources\OvertimeMasterOrders\Pages\ListOvertimeMasterOrders;
use App\Filament\Resources\OvertimeMasterOrders\Schemas\OvertimeMasterOrderForm;
use App\Filament\Resources\OvertimeMasterOrders\Tables\OvertimeMasterOrdersTable;
use App\Models\OvertimeMasterOrder;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OvertimeMasterOrderResource extends Resource
{
    protected static ?string $model = OvertimeMasterOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static \UnitEnum|string|null $navigationGroup = 'Manajemen Lembur';

    protected static ?string $navigationLabel = 'SPKL Bulanan';

    protected static ?string $modelLabel = 'SPKL Bulanan';

    protected static ?string $pluralModelLabel = 'SPKL Bulanan';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) ($user && ($user->hasRole('admin') || $user->hasRole('atasan') || $user->hasRole('supervisor')));
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) ($user && ($user->hasRole('admin') || $user->hasRole('atasan')));
    }

    public static function canEdit(Model $record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) ($user && ($user->hasRole('admin') || $user->hasRole('atasan')));
    }

    public static function canDelete(Model $record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) $user?->hasRole('admin') && ! $record->isIssued();
    }

    public static function form(Schema $schema): Schema
    {
        return OvertimeMasterOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OvertimeMasterOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOvertimeMasterOrders::route('/'),
            'create' => CreateOvertimeMasterOrder::route('/create'),
            'edit' => EditOvertimeMasterOrder::route('/{record}/edit'),
        ];
    }
}
