<?php

namespace App\Filament\Resources\LateExceptions;

use App\Filament\Resources\LateExceptions\Pages\ListLateExceptions;
use App\Filament\Resources\LateExceptions\Pages\ViewLateException;
use App\Filament\Resources\LateExceptions\Schemas\LateExceptionInfolist;
use App\Filament\Resources\LateExceptions\Tables\LateExceptionsTable;
use App\Models\LateException;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LateExceptionResource extends Resource
{
    protected static ?string $model = LateException::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static \UnitEnum|string|null $navigationGroup = 'Manajemen Kehadiran & Izin';

    protected static ?string $navigationLabel = 'Dispensasi Kahar (TL)';

    protected static ?string $modelLabel = 'Dispensasi Kahar';

    protected static ?string $pluralModelLabel = 'Dispensasi Kahar (Force Majeure)';

    protected static ?int $navigationSort = 4;

    public static function infolist(Schema $schema): Schema
    {
        return LateExceptionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LateExceptionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = Auth::user();

        // Supervisor hanya melihat pengajuan dari pegawai di bawah subordinat langsungnya
        if ($user && $user->hasRole('supervisor') && ! $user->hasRole('admin')) {
            $query->whereHas('employee', function (Builder $q) use ($user): void {
                $q->where('supervisor_id', $user->id);
            });
        }

        // Atasan hanya melihat pengajuan dari pegawai di bawah unit persetujuannya
        if ($user && $user->hasRole('atasan') && ! $user->hasRole('admin')) {
            $query->whereHas('employee', function (Builder $q) use ($user): void {
                $q->where('atasan_id', $user->id);
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLateExceptions::route('/'),
            'view' => ViewLateException::route('/{record}'),
        ];
    }
}
