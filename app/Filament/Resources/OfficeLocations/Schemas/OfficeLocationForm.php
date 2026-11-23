<?php

namespace App\Filament\Resources\OfficeLocations\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OfficeLocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Lokasi & Geofence Kantor')
                    ->description('Tentukan nama kantor, alamat resmi, dan titik koordinat acuan perimeter presensi.')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lokasi / Kantor')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Contoh: Kanwil DJPb Provinsi Lampung'),

                        Textarea::make('address')
                            ->label('Alamat Lengkap')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Jl. Teuku Umar No. 187, Kedaton, Bandar Lampung'),

                        Grid::make(3)->schema([
                            TextInput::make('latitude')
                                ->label('Latitude')
                                ->numeric()
                                ->required()
                                ->step('0.0000001')
                                ->placeholder('-5.3971400')
                                ->helperText('Koordinat lintang (desimal)'),

                            TextInput::make('longitude')
                                ->label('Longitude')
                                ->numeric()
                                ->required()
                                ->step('0.0000001')
                                ->placeholder('105.2667900')
                                ->helperText('Koordinat bujur (desimal)'),

                            TextInput::make('radius_meters')
                                ->label('Radius Geofence (Meter)')
                                ->numeric()
                                ->required()
                                ->default(100)
                                ->minValue(10)
                                ->maxValue(5000)
                                ->suffix(' meter')
                                ->helperText('Batas toleransi kehadiran (standar: 100 m)'),
                        ]),

                        Toggle::make('is_active')
                            ->label('Status Lokasi Aktif')
                            ->helperText('Hanya titik lokasi aktif yang digunakan untuk memverifikasi absensi pegawai.')
                            ->default(true),
                    ]),

                Section::make('Peta Interaktif & Pencarian Lokasi Kantor')
                    ->description('Cari lokasi langsung di peta, geser pin (drag), atau klik peta untuk menentukan titik koordinat perimeter presensi secara dinamis.')
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('interactive_map_picker')
                            ->hiddenLabel()
                            ->content(function ($record = null, $get = null) {
                                $lat = ($get ? $get('latitude') : null) ?? ($record?->latitude ?? -5.3971400);
                                $lon = ($get ? $get('longitude') : null) ?? ($record?->longitude ?? 105.2667900);
                                $radius = ($get ? $get('radius_meters') : null) ?? ($record?->radius_meters ?? 100);

                                return view('filament.forms.components.interactive-map-picker', [
                                    'record' => $record,
                                    'get' => $get ?? fn ($k) => null,
                                    'lat' => $lat,
                                    'lon' => $lon,
                                    'radius' => $radius,
                                ]);
                            }),
                    ]),
            ]);
    }
}
