<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class DjpbWelcomeWidget extends Widget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.djpb-welcome-widget';

    public function getUserName(): string
    {
        return Auth::user()?->name ?? 'Pengguna';
    }

    public function getUserRoleLabel(): string
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return 'Tamu';
        }

        $roles = $user->roles->pluck('name')->map(fn (string $r): string => match ($r) {
            'admin' => 'Administrator Sistem',
            'atasan' => 'Pejabat Penyetuju (Atasan / PPK)',
            'supervisor' => 'Supervisor Lapangan',
            'karyawan' => 'Pegawai PPNPN',
            default => ucfirst($r),
        })->join(', ');

        return $roles ?: 'Pengguna Terdaftar';
    }

    public function getCurrentDate(): string
    {
        return Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y');
    }
}
