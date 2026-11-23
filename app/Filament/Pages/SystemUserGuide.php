<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class SystemUserGuide extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static \UnitEnum|string|null $navigationGroup = 'Bantuan & Panduan';

    protected static ?string $navigationLabel = 'Buku Panduan Operasional';

    protected static ?string $title = 'Buku Panduan Operasional Sistem';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.system-user-guide';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('admin') || $user->hasRole('supervisor') || $user->hasRole('atasan');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label('Unduh Manual Book (PDF)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('primary')
                ->url(fn (): string => route('mobile.guide.download-pdf'), shouldOpenInNewTab: true),
        ];
    }
}
