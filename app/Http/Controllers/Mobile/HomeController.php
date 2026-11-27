<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    /**
     * Display the employee mobile home dashboard.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $employee = $user->employee()->with('position')->first();

        $nowWib = Carbon::now('Asia/Jakarta');
        $todayFormatted = $nowWib->copy()->locale('id')->translatedFormat('l, d F Y');

        $summaryPeriod = $request->query('summary_period') === 'last_month' ? 'last_month' : 'this_month';
        $targetMonth = $summaryPeriod === 'last_month'
            ? $nowWib->copy()->subMonthNoOverflow()
            : $nowWib->copy();

        $contractDays = $employee?->getDaysUntilContractExpiry();
        $ews = $employee?->getEwsStatus();
        $activePoints = $employee?->calculateActiveViolationPoints() ?? 0;

        /** @var FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');
        $photoUrl = $employee?->photo_path ? $publicDisk->url($employee->photo_path) : null;

        $displayName = trim((string) ($employee?->full_name ?? $user->name));
        $nameParts = preg_split('/\s+/', $displayName) ?: [$displayName];
        $initials = count($nameParts) >= 2
            ? strtoupper(substr($nameParts[0], 0, 1).substr($nameParts[1], 0, 1))
            : strtoupper(substr($displayName, 0, 2));

        $todayAttendance = null;
        $monthlySummary = [
            'period_key' => $summaryPeriod,
            'period_label' => $targetMonth->copy()->locale('id')->translatedFormat('F Y'),
            'present_days' => 0,
            'leave_days' => 0,
            'alpha_days' => 0,
            'attendance_rate' => 0,
        ];

        if ($employee) {
            $todayAttendance = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('date', $nowWib->toDateString())
                ->latest('id')
                ->first();

            $monthAttendances = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereYear('date', $targetMonth->year)
                ->whereMonth('date', $targetMonth->month)
                ->get(['status', 'check_in_at']);

            $presentDays = $monthAttendances
                ->filter(fn (Attendance $att): bool => $att->check_in_at !== null || in_array($att->status, [
                    Attendance::STATUS_ON_TIME,
                    Attendance::STATUS_TOLERANCE,
                    Attendance::STATUS_LATE_TL,
                ], true))
                ->count();

            $leaveDays = $monthAttendances
                ->whereIn('status', [
                    Attendance::STATUS_LEAVE,
                    Attendance::STATUS_PERMIT,
                    Attendance::STATUS_SICK,
                ])
                ->count();

            $alphaDays = $monthAttendances
                ->where('status', Attendance::STATUS_ALPHA)
                ->count();

            $totalTracked = $presentDays + $leaveDays + $alphaDays;
            $attendanceRate = $totalTracked > 0 ? (int) round(($presentDays / $totalTracked) * 100) : 0;

            $monthlySummary = [
                'period_key' => $summaryPeriod,
                'period_label' => $targetMonth->copy()->locale('id')->translatedFormat('F Y'),
                'present_days' => $presentDays,
                'leave_days' => $leaveDays,
                'alpha_days' => $alphaDays,
                'attendance_rate' => $attendanceRate,
            ];
        }

        $officeBannerUrl = null;
        foreach (['images/kantor-djpb.jpg', 'images/kantor-djpb.png', 'images/kantor-djpb.webp'] as $candidatePath) {
            if (file_exists(public_path($candidatePath))) {
                $officeBannerUrl = asset($candidatePath);
                break;
            }
        }

        return view('mobile.home', [
            'user' => $user,
            'employee' => $employee,
            'displayName' => $displayName,
            'initials' => $initials,
            'photoUrl' => $photoUrl,
            'todayFormatted' => $todayFormatted,
            'contractDays' => $contractDays,
            'ews' => $ews,
            'activePoints' => $activePoints,
            'todayAttendance' => $todayAttendance,
            'monthlySummary' => $monthlySummary,
            'officeBannerUrl' => $officeBannerUrl,
        ]);
    }
}
