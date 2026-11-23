<?php

namespace App\Filament\Resources\AttendanceCorrections;

use App\Filament\Resources\AttendanceCorrections\Pages\ListAttendanceCorrections;
use App\Filament\Resources\AttendanceCorrections\Pages\ViewAttendanceCorrection;
use App\Filament\Resources\AttendanceCorrections\Schemas\AttendanceCorrectionInfolist;
use App\Filament\Resources\AttendanceCorrections\Tables\AttendanceCorrectionsTable;
use App\Models\AttendanceCorrection;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AttendanceCorrectionResource extends Resource
{
    protected static ?string $model = AttendanceCorrection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static \UnitEnum|string|null $navigationGroup = 'Manajemen Kehadiran & Izin';

    protected static ?string $navigationLabel = 'Koreksi Lupa Absen';

    protected static ?string $modelLabel = 'Koreksi Lupa Absen';

    protected static ?string $pluralModelLabel = 'Koreksi Lupa Absen';

    protected static ?int $navigationSort = 3;

    public static function infolist(Schema $schema): Schema
    {
        return AttendanceCorrectionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceCorrectionsTable::configure($table);
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
            'index' => ListAttendanceCorrections::route('/'),
            'view' => ViewAttendanceCorrection::route('/{record}'),
        ];
    }
}
