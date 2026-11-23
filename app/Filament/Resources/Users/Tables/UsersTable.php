<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\AuditLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Pengguna')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyMessage('Username disalin ke clipboard'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('roles.name')
                    ->label('Peran (Role)')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'atasan' => 'warning',
                        'supervisor' => 'info',
                        'karyawan' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'Administrator',
                        'atasan' => 'Atasan / PPK',
                        'supervisor' => 'Supervisor',
                        'karyawan' => 'PPNPN',
                        default => ucfirst($state),
                    }),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('must_change_password')
                    ->label('Wajib Ubah Sandi')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_login_at')
                    ->label('Login Terakhir')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Belum Pernah'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Filter Peran')
                    ->relationship('roles', 'name')
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua Status')
                    ->trueLabel('Hanya Akun Aktif')
                    ->falseLabel('Hanya Akun Nonaktif'),
            ])
            ->actions([
                Action::make('resetPassword')
                    ->label('Reset Sandi')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('warning')
                    ->modalHeading(fn (User $record): string => "Reset Kata Sandi: {$record->name}")
                    ->modalDescription('Masukkan kata sandi baru untuk pengguna ini. Pengguna dapat dipaksa mengganti kata sandi ini saat login berikutnya.')
                    ->modalSubmitActionLabel('Simpan Kata Sandi')
                    ->form([
                        TextInput::make('new_password')
                            ->label('Kata Sandi Baru')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(6)
                            ->maxLength(255),

                        Toggle::make('must_change_password')
                            ->label('Wajibkan ubah kata sandi saat login berikutnya')
                            ->default(true),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update([
                            'password' => $data['new_password'],
                            'must_change_password' => (bool) $data['must_change_password'],
                        ]);

                        AuditLog::create([
                            'user_id' => Auth::id(),
                            'action' => 'USER_PASSWORD_RESET',
                            'entity_type' => User::class,
                            'entity_id' => $record->id,
                            'new_values' => [
                                'target_username' => $record->username,
                                'must_change_password' => $record->must_change_password,
                            ],
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]);

                        Notification::make()
                            ->title('Kata Sandi Berhasil Direset')
                            ->body("Kata sandi untuk pengguna [{$record->username}] telah diperbarui.")
                            ->success()
                            ->send();
                    }),

                Action::make('toggleActive')
                    ->label(fn (User $record): string => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (User $record) => $record->is_active ? Heroicon::OutlinedLockClosed : Heroicon::OutlinedLockOpen)
                    ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => ($record->is_active ? 'Nonaktifkan' : 'Aktifkan')." Akun: {$record->name}")
                    ->modalDescription(fn (User $record): string => $record->is_active
                        ? "Apakah Anda yakin ingin menonaktifkan akun {$record->username}? Pengguna tidak akan dapat login ke sistem."
                        : "Apakah Anda yakin ingin mengaktifkan kembali akun {$record->username}?")
                    ->hidden(fn (User $record): bool => $record->id === Auth::id())
                    ->action(function (User $record): void {
                        $record->update([
                            'is_active' => ! $record->is_active,
                        ]);

                        AuditLog::create([
                            'user_id' => Auth::id(),
                            'action' => 'USER_STATUS_TOGGLED',
                            'entity_type' => User::class,
                            'entity_id' => $record->id,
                            'new_values' => [
                                'username' => $record->username,
                                'is_active' => $record->is_active,
                            ],
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]);

                        $statusLabel = $record->is_active ? 'diaktifkan' : 'dinonaktifkan';

                        Notification::make()
                            ->title("Akun Berhasil {$statusLabel}")
                            ->body("Status akun [{$record->username}] kini: ".($record->is_active ? 'Aktif' : 'Nonaktif'))
                            ->success()
                            ->send();
                    }),

                EditAction::make(),

                DeleteAction::make()
                    ->hidden(fn (User $record): bool => $record->id === Auth::id()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
