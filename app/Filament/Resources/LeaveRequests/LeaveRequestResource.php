<?php

namespace App\Filament\Resources\LeaveRequests;

use App\Filament\Resources\LeaveRequests\Pages\ListLeaveRequests;
use App\Filament\Resources\LeaveRequests\Pages\ViewLeaveRequest;
use App\Filament\Resources\LeaveRequests\Schemas\LeaveRequestForm;
use App\Filament\Resources\LeaveRequests\Schemas\LeaveRequestInfolist;
use App\Filament\Resources\LeaveRequests\Tables\LeaveRequestsTable;
use App\Models\LeaveRequest;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static \UnitEnum|string|null $navigationGroup = 'Manajemen Kehadiran & Izin';

    protected static ?string $navigationLabel = 'Persetujuan Cuti & Izin';

    protected static ?string $modelLabel = 'Pengajuan Cuti / Izin';

    protected static ?string $pluralModelLabel = 'Persetujuan Cuti & Izin';

    public static function form(Schema $schema): Schema
    {
        return LeaveRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LeaveRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveRequestsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = Auth::user();

        // Supervisor hanya melihat pengajuan subordinat langsungnya atau yang ditugaskan kepadanya
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

    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('admin') || $user->hasRole('supervisor') || $user->hasRole('atasan');
    }

    public static function canCreate(): bool
    {
        return false; // Pengajuan resmi dibuat oleh pegawai via portal Mobile PWA
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

    public static function getPages(): array
    {
        return [
            'index' => ListLeaveRequests::route('/'),
            'view' => ViewLeaveRequest::route('/{record}'),
        ];
    }
}
