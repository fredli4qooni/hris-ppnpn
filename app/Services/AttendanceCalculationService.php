<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Position;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class AttendanceCalculationService
{
    /**
     * Maximum late threshold in minutes for 15-minute tolerance grace period.
     */
    public const TOLERANCE_MINUTES = 15;

    /**
     * Upper bound minutes for TL 1 late category (16 to 30 minutes).
     */
    public const TL_1_MAX_MINUTES = 30;

    /**
     * Upper bound minutes for TL 2 late category (31 to 60 minutes).
     */
    public const TL_2_MAX_MINUTES = 60;

    /**
     * Deduction percentages according to Lampiran IV SPK.
     */
    public const DEDUCTION_ON_TIME = 0.00;

    public const DEDUCTION_TOLERANCE = 0.00;

    public const DEDUCTION_TL_1 = 0.50;

    public const DEDUCTION_TL_2 = 1.00;

    public const DEDUCTION_TL_3 = 1.50;

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

        // Calculate late minutes
        if ($checkIn->lessThanOrEqualTo($scheduleStart)) {
            $lateMinutes = 0;
            $status = Attendance::STATUS_ON_TIME;
            $tlCategory = Attendance::TL_NONE;
            $deductionPercentage = self::DEDUCTION_ON_TIME;
            $requiredCompensationMinutes = 0;
            $statusLabel = 'Tepat Waktu';
            $notes = 'Hadir tepat waktu sebelum atau pada jam dinas resmi.';
        } else {
            $lateMinutes = (int) $scheduleStart->diffInMinutes($checkIn, false);

            if ($lateMinutes <= self::TOLERANCE_MINUTES) {
                // 1 to 15 minutes late: In tolerance, no deduction, but must compensate at checkout
                $status = Attendance::STATUS_TOLERANCE;
                $tlCategory = Attendance::TL_NONE;
                $deductionPercentage = self::DEDUCTION_TOLERANCE;
                $requiredCompensationMinutes = $lateMinutes;
                $statusLabel = "Terlambat dalam Toleransi ({$lateMinutes} Menit)";
                $notes = "Terlambat {$lateMinutes} menit dalam masa toleransi 15 menit. Wajib mengganti jam kepulangan {$lateMinutes} menit lebih lama.";
            } else {
                // > 15 minutes late: TL Category with percentage deduction
                $status = Attendance::STATUS_LATE_TL;
                $requiredCompensationMinutes = 0;

                if ($lateMinutes <= self::TL_1_MAX_MINUTES) {
                    $tlCategory = Attendance::TL_1;
                    $deductionPercentage = self::DEDUCTION_TL_1;
                    $statusLabel = "Terlambat TL 1 ({$lateMinutes} Menit)";
                    $notes = "Keterlambatan {$lateMinutes} menit masuk kategori TL 1 (Potongan 0,5%).";
                } elseif ($lateMinutes <= self::TL_2_MAX_MINUTES) {
                    $tlCategory = Attendance::TL_2;
                    $deductionPercentage = self::DEDUCTION_TL_2;
                    $statusLabel = "Terlambat TL 2 ({$lateMinutes} Menit)";
                    $notes = "Keterlambatan {$lateMinutes} menit masuk kategori TL 2 (Potongan 1,0%).";
                } else {
                    $tlCategory = Attendance::TL_3;
                    $deductionPercentage = self::DEDUCTION_TL_3;
                    $statusLabel = "Terlambat TL 3 ({$lateMinutes} Menit)";
                    $notes = "Keterlambatan {$lateMinutes} menit masuk kategori TL 3 (Potongan 1,5%).";
                }
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
        $position = $attendance->position;

        $dateStr = $attendance->date instanceof CarbonInterface
            ? $attendance->date->toDateString()
            : (string) $attendance->date;

        // Determine schedule end in Asia/Jakarta timezone
        if ($attendance->shift_type === Attendance::SHIFT_SECURITY_NIGHT || $position->is_overnight) {
            $scheduleEnd = Carbon::parse($dateStr, 'Asia/Jakarta')->addDay()->setTimeFromTimeString($position->default_end_time);
        } else {
            $scheduleEnd = Carbon::parse($dateStr, 'Asia/Jakarta')->setTimeFromTimeString($position->default_end_time);
        }

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
}
