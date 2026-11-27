<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttendanceHistoryController extends Controller
{
    /**
     * Indonesian month names.
     *
     * @var array<int, string>
     */
    protected const MONTH_NAMES = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    /**
     * Indonesian day names.
     *
     * @var array<string, string>
     */
    protected const DAY_NAMES = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
    ];

    /**
     * Display personal attendance history with monthly filter and KPI summary (FR-211 & FR-307).
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Employee|null $employee */
        $employee = $user->employee()
            ->with(['position'])
            ->first();

        // Determine current time in WIB
        $nowWib = Carbon::now('Asia/Jakarta');

        $selectedYear = (int) $request->input('year', $nowWib->year);
        $selectedMonth = (int) $request->input('month', $nowWib->month);

        if ($selectedMonth < 1 || $selectedMonth > 12) {
            $selectedMonth = $nowWib->month;
        }

        if ($selectedYear < 2020 || $selectedYear > 2050) {
            $selectedYear = $nowWib->year;
        }

        $currentPeriod = Carbon::createFromDate($selectedYear, $selectedMonth, 1, 'Asia/Jakarta')->startOfMonth();
        $prevPeriod = $currentPeriod->copy()->subMonth();
        $nextPeriod = $currentPeriod->copy()->addMonth();

        /** @var Collection<int, Attendance> $attendances */
        $attendances = collect();

        $summary = [
            'total_days' => 0,
            'on_time_count' => 0,
            'tolerance_count' => 0,
            'tolerance_compensated_count' => 0,
            'tolerance_uncompensated_count' => 0,
            'late_tl_count' => 0,
            'tl_1_count' => 0,
            'tl_2_count' => 0,
            'tl_3_count' => 0,
            'psw_count' => 0,
            'total_deduction_percentage' => 0.0,
            'estimated_deduction_amount' => 0.0,
        ];

        /** @var FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');

        if ($employee) {
            $attendances = Attendance::with(['position', 'officeLocation'])
                ->where('employee_id', $employee->id)
                ->whereYear('date', $selectedYear)
                ->whereMonth('date', $selectedMonth)
                ->orderBy('date', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            $summary['total_days'] = $attendances->count();
            $summary['on_time_count'] = $attendances->where('status', Attendance::STATUS_ON_TIME)->count();
            $summary['tolerance_count'] = $attendances->where('status', Attendance::STATUS_TOLERANCE)->count();
            $summary['tolerance_compensated_count'] = $attendances->where('status', Attendance::STATUS_TOLERANCE)
                ->where('is_compensated', true)
                ->count();
            $summary['tolerance_uncompensated_count'] = $attendances->where('status', Attendance::STATUS_TOLERANCE)
                ->where('is_compensated', false)
                ->count();
            $summary['late_tl_count'] = $attendances->where('status', Attendance::STATUS_LATE_TL)->count();
            $summary['tl_1_count'] = $attendances->where('tl_category', Attendance::TL_1)->count();
            $summary['tl_2_count'] = $attendances->where('tl_category', Attendance::TL_2)->count();
            $summary['tl_3_count'] = $attendances->where('tl_category', Attendance::TL_3)->count();
            $summary['psw_count'] = $attendances->filter(fn (Attendance $att): bool => $att->isPsw())->count();
            $summary['total_deduction_percentage'] = (float) $attendances->sum('deduction_percentage');

            if ($employee->base_salary) {
                $summary['estimated_deduction_amount'] = round(
                    ((float) $employee->base_salary * $summary['total_deduction_percentage']) / 100,
                    2
                );
            }

            // Transform each attendance item for presentation
            $attendances->transform(function (Attendance $att) use ($publicDisk): Attendance {
                $dayNameEnglish = $att->date ? $att->date->format('l') : 'Sunday';
                $dayNameIndo = self::DAY_NAMES[$dayNameEnglish] ?? $dayNameEnglish;
                $monthNum = $att->date ? (int) $att->date->format('n') : 1;
                $monthNameIndo = self::MONTH_NAMES[$monthNum] ?? '';

                $att->setAttribute('day_name', $dayNameIndo);
                $att->setAttribute('month_name', $monthNameIndo);
                $att->setAttribute(
                    'formatted_date',
                    $att->date ? "{$dayNameIndo}, {$att->date->format('d')} {$monthNameIndo} {$att->date->format('Y')}" : '-'
                );

                $att->setAttribute(
                    'check_in_time_formatted',
                    $att->check_in_at ? $att->check_in_at->timezone('Asia/Jakarta')->format('H:i').' WIB' : '-'
                );
                $att->setAttribute(
                    'check_in_time_full',
                    $att->check_in_at ? $att->check_in_at->timezone('Asia/Jakarta')->format('H:i:s').' WIB' : '-'
                );

                $att->setAttribute(
                    'check_out_time_formatted',
                    $att->check_out_at ? $att->check_out_at->timezone('Asia/Jakarta')->format('H:i').' WIB' : null
                );
                $att->setAttribute(
                    'check_out_time_full',
                    $att->check_out_at ? $att->check_out_at->timezone('Asia/Jakarta')->format('H:i:s').' WIB' : null
                );

                $att->setAttribute(
                    'check_in_photo_url',
                    $att->check_in_photo_path ? $publicDisk->url($att->check_in_photo_path) : null
                );
                $att->setAttribute(
                    'check_out_photo_url',
                    $att->check_out_photo_path ? $publicDisk->url($att->check_out_photo_path) : null
                );

                return $att;
            });
        }

        $monthName = self::MONTH_NAMES[$selectedMonth] ?? '';

        $attendancesByDate = $attendances->keyBy(fn (Attendance $att): string => $att->date->toDateString());
        $leadingEmptyCells = $currentPeriod->dayOfWeekIso - 1;
        $todayDateString = $nowWib->toDateString();

        $calendarDays = [];
        for ($day = 1; $day <= $currentPeriod->daysInMonth; $day++) {
            $dateObj = $currentPeriod->copy()->day($day);
            $dateKey = $dateObj->toDateString();
            /** @var Attendance|null $att */
            $att = $attendancesByDate->get($dateKey);

            $state = 'empty';
            if ($att) {
                if ($att->isLateTl() || $att->isPsw()) {
                    $state = 'late';
                } elseif ($att->isTolerance()) {
                    $state = 'tolerance';
                } else {
                    $state = 'on_time';
                }
            }

            $calendarDays[] = [
                'day' => $day,
                'date' => $dateKey,
                'is_today' => $dateKey === $todayDateString,
                'is_weekend' => $dateObj->isWeekend(),
                'attendance' => $att,
                'check_in_short' => $att?->check_in_at ? $att->check_in_at->timezone('Asia/Jakarta')->format('H:i') : null,
                'check_out_short' => $att?->check_out_at
                    ? $att->check_out_at->timezone('Asia/Jakarta')->format('H:i')
                    : ($att?->check_in_at ? '--:--' : null),
                'state' => $state,
            ];
        }

        return view('mobile.attendance.history', [
            'user' => $user,
            'employee' => $employee,
            'attendances' => $attendances,
            'calendarDays' => $calendarDays,
            'leadingEmptyCells' => $leadingEmptyCells,
            'summary' => $summary,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'monthName' => $monthName,
            'currentPeriod' => $currentPeriod,
            'prevPeriod' => $prevPeriod,
            'nextPeriod' => $nextPeriod,
            'monthOptions' => self::MONTH_NAMES,
        ]);
    }
}
