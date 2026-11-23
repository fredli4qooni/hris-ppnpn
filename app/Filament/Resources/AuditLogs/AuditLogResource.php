<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Tables\AuditLogsTable;
use App\Models\AuditLog;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static \UnitEnum|string|null $navigationGroup = 'Pengaturan & Master Data';

    protected static ?string $navigationLabel = 'Jejak Audit Sistem';

    protected static ?string $modelLabel = 'Jejak Audit';

    protected static ?string $pluralModelLabel = 'Jejak Audit Sistem';

    protected static ?int $navigationSort = 10;

    /**
     * Strict access restriction: Only administrator can access the Audit Trail (FR-902 / US-605).
     */
    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) $user?->hasRole('admin');
    }

    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) $user?->hasRole('admin');
    }

    /**
     * Absolute immutability: Audit records can NEVER be created manually via panel.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Absolute immutability: Audit records can NEVER be edited.
     */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * Absolute immutability: Audit records can NEVER be deleted.
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /**
     * Absolute immutability: Bulk delete is strictly forbidden.
     */
    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return AuditLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
