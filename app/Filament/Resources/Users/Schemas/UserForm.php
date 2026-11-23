<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Akun Pengguna')
                    ->description('Kredensial dan data otentikasi akun pengguna sistem.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Nama Lengkap')
                                ->required()
                                ->maxLength(150)
                                ->placeholder('Contoh: Budi Santoso'),

                            TextInput::make('username')
                                ->label('Username')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->minLength(3)
                                ->maxLength(50)
                                ->regex('/^[a-zA-Z0-9._-]+$/')
                                ->validationMessages([
                                    'regex' => 'Username hanya boleh berisi huruf, angka, titik, minus, dan garis bawah.',
                                ])
                                ->placeholder('Contoh: budi.santoso')
                                ->helperText('Username digunakan untuk login ke web dan portal mobile.'),

                            TextInput::make('email')
                                ->label('Alamat Email')
                                ->email()
                                ->unique(ignoreRecord: true)
                                ->maxLength(150)
                                ->nullable()
                                ->placeholder('budi@kemenkeu.go.id'),

                            TextInput::make('password')
                                ->label('Kata Sandi (Password)')
                                ->password()
                                ->revealable()
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->dehydrated(fn (?string $state): bool => filled($state))
                                ->minLength(6)
                                ->maxLength(255)
                                ->helperText(fn (string $operation): string => $operation === 'create'
                                    ? 'Minimal 6 karakter.'
                                    : 'Kosongkan jika tidak ingin mengubah password.'),

                            Select::make('roles')
                                ->label('Peran / Hak Akses (Role)')
                                ->relationship('roles', 'name')
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->required()
                                ->getOptionLabelFromRecordUsing(fn ($record): string => match ($record->name) {
                                    'admin' => 'Administrator Sistem',
                                    'supervisor' => 'Supervisor Lapangan',
                                    'atasan' => 'Pejabat Penyetuju (Atasan / PPK)',
                                    'karyawan' => 'Pegawai PPNPN',
                                    default => ucfirst($record->name),
                                })
                                ->helperText('Menentukan hak akses menu dan alur persetujuan dalam sistem.')
                                ->columnSpanFull(),
                        ]),
                    ]),

                Section::make('Status & Kebijakan Keamanan')
                    ->description('Pengaturan status aktivasi dan kewajiban pergantian kata sandi.')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('is_active')
                                ->label('Status Akun Aktif')
                                ->default(true)
                                ->helperText('Akun nonaktif tidak diizinkan masuk ke panel web maupun aplikasi mobile.')
                                ->inline(false),

                            Toggle::make('must_change_password')
                                ->label('Wajib Ganti Password')
                                ->default(false)
                                ->helperText('Pengguna akan dipaksa mengganti kata sandi default saat login pertama kali.')
                                ->inline(false),
                        ]),
                    ]),
            ]);
    }
}
