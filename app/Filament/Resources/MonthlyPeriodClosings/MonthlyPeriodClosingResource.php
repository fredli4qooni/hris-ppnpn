<?php

namespace App\Filament\Resources\MonthlyPeriodClosings;

use App\Filament\Resources\MonthlyPeriodClosings\Pages\ListMonthlyPeriodClosings;
use App\Filament\Resources\MonthlyPeriodClosings\Tables\MonthlyPeriodClosingsTable;
use App\Models\MonthlyPeriodClosing;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MonthlyPeriodClosingResource extends Resource
{
    protected static ?string $model = MonthlyPeriodClosing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static \UnitEnum|string|null $navigationGroup = 'Rekapitulasi & Penggajian';

    protected static ?string $navigationLabel = 'Penutupan Periode';

    protected static ?string $modelLabel = 'Penutupan Periode';

    protected static ?string $pluralModelLabel = 'Penutupan Periode Bulanan';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('admin') || $user->hasRole('atasan');
    }

    public static function table(Table $table): Table
    {
        return MonthlyPeriodClosingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonthlyPeriodClosings::route('/'),
        ];
    }
}
