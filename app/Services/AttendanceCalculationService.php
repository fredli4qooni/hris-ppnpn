<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Position;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class AttendanceCalculationService
{
    /**
     * Deduction percentages according to Lampiran IV SPK.
     */
    public const DEDUCTION_ON_TIME = 0.00;

    public const DEDUCTION_TOLERANCE = 0.00;

    public const DEDUCTION_TL_1 = 0.50;

    public const DEDUCTION_TL_2 = 1.00;

    public const DEDUCTION_TL_3 = 1.50;

    /**
     * Role-based TL threshold cutoffs (HH:MM:SS) keyed by tl_group.
     *
     * @var array<string, array{tl_1: array{int, int, int}, tl_2: array{int, int, int}, tl_3: array{int, int, int}}>
     */
    private const TL_GROUP_CUTOFFS = [
        'OB' => [
            'tl_1' => [6, 46, 0],
            'tl_2' => [7, 1, 0],
            'tl_3' => [7, 30, 0],
        ],
        'STANDARD' => [
            'tl_1' => [7, 1, 0],
            'tl_2' => [7, 31, 0],
            'tl_3' => [8, 0, 0],
        ],
        'SECURITY_DAY' => [
            'tl_1' => [7, 1, 0],
            'tl_2' => [7, 31, 0],
            'tl_3' => [8, 0, 0],
        ],
        'SECURITY_NIGHT' => [
            'tl_1' => [19, 1, 0],
            'tl_2' => [19, 31, 0],
            'tl_3' => [20, 0, 0],
        ],
    ];

    /**
     * Calculate check-in status, late minutes, TL category, and deduction percentage.
     *
     * @return array{
     *     work_date: string,
     *     schedule_start: Carbon,
     *     schedule_end: Carbon,
     *     check_in_time: Carbon,
     *     late_minutes: int,
     *     status: string,
     *     tl_category: string,
     *     deduction_percentage: float,
     *     required_compensation_minutes: int,
     *     shift_type: string,
     *     status_label: string,
     *     notes: string
     * }
     */
    public function calculateCheckIn(Position $dutyPosition, CarbonInterface $checkInTime): array
    {
        // Ensure Carbon instance with Asia/Jakarta timezone
        $checkIn = Carbon::instance($checkInTime)->timezone('Asia/Jakarta');

        $shiftType = $this->resolveShiftType($dutyPosition);

        // Determine work date and scheduled start/end times
        if ($shiftType === Attendance::SHIFT_SECURITY_NIGHT && $checkIn->hour < 12) {
            // Check-in in the early morning for overnight shift counts towards previous calendar date
            $workDate = $checkIn->copy()->subDay()->toDateString();
            $scheduleStart = $checkIn->copy()->subDay()->setTimeFromTimeString($dutyPosition->default_start_time);
            $scheduleEnd = $checkIn->copy()->setTimeFromTimeString($dutyPosition->default_end_time);
        } else {
            $workDate = $checkIn->toDateString();
            $scheduleStart = $checkIn->copy()->setTimeFromTimeString($dutyPosition->default_start_time);
            $scheduleEnd = $checkIn->copy()->setTimeFromTimeString($dutyPosition->default_end_time);

            if ($dutyPosition->is_overnight) {
                $scheduleEnd = $scheduleEnd->addDay();
            }
        }

        $workCarbon = Carbon::parse($workDate, 'Asia/Jakarta');
        $thresholds = $this->resolveTlThresholds($dutyPosition, $shiftType, $workCarbon);

        // Evaluate check-in against role-specific TL thresholds (TL 0 / TL 1 / TL 2 / TL 3)
        if ($checkIn->lessThan($thresholds['tl_1_start'])) {
            $lateMinutes = 0;
            $status = Attendance::STATUS_ON_TIME;
            $tlCategory = Attendance::TL_NONE;
            $deductionPercentage = self::DEDUCTION_ON_TIME;
            $requiredCompensationMinutes = 0;
            $statusLabel = 'Tepat Waktu';
            $notes = 'Hadir tepat waktu sesuai jadwal.';
        } else {
            $lateMinutes = max(1, (int) $scheduleStart->diffInMinutes($checkIn, false));
            $status = Attendance::STATUS_LATE_TL;
            $requiredCompensationMinutes = 0;

            if ($checkIn->lessThan($thresholds['tl_2_start'])) {
                $tlCategory = Attendance::TL_1;
                $deductionPercentage = self::DEDUCTION_TL_1;
                $statusLabel = "Terlambat TL 1 ({$lateMinutes} Menit)";
                $notes = "Keterlambatan {$lateMinutes} menit masuk kategori TL 1.";
            } elseif ($checkIn->lessThan($thresholds['tl_3_start'])) {
                $tlCategory = Attendance::TL_2;
                $deductionPercentage = self::DEDUCTION_TL_2;
                $statusLabel = "Terlambat TL 2 ({$lateMinutes} Menit)";
                $notes = "Keterlambatan {$lateMinutes} menit masuk kategori TL 2.";
            } else {
                $tlCategory = Attendance::TL_3;
                $deductionPercentage = self::DEDUCTION_TL_3;
                $statusLabel = "Terlambat TL 3 ({$lateMinutes} Menit)";
                $notes = "Keterlambatan {$lateMinutes} menit masuk kategori TL 3.";
            }
        }

        return [
            'work_date' => $workDate,
            'schedule_start' => $scheduleStart,
            'schedule_end' => $scheduleEnd,
            'check_in_time' => $checkIn,
            'late_minutes' => $lateMinutes,
            'status' => $status,
            'tl_category' => $tlCategory,
            'deduction_percentage' => $deductionPercentage,
            'required_compensation_minutes' => $requiredCompensationMinutes,
            'shift_type' => $shiftType,
            'status_label' => $statusLabel,
            'notes' => $notes,
        ];
    }

    /**
     * Resolve the official scheduled check-out timestamp (WIB) for an attendance record.
     */
    public function getScheduledEndTime(Attendance $attendance): Carbon
    {
        $position = $attendance->position;
        $dateStr = $attendance->date instanceof CarbonInterface
            ? $attendance->date->toDateString()
            : (string) $attendance->date;

        if ($attendance->shift_type === Attendance::SHIFT_SECURITY_NIGHT || $position->is_overnight) {
            return Carbon::parse($dateStr, 'Asia/Jakarta')->addDay()->setTimeFromTimeString($position->default_end_time);
        }

        return Carbon::parse($dateStr, 'Asia/Jakarta')->setTimeFromTimeString($position->default_end_time);
    }

    /**
     * Calculate check-out rules, tolerance replacement verification, and early leave (PSW) detection (FR-207, FR-208 / US-305).
     *
     * @return array{
     *     schedule_end: Carbon,
     *     target_end: Carbon,
     *     check_out_time: Carbon,
     *     early_leave_minutes: int,
     *     is_early_leave: bool,
     *     is_compensated: bool,
     *     check_out_status: 'ON_TIME'|'LUNAS_PENGGANTIAN'|'TIDAK_MENGGANTI_JAM'|'PSW',
     *     status_label: string,
     *     notes: string
     * }
     */
    public function calculateCheckOut(Attendance $attendance, CarbonInterface $checkOutTime): array
    {
        $checkOut = Carbon::instance($checkOutTime)->timezone('Asia/Jakarta');
        $scheduleEnd = $this->getScheduledEndTime($attendance);

        $requiredMinutes = (int) $attendance->required_compensation_minutes;
        $targetEnd = $scheduleEnd->copy()->addMinutes($requiredMinutes);

        // Check for Early Leave (Pulang Sebelum Waktunya - PSW)
        if ($checkOut->lessThan($scheduleEnd)) {
            $earlyLeaveMinutes = (int) $checkOut->diffInMinutes($scheduleEnd, false);
            $isEarlyLeave = true;
            $isCompensated = false;
            $checkOutStatus = Attendance::CHECK_OUT_PSW;
            $statusLabel = "Pulang Sebelum Waktunya (PSW) - {$earlyLeaveMinutes} Menit";
            $notes = "Pulang lebih awal {$earlyLeaveMinutes} menit sebelum jam dinas resmi berakhir ({$scheduleEnd->format('H:i')} WIB).";
        } else {
            $earlyLeaveMinutes = 0;
            $isEarlyLeave = false;

            if ($attendance->isTolerance() || $requiredMinutes > 0) {
                if ($checkOut->greaterThanOrEqualTo($targetEnd)) {
                    $isCompensated = true;
                    $checkOutStatus = Attendance::CHECK_OUT_COMPENSATED;
                    $statusLabel = "Lunas Penggantian Jam ({$requiredMinutes} Menit)";
                    $notes = "Kewajiban penggantian jam toleransi {$requiredMinutes} menit telah lunas diganti (pulang pukul {$checkOut->format('H:i:s')} WIB).";
                } else {
                    $shortfall = (int) $checkOut->diffInMinutes($targetEnd, false);
                    $isCompensated = false;
                    $checkOutStatus = Attendance::CHECK_OUT_UNCOMPENSATED;
                    $statusLabel = "Belum Mengganti Jam Penuh (Kurang {$shortfall} Menit)";
                    $notes = "Kewajiban penggantian jam toleransi {$requiredMinutes} menit belum terpenuhi sepenuhnya (kurang {$shortfall} menit dari target {$targetEnd->format('H:i')} WIB).";
                }
            } else {
                $isCompensated = true;
                $checkOutStatus = Attendance::CHECK_OUT_ON_TIME;
                $statusLabel = 'Pulang Sesuai Jadwal';
                $notes = 'Presensi pulang tercatat sesuai jam kedinasan resmi.';
            }
        }

        return [
            'schedule_end' => $scheduleEnd,
            'target_end' => $targetEnd,
            'check_out_time' => $checkOut,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'is_early_leave' => $isEarlyLeave,
            'is_compensated' => $isCompensated,
            'check_out_status' => $checkOutStatus,
            'status_label' => $statusLabel,
            'notes' => $notes,
        ];
    }

    /**
     * Resolve shift type based on position code and metadata.
     */
    public function resolveShiftType(Position $position): string
    {
        if ($position->code === 'SECURITY_NIGHT' || $position->is_overnight) {
            return Attendance::SHIFT_SECURITY_NIGHT;
        }

        if ($position->code === 'SECURITY_DAY') {
            return Attendance::SHIFT_SECURITY_DAY;
        }

        return Attendance::SHIFT_NORMAL;
    }

    /**
     * Resolve exact TL tier cutoff timestamps on the work date for a position.
     *
     * @return array{tl_1_start: Carbon, tl_2_start: Carbon, tl_3_start: Carbon}
     */
    public function resolveTlThresholds(Position $position, string $shiftType, Carbon $workDate): array
    {
        $group = $this->resolveTlGroup($position, $shiftType);
        $cutoffs = self::TL_GROUP_CUTOFFS[$group] ?? self::TL_GROUP_CUTOFFS['STANDARD'];

        return [
            'tl_1_start' => $workDate->copy()->setTime(...$cutoffs['tl_1']),
            'tl_2_start' => $workDate->copy()->setTime(...$cutoffs['tl_2']),
            'tl_3_start' => $workDate->copy()->setTime(...$cutoffs['tl_3']),
        ];
    }

    /**
     * Resolve the TL policy group key for a position.
     */
    private function resolveTlGroup(Position $position, string $shiftType): string
    {
        if ($position->tl_group && isset(self::TL_GROUP_CUTOFFS[$position->tl_group])) {
            return $position->tl_group;
        }

        return match (true) {
            $shiftType === Attendance::SHIFT_SECURITY_NIGHT => 'SECURITY_NIGHT',
            $shiftType === Attendance::SHIFT_SECURITY_DAY => 'SECURITY_DAY',
            $position->code === 'OB' => 'OB',
            default => 'STANDARD',
        };
    }
}
