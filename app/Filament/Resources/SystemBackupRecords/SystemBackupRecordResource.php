<?php

namespace App\Filament\Resources\SystemBackupRecords;

use App\Filament\Resources\SystemBackupRecords\Pages\ListSystemBackupRecords;
use App\Filament\Resources\SystemBackupRecords\Tables\SystemBackupRecordsTable;
use App\Models\SystemBackupRecord;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SystemBackupRecordResource extends Resource
{
    protected static ?string $model = SystemBackupRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxArrowDown;

    protected static \UnitEnum|string|null $navigationGroup = 'Pengaturan & Master Data';

    protected static ?string $navigationLabel = 'Pencadangan Sistem (DRP)';

    protected static ?string $modelLabel = 'Arsip Cadangan DRP';

    protected static ?string $pluralModelLabel = 'Pencadangan Sistem & DRP (US-802)';

    protected static ?int $navigationSort = 11;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return (bool) $user->hasRole('admin');
    }

    public static function table(Table $table): Table
    {
        return SystemBackupRecordsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSystemBackupRecords::route('/'),
        ];
    }
}
