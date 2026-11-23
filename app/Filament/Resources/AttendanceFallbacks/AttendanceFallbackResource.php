<?php

namespace App\Filament\Resources\AttendanceFallbacks;

use App\Filament\Resources\AttendanceFallbacks\Pages\ListAttendanceFallbacks;
use App\Filament\Resources\AttendanceFallbacks\Tables\AttendanceFallbacksTable;
use App\Models\Attendance;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AttendanceFallbackResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static \UnitEnum|string|null $navigationGroup = 'Manajemen Kehadiran & Izin';

    protected static ?string $navigationLabel = 'Verifikasi Presensi Fallback';

    protected static ?string $modelLabel = 'Presensi Fallback';

    protected static ?string $pluralModelLabel = 'Verifikasi Presensi Fallback';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return AttendanceFallbacksTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('is_fallback', true);

        /** @var User|null $user */
        $user = Auth::user();

        // Supervisor hanya memverifikasi subordinat langsungnya
        if ($user && $user->hasRole('supervisor') && ! $user->hasRole('admin')) {
            $query->whereHas('employee', function (Builder $q) use ($user): void {
                $q->where('supervisor_id', $user->id);
            });
        }

        // Atasan hanya memverifikasi bawahan dalam unitnya
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
            'index' => ListAttendanceFallbacks::route('/'),
        ];
    }
}
