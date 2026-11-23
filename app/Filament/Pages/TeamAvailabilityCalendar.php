<?php

namespace App\Filament\Pages;

use App\Models\Position;
use App\Models\User;
use App\Services\LeaveRequestService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class TeamAvailabilityCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static \UnitEnum|string|null $navigationGroup = 'Manajemen Kehadiran & Izin';

    protected static ?string $navigationLabel = 'Kalender Regu Kerja';

    protected static ?string $title = 'Kalender Ketersediaan & Jadwal Regu Kerja';

    protected string $view = 'filament.pages.team-availability-calendar';

    public int $selectedMonth;

    public int $selectedYear;

    public ?int $selectedPositionId = null;

    public function mount(): void
    {
        $this->selectedMonth = (int) request('month', now()->month);
        $this->selectedYear = (int) request('year', now()->year);
        $this->selectedPositionId = request('position_id') ? (int) request('position_id') : null;
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('admin') || $user->hasRole('supervisor') || $user->hasRole('atasan');
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        $supervisorId = ($user && $user->hasRole('supervisor') && ! $user->hasRole('admin')) ? $user->id : null;

        $service = app(LeaveRequestService::class);
        $calendarData = $service->getTeamAvailability(
            $this->selectedYear,
            $this->selectedMonth,
            $supervisorId,
            $this->selectedPositionId
        );

        $positions = Position::where('is_active', true)->orderBy('name')->get();

        return [
            'calendarData' => $calendarData,
            'positions' => $positions,
            'selectedMonth' => $this->selectedMonth,
            'selectedYear' => $this->selectedYear,
            'selectedPositionId' => $this->selectedPositionId,
        ];
    }
}
