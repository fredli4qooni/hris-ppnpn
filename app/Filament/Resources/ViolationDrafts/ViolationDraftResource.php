<?php

namespace App\Filament\Resources\ViolationDrafts;

use App\Filament\Resources\ViolationDrafts\Pages\ListViolationDrafts;
use App\Filament\Resources\ViolationDrafts\Pages\ViewViolationDraft;
use App\Filament\Resources\ViolationDrafts\Schemas\ViolationDraftInfolist;
use App\Filament\Resources\ViolationDrafts\Tables\ViolationDraftsTable;
use App\Models\User;
use App\Models\ViolationDraft;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ViolationDraftResource extends Resource
{
    protected static ?string $model = ViolationDraft::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static \UnitEnum|string|null $navigationGroup = 'Penegakan Disiplin & SPK';

    protected static ?string $navigationLabel = 'Draf Pelanggaran Disiplin';

    protected static ?string $modelLabel = 'Draf Pelanggaran';

    protected static ?string $pluralModelLabel = 'Draf Pelanggaran Disiplin';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        /** @var User|null $user */
        $user = Auth::user();

        $query = ViolationDraft::where('status', ViolationDraft::STATUS_PENDING);

        if ($user && $user->hasRole('supervisor') && ! $user->hasRole('admin')) {
            $query->whereHas('employee', fn (Builder $q) => $q->where('supervisor_id', $user->id));
        }

        $count = $query->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return ViolationDraftInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ViolationDraftsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = Auth::user();

        // Supervisor hanya melihat draf pelanggaran dari pegawai di bawah subordinat langsungnya
        if ($user && $user->hasRole('supervisor') && ! $user->hasRole('admin')) {
            $query->whereHas('employee', function (Builder $q) use ($user): void {
                $q->where('supervisor_id', $user->id);
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListViolationDrafts::route('/'),
            'view' => ViewViolationDraft::route('/{record}'),
        ];
    }
}
