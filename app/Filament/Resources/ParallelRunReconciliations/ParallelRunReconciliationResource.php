<?php

namespace App\Filament\Resources\ParallelRunReconciliations;

use App\Filament\Resources\ParallelRunReconciliations\Pages\ListParallelRunReconciliations;
use App\Filament\Resources\ParallelRunReconciliations\Tables\ParallelRunReconciliationsTable;
use App\Models\ParallelRunReconciliation;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ParallelRunReconciliationResource extends Resource
{
    protected static ?string $model = ParallelRunReconciliation::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static \UnitEnum|string|null $navigationGroup = 'Pengujian & Mutu';

    protected static ?string $navigationLabel = 'Rekonsiliasi Pilot Run';

    protected static ?string $modelLabel = 'Rekonsiliasi Pilot Run';

    protected static ?string $pluralModelLabel = 'Rekonsiliasi Pilot Run Paralel (US-801)';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('admin') || $user->hasRole('atasan') || $user->hasRole('supervisor');
    }

    public static function table(Table $table): Table
    {
        return ParallelRunReconciliationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParallelRunReconciliations::route('/'),
        ];
    }
}
