<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\MonthlyPeriodClosing;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use App\Services\AttendanceCalculationService;
use App\Services\AttendancePhotoService;
use App\Services\BiometricVerificationService;
use App\Services\GeofenceService;
use Illuminate\Contracts\View\View;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    /**
     * Attendance services for biometric, geofencing, rule calculation, and photo thumbnail handling.
     */
    public function __construct(
        protected BiometricVerificationService $biometricService,
        protected GeofenceService $geofenceService,
        protected AttendanceCalculationService $attendanceCalculationService,
        protected AttendancePhotoService $photoService
    ) {}

    /**
     * Display the mobile PWA attendance scanner and position selection interface (FR-201, FR-202 / US-301).
     */
    public function showScanner(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Employee|null $employee */
        $employee = $user->employee()
            ->with(['position', 'supervisor', 'atasan', 'faceTemplate'])
            ->first();

        if (! $employee || ! $employee->is_active) {
            return redirect()->route('mobile.home')->with('error', 'Status akun pegawai Anda tidak aktif (masa kontrak berakhir). Presensi diblokir.');
        }

        $activePositions = Position::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $officeLocation = OfficeLocation::query()
            ->where('is_active', true)
            ->first();

        // Resolve active attendance: Check for open overnight Satpam shift first, then today's attendance
        $serverNow = now()->timezone('Asia/Jakarta');
        $todayWib = $serverNow->toDateString();

        $openOvernight = Attendance::query()
            ->with(['position'])
            ->where('employee_id', $employee->id)
            ->where('shift_type', Attendance::SHIFT_SECURITY_NIGHT)
            ->whereDate('date', Carbon::parse($todayWib)->subDay()->toDateString())
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->first();

        if ($openOvernight && $serverNow->hour < 12) {
            $activeAttendance = $openOvernight;
        } else {
            $activeAttendance = Attendance::query()
                ->with(['position'])
                ->where('employee_id', $employee->id)
                ->whereDate('date', $todayWib)
                ->first();
        }

        $isCheckedIn = $activeAttendance && $activeAttendance->check_in_at !== null;
        $isCheckedOut = $activeAttendance && $activeAttendance->check_out_at !== null;

        if ($isCheckedIn && $isCheckedOut) {
            $attendanceMode = 'COMPLETED';
        } elseif ($isCheckedIn && ! $isCheckedOut) {
            $attendanceMode = 'CHECK_OUT';
        } else {
            $attendanceMode = 'CHECK_IN';
        }

        $canCheckOut = true;
        $scheduledCheckOutTime = null;

        if ($attendanceMode === 'CHECK_OUT' && $activeAttendance && $activeAttendance->position) {
            $scheduleEnd = $this->attendanceCalculationService->getScheduledEndTime($activeAttendance);
            $canCheckOut = $serverNow->greaterThanOrEqualTo($scheduleEnd);
            $scheduledCheckOutTime = $scheduleEnd->format('H:i');
        }

        return view('mobile.attendance.scan', [
            'user' => $user,
            'employee' => $employee,
            'activePositions' => $activePositions,
            'defaultPosition' => $activeAttendance?->position ?? $employee->position,
            'officeLocation' => $officeLocation,
            'hasFaceEnrolled' => true,
            'activeAttendance' => $activeAttendance,
            'todayAttendance' => $activeAttendance,
            'isCheckedIn' => $isCheckedIn,
            'isCheckedOut' => $isCheckedOut,
            'attendanceMode' => $attendanceMode,
            'canCheckOut' => $canCheckOut,
            'scheduledCheckOutTime' => $scheduledCheckOutTime,
            'serverTimeWib' => $serverNow->format('H:i:s'),
            'serverDateWib' => $serverNow->translatedFormat('l, d F Y'),
        ]);
    }

    /**
     * Check if user is temporarily locked out due to repeated biometric failures (US-703).
     */
    protected function isBiometricLockedOut(int $userId): bool
    {
        return (int) Cache::get("biometric_fail_count_{$userId}", 0) >= 5;
    }

    /**
     * Record a biometric mismatch failure and enforce 15-minute lockout on >= 5 failures (US-703).
     */
    protected function recordBiometricFailure(int $userId, Request $request, int $employeeId): void
    {
        $key = "biometric_fail_count_{$userId}";
        $count = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $count, now()->addMinutes(15));

        if ($count >= 5) {
            AuditLog::create([
                'user_id' => $userId,
                'action' => 'BIOMETRIC_ANOMALY_LOCKOUT',
                'entity_type' => Employee::class,
                'entity_id' => $employeeId,
                'new_values' => [
                    'failure_count' => $count,
                    'cooldown_minutes' => 15,
                    'reason' => 'Anomali kegagalan verifikasi biometrik berulang kali (>= 5 kali). Akses dikunci sementara 15 menit.',
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }

    /**
     * Reset biometric failure count upon successful match.
     */
    protected function clearBiometricFailures(int $userId): void
    {
        Cache::forget("biometric_fail_count_{$userId}");
    }

    /**
     * Validate an active or passive liveness challenge response (FR-202 / US-302).
     */
    public function verifyLiveness(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($this->isBiometricLockedOut($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dikunci sementara selama 15 menit akibat anomali verifikasi biometrik berulang (>= 5 kali). Silakan gunakan form Swafoto Darurat (Fallback) atau hubungi Supervisor.',
                'error_code' => 'BIOMETRIC_LOCKOUT',
            ], 423);
        }

        $validated = $request->validate([
            'passed' => 'required|boolean',
            'challenge_type' => 'required|string|in:blink,head_turn,passive',
            'duration_ms' => 'required|numeric|min:0',
            'ear_value' => 'nullable|numeric',
            'yaw_angle' => 'nullable|numeric',
        ]);

        $result = $this->biometricService->verifyLiveness($validated);

        if (! $result['is_valid']) {
            $this->recordBiometricFailure($user->id, $request, $user->employee?->id ?? 0);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'LIVENESS_CHECK_FAILED',
                'entity_type' => Employee::class,
                'entity_id' => $user->employee?->id,
                'new_values' => [
                    'challenge_type' => $validated['challenge_type'],
                    'duration_ms' => $validated['duration_ms'],
                    'reason' => $result['message'],
                    'spoofing_alert' => true,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data' => $result,
            ], 422);
        }

        $this->clearBiometricFailures($user->id);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result,
        ]);
    }

    /**
     * Validate employee device GPS coordinates against active office geofence (FR-203, FR-204 / US-303).
     */
    public function verifyGeofence(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'is_mock' => 'nullable|boolean',
        ]);

        /** @var User $user */
        $user = Auth::user();

        $result = $this->geofenceService->validatePresenceLocation(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            null,
            isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
            (bool) ($validated['is_mock'] ?? false)
        );

        if (! $result['is_valid']) {
            $action = match ($result['status']) {
                'MOCK_LOCATION_DETECTED' => 'FAKE_GPS_DETECTED',
                'LOW_ACCURACY' => 'GPS_LOW_ACCURACY',
                default => 'GEOFENCE_CHECK_FAILED',
            };

            AuditLog::create([
                'user_id' => $user->id,
                'action' => $action,
                'entity_type' => Employee::class,
                'entity_id' => $user->employee?->id,
                'new_values' => [
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'accuracy' => $validated['accuracy'] ?? null,
                    'is_mock' => $validated['is_mock'] ?? false,
                    'distance_meters' => $result['distance_meters'],
                    'allowed_radius' => $result['allowed_radius'],
                    'status' => $result['status'],
                    'reason' => $result['message'],
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data' => $result,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result,
        ]);
    }

    /**
     * Process employee check-in with WIB server time enforcement, geofence, liveness, and late rules (FR-205, FR-206, FR-301 / US-304).
     */
    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'position_id' => 'required|exists:positions,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'is_mock' => 'nullable|boolean',
            'liveness_verified' => 'required|boolean',
            'face_probe_vector' => 'nullable|array',
            'face_probe_vector.*' => 'numeric',
            'photo' => 'required|string',
        ]);

        /** @var User $user */
        $user = Auth::user();

        /** @var Employee|null $employee */
        $employee = $user->employee()->with(['faceTemplate'])->first();
        if (! $employee || ! $employee->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Status akun pegawai Anda tidak aktif (masa kontrak berakhir). Presensi diblokir.',
            ], 403);
        }

        $nowWib = Carbon::now('Asia/Jakarta');
        if (MonthlyPeriodClosing::isPeriodLocked($nowWib->year, $nowWib->month)) {
            return response()->json([
                'success' => false,
                'message' => 'Periode absensi bulan ini telah dikunci (READ ONLY). Transaksi presensi tidak dapat diproses.',
            ], 422);
        }

        // Check for temporary biometric lockout (US-703)
        if ($this->isBiometricLockedOut($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dikunci sementara selama 15 menit akibat anomali verifikasi biometrik berulang kali (>= 5 kali). Silakan gunakan form Swafoto Darurat (Fallback) atau hubungi Supervisor.',
                'error_code' => 'BIOMETRIC_LOCKOUT',
            ], 423);
        }

        // 1. Verify Liveness Flag
        if (! $validated['liveness_verified']) {
            $this->recordBiometricFailure($user->id, $request, $employee->id);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'CHECKIN_REJECTED_LIVENESS',
                'entity_type' => Employee::class,
                'entity_id' => $employee->id,
                'new_values' => [
                    'reason' => 'Verifikasi liveness manusia hidup belum terpenuhi.',
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Pemeriksaan liveness anti-spoofing belum terpenuhi.',
            ], 422);
        }

        // 2. Validate Geofence GPS Coordinates
        $geofenceResult = $this->geofenceService->validatePresenceLocation(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            null,
            isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
            (bool) ($validated['is_mock'] ?? false)
        );

        if (! $geofenceResult['is_valid']) {
            $action = match ($geofenceResult['status']) {
                'MOCK_LOCATION_DETECTED' => 'FAKE_GPS_DETECTED',
                'LOW_ACCURACY' => 'GPS_LOW_ACCURACY',
                default => 'GEOFENCE_CHECK_FAILED',
            };

            AuditLog::create([
                'user_id' => $user->id,
                'action' => $action,
                'entity_type' => Employee::class,
                'entity_id' => $employee->id,
                'new_values' => [
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'accuracy' => $validated['accuracy'] ?? null,
                    'is_mock' => $validated['is_mock'] ?? false,
                    'distance_meters' => $geofenceResult['distance_meters'],
                    'allowed_radius' => $geofenceResult['allowed_radius'],
                    'status' => $geofenceResult['status'],
                    'reason' => $geofenceResult['message'],
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $geofenceResult['message'],
                'data' => $geofenceResult,
            ], 422);
        }

        $this->clearBiometricFailures($user->id);

        // 3. Server Timestamp Enforcement (WIB / Asia/Jakarta)
        $serverTime = now()->timezone('Asia/Jakarta');

        /** @var Position $position */
        $position = Position::findOrFail($validated['position_id']);

        // 5. Calculate Attendance Rules (Toleransi 15 Mnt / TL 1-3)
        $calc = $this->attendanceCalculationService->calculateCheckIn($position, $serverTime);

        // 6. Check for Duplicate Check-in
        $existing = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $calc['work_date'])
            ->where('shift_type', $calc['shift_type'])
            ->first();

        if ($existing && $existing->check_in_at !== null) {
            if ($existing->check_out_at !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah menyelesaikan presensi masuk dan pulang hari ini. Tidak dapat absen lagi hari ini.',
                    'data' => [
                        'attendance_id' => $existing->id,
                        'already_checked_in' => true,
                        'already_completed' => true,
                    ],
                ], 422);
            }

            $timeFormatted = $existing->check_in_at->timezone('Asia/Jakarta')->format('H:i:s');

            return response()->json([
                'success' => false,
                'message' => "Anda telah melakukan presensi masuk pada tanggal {$calc['work_date']} pukul {$timeFormatted} WIB.",
                'data' => [
                    'attendance_id' => $existing->id,
                    'already_checked_in' => true,
                ],
            ], 422);
        }

        // 7. Store Compressed Selfie Thumbnail (< 100 KB)
        $photoPath = $this->photoService->storeSelfieThumbnail(
            $validated['photo'],
            $employee->employee_code,
            'check_in'
        );

        // 8. Persist Attendance Record
        $officeLocation = OfficeLocation::where('is_active', true)->first();

        $attendanceData = [
            'position_id' => $position->id,
            'office_location_id' => $officeLocation?->id,
            'check_in_at' => $serverTime,
            'check_in_lat' => $validated['latitude'],
            'check_in_lng' => $validated['longitude'],
            'check_in_distance_meters' => $geofenceResult['distance_meters'],
            'check_in_photo_path' => $photoPath,
            'status' => $calc['status'],
            'late_minutes' => $calc['late_minutes'],
            'required_compensation_minutes' => $calc['required_compensation_minutes'],
            'tl_category' => $calc['tl_category'],
            'deduction_percentage' => $calc['deduction_percentage'],
            'notes' => $calc['notes'],
        ];

        if ($existing) {
            $existing->update($attendanceData);
            $attendance = $existing;
        } else {
            $attendanceData['employee_id'] = $employee->id;
            $attendanceData['date'] = $calc['work_date'];
            $attendanceData['shift_type'] = $calc['shift_type'];
            $attendance = Attendance::create($attendanceData);
        }

        // 9. Record Audit Log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'ATTENDANCE_CHECK_IN',
            'entity_type' => Attendance::class,
            'entity_id' => $attendance->id,
            'new_values' => [
                'employee_id' => $employee->id,
                'position_id' => $position->id,
                'date' => $calc['work_date'],
                'check_in_at' => $serverTime->toDateTimeString(),
                'status' => $calc['status'],
                'late_minutes' => $calc['late_minutes'],
                'tl_category' => $calc['tl_category'],
                'deduction_percentage' => $calc['deduction_percentage'],
                'required_compensation_minutes' => $calc['required_compensation_minutes'],
                'distance_meters' => $geofenceResult['distance_meters'],
                'photo_path' => $photoPath,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        /** @var FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');

        $scheduledEnd = $calc['schedule_end'];

        return response()->json([
            'success' => true,
            'message' => 'Presensi masuk berhasil dicatat!',
            'data' => [
                'attendance_id' => $attendance->id,
                'employee_name' => $employee->full_name,
                'position_name' => $position->name,
                'work_date' => $calc['work_date'],
                'check_in_time' => $serverTime->format('H:i:s').' WIB',
                'status' => $calc['status'],
                'status_label' => $calc['status_label'],
                'late_minutes' => $calc['late_minutes'],
                'tl_category' => $calc['tl_category'],
                'deduction_percentage' => $calc['deduction_percentage'],
                'required_compensation_minutes' => $calc['required_compensation_minutes'],
                'can_check_out' => $serverTime->greaterThanOrEqualTo($scheduledEnd),
                'scheduled_end_time' => $scheduledEnd->format('H:i'),
                'distance_meters' => $geofenceResult['distance_meters'],
                'photo_url' => $publicDisk->url($photoPath),
                'notes' => $calc['notes'],
            ],
        ]);
    }

    /**
     * Process employee check-out with tolerance compensation verification, early leave (PSW) detection, and overnight shift resolution (FR-207, FR-208 / US-305).
     */
    public function checkOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'is_mock' => 'nullable|boolean',
            'liveness_verified' => 'required|boolean',
            'face_probe_vector' => 'nullable|array',
            'face_probe_vector.*' => 'numeric',
            'photo' => 'required|string',
        ]);

        /** @var User $user */
        $user = Auth::user();

        /** @var Employee|null $employee */
        $employee = $user->employee()->with(['faceTemplate'])->first();
        if (! $employee || ! $employee->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Status akun pegawai Anda tidak aktif (masa kontrak berakhir). Presensi diblokir.',
            ], 403);
        }

        $nowWib = Carbon::now('Asia/Jakarta');
        if (MonthlyPeriodClosing::isPeriodLocked($nowWib->year, $nowWib->month)) {
            return response()->json([
                'success' => false,
                'message' => 'Periode absensi bulan ini telah dikunci (READ ONLY). Transaksi presensi tidak dapat diproses.',
            ], 422);
        }

        // Check for temporary biometric lockout (US-703)
        if ($this->isBiometricLockedOut($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dikunci sementara selama 15 menit akibat anomali verifikasi biometrik berulang kali (>= 5 kali). Silakan gunakan form Swafoto Darurat (Fallback) atau hubungi Supervisor.',
                'error_code' => 'BIOMETRIC_LOCKOUT',
            ], 423);
        }

        // 1. Verify Liveness Flag
        if (! $validated['liveness_verified']) {
            $this->recordBiometricFailure($user->id, $request, $employee->id);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'CHECKOUT_REJECTED_LIVENESS',
                'entity_type' => Employee::class,
                'entity_id' => $employee->id,
                'new_values' => [
                    'reason' => 'Verifikasi liveness manusia hidup belum terpenuhi saat presensi pulang.',
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Pemeriksaan liveness anti-spoofing belum terpenuhi.',
            ], 422);
        }

        // 2. Validate Geofence GPS Coordinates
        $geofenceResult = $this->geofenceService->validatePresenceLocation(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            null,
            isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
            (bool) ($validated['is_mock'] ?? false)
        );

        if (! $geofenceResult['is_valid']) {
            $action = match ($geofenceResult['status']) {
                'MOCK_LOCATION_DETECTED' => 'FAKE_GPS_DETECTED',
                'LOW_ACCURACY' => 'GPS_LOW_ACCURACY',
                default => 'GEOFENCE_CHECK_FAILED',
            };

            AuditLog::create([
                'user_id' => $user->id,
                'action' => $action,
                'entity_type' => Employee::class,
                'entity_id' => $employee->id,
                'new_values' => [
                    'action_type' => 'check_out',
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'distance_meters' => $geofenceResult['distance_meters'],
                    'status' => $geofenceResult['status'],
                    'reason' => $geofenceResult['message'],
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $geofenceResult['message'],
                'data' => $geofenceResult,
            ], 422);
        }

        $this->clearBiometricFailures($user->id);

        // 3. Server Timestamp Enforcement (WIB / Asia/Jakarta)
        $serverTime = now()->timezone('Asia/Jakarta');
        $todayWib = $serverTime->toDateString();

        // 5. Locate Active Check-in Record (accounting for overnight shift)
        $openOvernight = Attendance::query()
            ->with(['position'])
            ->where('employee_id', $employee->id)
            ->where('shift_type', Attendance::SHIFT_SECURITY_NIGHT)
            ->whereDate('date', Carbon::parse($todayWib)->subDay()->toDateString())
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->first();

        if ($openOvernight && $serverTime->hour < 12) {
            $attendance = $openOvernight;
        } else {
            $attendance = Attendance::query()
                ->with(['position'])
                ->where('employee_id', $employee->id)
                ->whereDate('date', $todayWib)
                ->first();
        }

        if (! $attendance || $attendance->check_in_at === null) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum melakukan presensi masuk hari ini. Silakan lakukan presensi masuk terlebih dahulu.',
            ], 422);
        }

        if ($attendance->check_out_at !== null) {
            $checkoutTimeFormatted = $attendance->check_out_at->timezone('Asia/Jakarta')->format('H:i:s');

            return response()->json([
                'success' => false,
                'message' => "Anda telah melakukan presensi pulang pada tanggal {$attendance->date->toDateString()} pukul {$checkoutTimeFormatted} WIB.",
                'data' => [
                    'attendance_id' => $attendance->id,
                    'already_checked_out' => true,
                ],
            ], 422);
        }

        // Enforce check-out schedule lock: cannot check out before scheduled end time (Milestone 2 - Poin 1)
        $scheduleEnd = $this->attendanceCalculationService->getScheduledEndTime($attendance);
        if ($serverTime->lessThan($scheduleEnd)) {
            return response()->json([
                'success' => false,
                'message' => "Belum waktunya pulang. Presensi pulang baru dapat dilakukan mulai pukul {$scheduleEnd->format('H:i')} WIB.",
                'data' => [
                    'before_schedule_end' => true,
                    'scheduled_end_time' => $scheduleEnd->format('H:i').' WIB',
                ],
            ], 422);
        }

        // 6. Calculate Checkout Rules (PSW, Toleransi Lunas/Kurang, Sesuai Jadwal)
        $calc = $this->attendanceCalculationService->calculateCheckOut($attendance, $serverTime);

        // 7. Store Compressed Selfie Thumbnail (< 100 KB)
        $photoPath = $this->photoService->storeSelfieThumbnail(
            $validated['photo'],
            $employee->employee_code,
            'check_out'
        );

        // 8. Update Attendance Record
        $attendance->update([
            'check_out_at' => $serverTime,
            'check_out_lat' => $validated['latitude'],
            'check_out_lng' => $validated['longitude'],
            'check_out_distance_meters' => $geofenceResult['distance_meters'],
            'check_out_photo_path' => $photoPath,
            'check_out_status' => $calc['check_out_status'],
            'early_leave_minutes' => $calc['early_leave_minutes'],
            'is_compensated' => $calc['is_compensated'],
            'notes' => $attendance->notes ? ($attendance->notes.' | '.$calc['notes']) : $calc['notes'],
        ]);

        // 9. Record Audit Log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'ATTENDANCE_CHECK_OUT',
            'entity_type' => Attendance::class,
            'entity_id' => $attendance->id,
            'new_values' => [
                'employee_id' => $employee->id,
                'position_id' => $attendance->position_id,
                'date' => $attendance->date->toDateString(),
                'check_out_at' => $serverTime->toDateTimeString(),
                'check_out_status' => $calc['check_out_status'],
                'early_leave_minutes' => $calc['early_leave_minutes'],
                'is_compensated' => $calc['is_compensated'],
                'distance_meters' => $geofenceResult['distance_meters'],
                'photo_path' => $photoPath,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        /** @var FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');

        return response()->json([
            'success' => true,
            'message' => 'Presensi pulang berhasil dicatat!',
            'data' => [
                'attendance_id' => $attendance->id,
                'employee_name' => $employee->full_name,
                'position_name' => $attendance->position->name,
                'work_date' => $attendance->date->toDateString(),
                'check_in_time' => $attendance->check_in_at->timezone('Asia/Jakarta')->format('H:i:s').' WIB',
                'check_out_time' => $serverTime->format('H:i:s').' WIB',
                'check_out_status' => $calc['check_out_status'],
                'status_label' => $calc['status_label'],
                'is_early_leave' => $calc['is_early_leave'],
                'early_leave_minutes' => $calc['early_leave_minutes'],
                'is_compensated' => $calc['is_compensated'],
                'required_compensation_minutes' => $attendance->required_compensation_minutes,
                'distance_meters' => $geofenceResult['distance_meters'],
                'photo_url' => $publicDisk->url($photoPath),
                'notes' => $calc['notes'],
            ],
        ]);
    }

    /**
     * Emergency fallback check-in when facial biometric verification fails 3 times consecutively (FR-209 / US-501).
     */
    public function fallbackCheckIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'position_id' => 'required|exists:positions,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'is_mock' => 'nullable|boolean',
            'photo' => 'required|string',
            'fallback_reason' => 'required|string|max:255',
        ]);

        /** @var User $user */
        $user = Auth::user();

        /** @var Employee|null $employee */
        $employee = $user->employee()->with(['faceTemplate'])->first();
        if (! $employee || ! $employee->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Status akun pegawai Anda tidak aktif (masa kontrak berakhir). Presensi diblokir.',
            ], 403);
        }

        $nowWib = Carbon::now('Asia/Jakarta');
        if (MonthlyPeriodClosing::isPeriodLocked($nowWib->year, $nowWib->month)) {
            return response()->json([
                'success' => false,
                'message' => 'Periode absensi bulan ini telah dikunci (READ ONLY). Transaksi presensi tidak dapat diproses.',
            ], 422);
        }

        // Validate Geofence GPS Coordinates (must still be in office)
        $geofenceResult = $this->geofenceService->validatePresenceLocation(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            null,
            isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
            (bool) ($validated['is_mock'] ?? false)
        );

        if (! $geofenceResult['is_valid']) {
            return response()->json([
                'success' => false,
                'message' => 'Presensi darurat ditolak: '.$geofenceResult['message'],
                'data' => $geofenceResult,
            ], 422);
        }

        $serverTime = now()->timezone('Asia/Jakarta');

        /** @var Position $position */
        $position = Position::findOrFail($validated['position_id']);

        $calc = $this->attendanceCalculationService->calculateCheckIn($position, $serverTime);

        // Check for duplicate check-in
        $existing = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $calc['work_date'])
            ->where('shift_type', $calc['shift_type'])
            ->first();

        if ($existing && $existing->check_in_at !== null) {
            if ($existing->check_out_at !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah menyelesaikan presensi masuk dan pulang hari ini. Tidak dapat absen lagi hari ini.',
                ], 422);
            }

            $timeFormatted = $existing->check_in_at->timezone('Asia/Jakarta')->format('H:i:s');

            return response()->json([
                'success' => false,
                'message' => "Anda sudah melakukan presensi masuk untuk jadwal hari ini pada pukul {$timeFormatted} WIB.",
            ], 422);
        }

        $photoPath = $this->photoService->storeSelfieThumbnail($validated['photo'], $employee->employee_code, 'checkin_fallback');

        $officeLocation = $geofenceResult['office_location'] ?? OfficeLocation::where('is_active', true)->first();

        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $calc['work_date'],
                'shift_type' => $calc['shift_type'],
            ],
            [
                'position_id' => $position->id,
                'office_location_id' => $officeLocation?->id,
                'check_in_at' => $serverTime,
                'check_in_lat' => $validated['latitude'],
                'check_in_lng' => $validated['longitude'],
                'check_in_distance_meters' => $geofenceResult['distance_meters'],
                'check_in_photo_path' => $photoPath,
                'status' => $calc['status'],
                'late_minutes' => $calc['late_minutes'],
                'required_compensation_minutes' => $calc['required_compensation_minutes'],
                'is_compensated' => false,
                'tl_category' => $calc['tl_category'],
                'deduction_percentage' => $calc['deduction_percentage'],
                'shift_type' => $calc['shift_type'],
                'is_fallback' => true,
                'fallback_reason' => $validated['fallback_reason'],
                'fallback_status' => Attendance::FALLBACK_PENDING,
                'notes' => 'Presensi Darurat (Fallback Biometrik): '.$validated['fallback_reason'],
            ]
        );

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'ATTENDANCE_FALLBACK_CHECKIN',
            'entity_type' => Attendance::class,
            'entity_id' => $attendance->id,
            'new_values' => [
                'employee_id' => $employee->id,
                'date' => $calc['work_date'],
                'check_in_at' => $serverTime->toDateTimeString(),
                'fallback_reason' => $validated['fallback_reason'],
                'fallback_status' => Attendance::FALLBACK_PENDING,
                'distance_meters' => $geofenceResult['distance_meters'],
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        /** @var FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');

        return response()->json([
            'success' => true,
            'is_fallback' => true,
            'fallback_status' => Attendance::FALLBACK_PENDING,
            'message' => 'Presensi darurat (fallback) berhasil dicatat dan sedang menunggu verifikasi Supervisor Lapangan.',
            'data' => [
                'attendance_id' => $attendance->id,
                'employee_name' => $employee->full_name,
                'position_name' => $position->name,
                'work_date' => $attendance->date->toDateString(),
                'check_in_time' => $serverTime->format('H:i:s').' WIB',
                'status' => $calc['status'],
                'status_label' => $calc['status_label'],
                'fallback_status' => Attendance::FALLBACK_PENDING,
                'fallback_reason' => $validated['fallback_reason'],
                'distance_meters' => $geofenceResult['distance_meters'],
                'photo_url' => $publicDisk->url($photoPath),
            ],
        ]);
    }
}
