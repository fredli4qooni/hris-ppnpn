<?php

namespace App\Filament\Resources\ViolationSanctions;

use App\Filament\Resources\ViolationSanctions\Pages\CreateViolationSanction;
use App\Filament\Resources\ViolationSanctions\Pages\EditViolationSanction;
use App\Filament\Resources\ViolationSanctions\Pages\ListViolationSanctions;
use App\Filament\Resources\ViolationSanctions\Pages\ViewViolationSanction;
use App\Filament\Resources\ViolationSanctions\Schemas\ViolationSanctionForm;
use App\Filament\Resources\ViolationSanctions\Schemas\ViolationSanctionInfolist;
use App\Filament\Resources\ViolationSanctions\Tables\ViolationSanctionsTable;
use App\Models\User;
use App\Models\ViolationSanction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ViolationSanctionResource extends Resource
{
    protected static ?string $model = ViolationSanction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static \UnitEnum|string|null $navigationGroup = 'Penegakan Disiplin & SPK';

    protected static ?string $navigationLabel = 'Sanksi & Hukuman SPK';

    protected static ?string $modelLabel = 'Sanksi Disiplin';

    protected static ?string $pluralModelLabel = 'Sanksi & Hukuman Disiplin';

    public static function form(Schema $schema): Schema
    {
        return ViolationSanctionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ViolationSanctionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ViolationSanctionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = Auth::user();

        // If supervisor, scope to subordinates
        if ($user && $user->hasRole('supervisor') && ! $user->hasRole('admin')) {
            $query->whereHas('employee', function (Builder $eq) use ($user): void {
                $eq->where('supervisor_id', $user->id);
            });
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListViolationSanctions::route('/'),
            'create' => CreateViolationSanction::route('/create'),
            'view' => ViewViolationSanction::route('/{record}'),
            'edit' => EditViolationSanction::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->can('view-sanctions') || $user?->hasAnyRole(['admin', 'supervisor', 'atasan']);
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) $user?->hasRole('admin');
    }

    public static function canEdit(Model $record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) $user?->hasRole('admin');
    }

    public static function canDelete(Model $record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) $user?->hasRole('admin');
    }
}
