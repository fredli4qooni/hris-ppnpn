<?php

namespace App\Filament\Resources\EmployeeViolations;

use App\Filament\Resources\EmployeeViolations\Pages\CreateEmployeeViolation;
use App\Filament\Resources\EmployeeViolations\Pages\EditEmployeeViolation;
use App\Filament\Resources\EmployeeViolations\Pages\ListEmployeeViolations;
use App\Filament\Resources\EmployeeViolations\Pages\ViewEmployeeViolation;
use App\Filament\Resources\EmployeeViolations\Schemas\EmployeeViolationForm;
use App\Filament\Resources\EmployeeViolations\Schemas\EmployeeViolationInfolist;
use App\Filament\Resources\EmployeeViolations\Tables\EmployeeViolationsTable;
use App\Models\EmployeeViolation;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class EmployeeViolationResource extends Resource
{
    protected static ?string $model = EmployeeViolation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static \UnitEnum|string|null $navigationGroup = 'Penegakan Disiplin & SPK';

    protected static ?string $navigationLabel = 'Pelanggaran Disiplin SPK';

    protected static ?string $modelLabel = 'Pelanggaran Disiplin';

    protected static ?string $pluralModelLabel = 'Pelanggaran Disiplin SPK';

    public static function form(Schema $schema): Schema
    {
        return EmployeeViolationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmployeeViolationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeViolationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = Auth::user();

        // Supervisor can only see violations of their direct subordinates or logged by them
        if ($user && $user->hasRole('supervisor') && ! $user->hasRole('admin')) {
            $query->where(function (Builder $q) use ($user): void {
                $q->where('supervisor_id', $user->id)
                    ->orWhereHas('employee', function (Builder $eq) use ($user): void {
                        $eq->where('supervisor_id', $user->id);
                    });
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
            'index' => ListEmployeeViolations::route('/'),
            'create' => CreateEmployeeViolation::route('/create'),
            'view' => ViewEmployeeViolation::route('/{record}'),
            'edit' => EditEmployeeViolation::route('/{record}/edit'),
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

        return $user?->can('view-team-violations') || $user?->hasAnyRole(['admin', 'supervisor', 'atasan']);
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->can('record-violations') || $user?->hasAnyRole(['admin', 'supervisor']);
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
