<?php

namespace App\Filament\Resources\UatTestCases;

use App\Filament\Resources\UatTestCases\Pages\ListUatTestCases;
use App\Filament\Resources\UatTestCases\Tables\UatTestCasesTable;
use App\Models\UatTestCase;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UatTestCaseResource extends Resource
{
    protected static ?string $model = UatTestCase::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static \UnitEnum|string|null $navigationGroup = 'Pengujian & Mutu';

    protected static ?string $navigationLabel = 'Pengujian UAT Multi-Aktor';

    protected static ?string $modelLabel = 'Kasus Uji UAT';

    protected static ?string $pluralModelLabel = 'Skenario UAT Multi-Aktor (US-701)';

    protected static ?int $navigationSort = 1;

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
        return UatTestCasesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUatTestCases::route('/'),
        ];
    }
}
