<?php

namespace App\Filament\Resources\OfficeLocations\Tables;

use App\Models\OfficeLocation;
use App\Services\GeofenceService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OfficeLocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kantor / Titik')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(45)
                    ->tooltip(fn ($record) => $record->address)
                    ->placeholder('-'),

                TextColumn::make('latitude')
                    ->label('Latitude')
                    ->sortable(),

                TextColumn::make('longitude')
                    ->label('Longitude')
                    ->sortable(),

                TextColumn::make('radius_meters')
                    ->label('Radius')
                    ->suffix(' m')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->actions([
                Action::make('testGps')
                    ->label('Uji Jarak GPS')
                    ->icon('heroicon-o-calculator')
                    ->color('info')
                    ->modalHeading(fn (OfficeLocation $record) => "Uji Radius Geofence: {$record->name}")
                    ->modalDescription('Masukkan koordinat GPS uji coba untuk mengecek apakah titik tersebut berada di dalam perimeter toleransi absensi.')
                    ->modalSubmitActionLabel('Hitung Jarak')
                    ->modalWidth('md')
                    ->form([
                        TextInput::make('test_latitude')
                            ->label('Latitude Uji Coba')
                            ->numeric()
                            ->required()
                            ->step('0.0000001')
                            ->default(fn (OfficeLocation $record) => $record->latitude)
                            ->helperText('Contoh: -5.3971400'),

                        TextInput::make('test_longitude')
                            ->label('Longitude Uji Coba')
                            ->numeric()
                            ->required()
                            ->step('0.0000001')
                            ->default(fn (OfficeLocation $record) => $record->longitude)
                            ->helperText('Contoh: 105.2667900'),
                    ])
                    ->action(function (OfficeLocation $record, array $data, GeofenceService $geofenceService): void {
                        $testLat = (float) $data['test_latitude'];
                        $testLon = (float) $data['test_longitude'];

                        $result = $geofenceService->validatePresenceLocation($testLat, $testLon, $record);

                        if ($result['is_valid']) {
                            Notification::make()
                                ->title('Lokasi VALID (Dalam Perimeter)')
                                ->body("Jarak dari {$record->name}: {$result['distance_meters']} meter (Maksimal: {$result['allowed_radius']} m). Presensi diperbolehkan.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Lokasi DITOLAK (Out of Bounds)')
                                ->body("Jarak dari {$record->name}: {$result['distance_meters']} meter melebihi batas toleransi {$result['allowed_radius']} m. Presensi ditolak.")
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),

                Action::make('openMaps')
                    ->label('Peta')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (OfficeLocation $record): string => "https://www.google.com/maps?q={$record->latitude},{$record->longitude}")
                    ->openUrlInNewTab(),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
