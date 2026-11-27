<?php

namespace App\Filament\Resources\Holidays;

use App\Filament\Resources\Holidays\Pages\ManageHolidays;
use App\Models\Holiday;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static \UnitEnum|string|null $navigationGroup = 'Manajemen Lembur';

    protected static ?string $navigationLabel = 'Kalender Hari Libur';

    protected static ?string $modelLabel = 'Hari Libur';

    protected static ?string $pluralModelLabel = 'Kalender Hari Libur';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) ($user && ($user->hasRole('admin') || $user->hasRole('supervisor') || $user->hasRole('atasan')));
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) $user?->hasRole('admin');
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->label('Tanggal Libur')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->native(false)
                    ->displayFormat('d/m/Y'),

                TextInput::make('name')
                    ->label('Keterangan Hari Libur')
                    ->required()
                    ->maxLength(150)
                    ->placeholder('Contoh: Hari Raya Idul Fitri / Cuti Bersama'),

                Toggle::make('is_national_holiday')
                    ->label('Hari Libur Nasional / Cuti Bersama')
                    ->default(true),

                Textarea::make('notes')
                    ->label('Catatan Tambahan')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Keterangan Hari Libur')
                    ->searchable()
                    ->weight('bold'),

                IconColumn::make('is_national_holiday')
                    ->label('Libur Nasional')
                    ->boolean(),

                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(40)
                    ->placeholder('-'),
            ])
            ->defaultSort('date', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageHolidays::route('/'),
        ];
    }
}
