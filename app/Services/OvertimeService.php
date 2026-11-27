<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\MonthlyPeriodClosing;
use App\Models\OvertimeMasterOrder;
use App\Models\OvertimeRequest;
use App\Models\OvertimeRequestItem;
use App\Models\OvertimeSetting;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OvertimeService
{
    public function __construct(
        protected AttendanceCalculationService $attendanceCalculationService,
        protected InAppNotificationService $notificationService
    ) {}

    /**
     * Calculate duration in minutes and decimal hours between HH:MM start and end times.
     *
     * @return array{duration_minutes: int, duration_hours: float}
     *
     * @throws ValidationException
     */
    public function calculateTimeDuration(string $startTime, string $endTime): array
    {
        if (! preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', trim($startTime), $startMatch)) {
            throw ValidationException::withMessages([
                'start_time' => 'Format jam mulai lembur harus HH:MM (contoh: 17:30).',
            ]);
        }

        if (! preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', trim($endTime), $endMatch)) {
            throw ValidationException::withMessages([
                'end_time' => 'Format jam selesai lembur harus HH:MM (contoh: 19:30).',
            ]);
        }

        $startMinutes = ((int) $startMatch[1] * 60) + (int) $startMatch[2];
        $endMinutes = ((int) $endMatch[1] * 60) + (int) $endMatch[2];

        if ($endMinutes <= $startMinutes) {
            throw ValidationException::withMessages([
                'end_time' => 'Jam selesai lembur harus lebih akhir dari jam mulai lembur.',
            ]);
        }

        $durationMinutes = $endMinutes - $startMinutes;

        return [
            'duration_minutes' => $durationMinutes,
            'duration_hours' => round($durationMinutes / 60, 2),
        ];
    }

    /**
     * Resolve whether a date range is workday, holiday, or mixed.
     */
    public function resolveDayType(CarbonInterface $startDate, CarbonInterface $endDate): string
    {
        $hasWorkday = false;
        $hasHoliday = false;
        $cursor = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->startOfDay();

        while ($cursor->lte($end)) {
            if (Holiday::isNonWorkingDay($cursor)) {
                $hasHoliday = true;
            } else {
                $hasWorkday = true;
            }
            $cursor->addDay();
        }

        if ($hasWorkday && $hasHoliday) {
            return OvertimeRequest::DAY_TYPE_MIXED;
        }

        return $hasHoliday ? OvertimeRequest::DAY_TYPE_HOLIDAY : OvertimeRequest::DAY_TYPE_WORKDAY;
    }

    /**
     * Create a new overtime request (SPTPL / WITH_LETTER or WITHOUT_LETTER).
     *
     * @param  array{
     *     request_type?: string,
     *     start_date: string,
     *     end_date?: ?string,
     *     letter_date?: ?string,
     *     signer_name?: ?string,
     *     signer_title?: ?string,
     *     signer_nip?: ?string,
     *     attachment_pdf_path?: ?string,
     *     imported_pdf_summary?: ?array<string, mixed>,
     *     replaces_overtime_request_id?: ?int,
     *     items: list<array{employee_id: int, start_time: string, end_time: string, description: string}>
     * }  $data
     *
     * @throws ValidationException
     */
    public function createRequest(User $proposer, array $data): OvertimeRequest
    {
        $settings = OvertimeSetting::current();
        $requestType = $data['request_type'] ?? OvertimeRequest::TYPE_WITH_LETTER;

        $startDate = Carbon::parse($data['start_date'], 'Asia/Jakarta')->startOfDay();
        $endDate = Carbon::parse(! empty($data['end_date']) ? $data['end_date'] : $data['start_date'], 'Asia/Jakarta')->startOfDay();
        $letterDate = Carbon::parse($data['letter_date'] ?? now('Asia/Jakarta')->toDateString(), 'Asia/Jakarta')->startOfDay();

        $normalizedItems = $this->validateDatesAndItems($requestType, $startDate, $endDate, $data['items'] ?? []);

        $masterOrder = OvertimeMasterOrder::query()
            ->where('period_year', $startDate->year)
            ->where('period_month', $startDate->month)
            ->first();

        return DB::transaction(function () use ($proposer, $settings, $requestType, $startDate, $endDate, $letterDate, $normalizedItems, $masterOrder, $data): OvertimeRequest {
            $draftPrefix = $requestType === OvertimeRequest::TYPE_WITH_LETTER ? 'DRF-SPTPL' : 'DRF-LMBR';
            $draftCode = sprintf('%s-%s-%s', $draftPrefix, now('Asia/Jakarta')->format('YmdHis'), strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)));

            $request = OvertimeRequest::create([
                'request_type' => $requestType,
                'overtime_master_order_id' => $masterOrder?->id,
                'letter_year' => $startDate->year,
                'draft_code' => $draftCode,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'letter_date' => $letterDate->toDateString(),
                'day_type' => $this->resolveDayType($startDate, $endDate),
                'signer_name' => ! empty($data['signer_name']) ? $data['signer_name'] : $settings->signer_name,
                'signer_title' => ! empty($data['signer_title']) ? $data['signer_title'] : $settings->signer_title,
                'signer_nip' => $data['signer_nip'] ?? $settings->signer_nip,
                'attachment_pdf_path' => $data['attachment_pdf_path'] ?? null,
                'imported_pdf_summary' => $data['imported_pdf_summary'] ?? null,
                'status' => OvertimeRequest::STATUS_DRAFT,
                'proposer_id' => $proposer->id,
                'replaces_overtime_request_id' => $data['replaces_overtime_request_id'] ?? null,
            ]);

            foreach ($normalizedItems as $item) {
                $request->items()->create($item);
            }

            $this->logAudit($proposer, 'OVERTIME_REQUEST_CREATED', $request, null, [
                'request_type' => $requestType,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'items_count' => count($normalizedItems),
            ]);

            return $request->load('items.employee');
        });
    }

    /**
     * Update an editable overtime request (DRAFT or RETURNED).
     *
     * @param  array{
     *     start_date: string,
     *     end_date?: ?string,
     *     letter_date?: ?string,
     *     signer_name?: ?string,
     *     signer_title?: ?string,
     *     signer_nip?: ?string,
     *     attachment_pdf_path?: ?string,
     *     imported_pdf_summary?: ?array<string, mixed>,
     *     items: list<array{employee_id: int, start_time: string, end_time: string, description: string}>
     * }  $data
     *
     * @throws ValidationException
     */
    public function updateRequest(OvertimeRequest $request, User $actor, array $data): OvertimeRequest
    {
        if (! $request->isEditable()) {
            throw ValidationException::withMessages([
                'status' => 'Surat atau pengajuan dengan status ini tidak dapat diubah.',
            ]);
        }

        $startDate = Carbon::parse($data['start_date'], 'Asia/Jakarta')->startOfDay();
        $endDate = Carbon::parse(! empty($data['end_date']) ? $data['end_date'] : $data['start_date'], 'Asia/Jakarta')->startOfDay();
        $letterDate = Carbon::parse($data['letter_date'] ?? $request->letter_date?->toDateString() ?? now('Asia/Jakarta')->toDateString(), 'Asia/Jakarta')->startOfDay();

        $normalizedItems = $this->validateDatesAndItems(
            $request->request_type,
            $startDate,
            $endDate,
            $data['items'] ?? [],
            $request->id
        );

        $masterOrder = OvertimeMasterOrder::query()
            ->where('period_year', $startDate->year)
            ->where('period_month', $startDate->month)
            ->first();

        return DB::transaction(function () use ($request, $actor, $startDate, $endDate, $letterDate, $normalizedItems, $masterOrder, $data): OvertimeRequest {
            $oldValues = [
                'start_date' => $request->start_date?->toDateString(),
                'end_date' => $request->end_date?->toDateString(),
                'items_count' => $request->items()->count(),
            ];

            $request->update([
                'overtime_master_order_id' => $masterOrder?->id,
                'letter_year' => $startDate->year,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'letter_date' => $letterDate->toDateString(),
                'day_type' => $this->resolveDayType($startDate, $endDate),
                'signer_name' => $data['signer_name'] ?? $request->signer_name,
                'signer_title' => $data['signer_title'] ?? $request->signer_title,
                'signer_nip' => $data['signer_nip'] ?? $request->signer_nip,
                'attachment_pdf_path' => $data['attachment_pdf_path'] ?? $request->attachment_pdf_path,
                'imported_pdf_summary' => $data['imported_pdf_summary'] ?? $request->imported_pdf_summary,
            ]);

            $request->items()->delete();
            foreach ($normalizedItems as $item) {
                $request->items()->create($item);
            }

            $this->logAudit($actor, 'OVERTIME_REQUEST_UPDATED', $request, $oldValues, [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'items_count' => count($normalizedItems),
            ]);

            return $request->refresh()->load('items.employee');
        });
    }

    /**
     * Submit a DRAFT or RETURNED overtime request for approval.
     * Returns non-blocking SPKL reminder if monthly SPKL is not yet issued.
     *
     * @return array{request: OvertimeRequest, spkl_warning: ?string}
     *
     * @throws ValidationException
     */
    public function submit(OvertimeRequest $request, User $actor): array
    {
        if (! $request->isEditable()) {
            throw ValidationException::withMessages([
                'status' => 'Pengajuan lembur ini tidak dalam status draf atau dikembalikan.',
            ]);
        }

        $itemsPayload = $request->items->map(fn (OvertimeRequestItem $item): array => [
            'employee_id' => $item->employee_id,
            'start_time' => $item->start_time,
            'end_time' => $item->end_time,
            'description' => $item->description,
        ])->all();

        $this->validateDatesAndItems(
            $request->request_type,
            Carbon::parse($request->start_date, 'Asia/Jakarta'),
            Carbon::parse($request->end_date, 'Asia/Jakarta'),
            $itemsPayload,
            $request->id
        );

        $oldStatus = $request->status;
        $request->update([
            'status' => OvertimeRequest::STATUS_SUBMITTED,
            'submitted_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $spklWarning = $this->checkSpklReminder($request);

        $this->logAudit($actor, 'OVERTIME_REQUEST_SUBMITTED', $request, ['status' => $oldStatus], [
            'status' => OvertimeRequest::STATUS_SUBMITTED,
            'spkl_warning' => $spklWarning,
        ]);

        $this->notificationService->notifyOvertimeStatusChanged($request, 'SUBMITTED', $spklWarning);

        return [
            'request' => $request->refresh()->load('items.employee'),
            'spkl_warning' => $spklWarning,
        ];
    }

    /**
     * Approve an overtime request and lock official SPTPL number for WITH_LETTER.
     * Does NOT block when monthly SPKL is missing; attaches reminder notice instead.
     *
     * @return array{request: OvertimeRequest, spkl_warning: ?string}
     *
     * @throws AuthorizationException|ValidationException
     */
    public function approve(
        OvertimeRequest $request,
        User $approver,
        ?string $notes = null,
        ?string $signedPdfPath = null
    ): array {
        if (! $approver->hasRole('atasan') && ! $approver->hasRole('admin')) {
            throw new AuthorizationException('Hanya Pejabat Penyetuju (Atasan) atau Admin yang berwenang menyetujui lembur.');
        }

        if ($request->status !== OvertimeRequest::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'status' => 'Hanya pengajuan berstatus Diajukan yang dapat disetujui.',
            ]);
        }

        $spklWarning = $this->checkSpklReminder($request);
        $settings = OvertimeSetting::current();

        $approvedRequest = DB::transaction(function () use ($request, $approver, $notes, $signedPdfPath, $settings): OvertimeRequest {
            $oldStatus = $request->status;
            $year = (int) ($request->letter_year ?: $request->start_date->year);

            $sequenceNumber = $request->sequence_number;
            $letterNumber = $request->letter_number;

            if ($request->request_type === OvertimeRequest::TYPE_WITH_LETTER && $letterNumber === null) {
                $maxSeq = (int) (OvertimeRequest::query()
                    ->where('request_type', OvertimeRequest::TYPE_WITH_LETTER)
                    ->where('letter_year', $year)
                    ->whereNotNull('sequence_number')
                    ->max('sequence_number') ?? 0);

                $sequenceNumber = max($maxSeq + 1, (int) $settings->sptpl_sequence_start);
                $letterNumber = sprintf('SPTPL-%d/%s/%d', $sequenceNumber, trim($settings->unit_code), $year);
            }

            $masterOrder = OvertimeMasterOrder::query()
                ->where('period_year', $request->start_date->year)
                ->where('period_month', $request->start_date->month)
                ->first();

            $request->update([
                'status' => OvertimeRequest::STATUS_APPROVED,
                'sequence_number' => $sequenceNumber,
                'letter_number' => $letterNumber,
                'overtime_master_order_id' => $masterOrder?->id ?? $request->overtime_master_order_id,
                'approver_id' => $approver->id,
                'approved_at' => Carbon::now('Asia/Jakarta'),
                'approver_notes' => $notes,
                'signed_pdf_path' => $signedPdfPath ?: $request->signed_pdf_path,
            ]);

            $this->logAudit($approver, 'OVERTIME_REQUEST_APPROVED', $request, ['status' => $oldStatus], [
                'status' => OvertimeRequest::STATUS_APPROVED,
                'letter_number' => $letterNumber,
                'notes' => $notes,
            ]);

            return $request->refresh()->load('items.employee');
        });

        $this->notificationService->notifyOvertimeStatusChanged($approvedRequest, 'APPROVED', $spklWarning);

        return [
            'request' => $approvedRequest,
            'spkl_warning' => $spklWarning,
        ];
    }

    /**
     * Return a submitted request to proposer with revision notes.
     *
     * @throws AuthorizationException|ValidationException
     */
    public function returnForRevision(OvertimeRequest $request, User $approver, string $notes): OvertimeRequest
    {
        if (! $approver->hasRole('atasan') && ! $approver->hasRole('admin')) {
            throw new AuthorizationException('Anda tidak berwenang mengembalikan pengajuan lembur.');
        }

        if ($request->status !== OvertimeRequest::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'status' => 'Hanya pengajuan berstatus Diajukan yang dapat dikembalikan.',
            ]);
        }

        $oldStatus = $request->status;
        $request->update([
            'status' => OvertimeRequest::STATUS_RETURNED,
            'approver_id' => $approver->id,
            'returned_at' => Carbon::now('Asia/Jakarta'),
            'approver_notes' => trim($notes),
        ]);

        $this->logAudit($approver, 'OVERTIME_REQUEST_RETURNED', $request, ['status' => $oldStatus], [
            'status' => OvertimeRequest::STATUS_RETURNED,
            'notes' => $notes,
        ]);

        $this->notificationService->notifyOvertimeStatusChanged($request, 'RETURNED');

        return $request->refresh()->load('items.employee');
    }

    /**
     * Reject a submitted request with reason.
     *
     * @throws AuthorizationException|ValidationException
     */
    public function reject(OvertimeRequest $request, User $approver, string $reason): OvertimeRequest
    {
        if (! $approver->hasRole('atasan') && ! $approver->hasRole('admin')) {
            throw new AuthorizationException('Anda tidak berwenang menolak pengajuan lembur.');
        }

        if ($request->status !== OvertimeRequest::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'status' => 'Hanya pengajuan berstatus Diajukan yang dapat ditolak.',
            ]);
        }

        $oldStatus = $request->status;
        $request->update([
            'status' => OvertimeRequest::STATUS_REJECTED,
            'approver_id' => $approver->id,
            'rejected_at' => Carbon::now('Asia/Jakarta'),
            'approver_notes' => trim($reason),
        ]);

        $this->logAudit($approver, 'OVERTIME_REQUEST_REJECTED', $request, ['status' => $oldStatus], [
            'status' => OvertimeRequest::STATUS_REJECTED,
            'reason' => $reason,
        ]);

        $this->notificationService->notifyOvertimeStatusChanged($request, 'REJECTED');

        return $request->refresh()->load('items.employee');
    }

    /**
     * Cancel an approved/draft/submitted overtime request with mandatory reason.
     * Preserves letter_number so sequences are never reused.
     *
     * @throws ValidationException
     */
    public function cancel(OvertimeRequest $request, User $actor, string $reason): OvertimeRequest
    {
        if (in_array($request->status, [OvertimeRequest::STATUS_CANCELLED, OvertimeRequest::STATUS_REJECTED], true)) {
            throw ValidationException::withMessages([
                'status' => 'Pengajuan sudah berstatus batal atau ditolak.',
            ]);
        }

        $cleanReason = trim($reason);
        if (mb_strlen($cleanReason) < 5) {
            throw ValidationException::withMessages([
                'cancellation_reason' => 'Alasan pembatalan wajib diisi minimal 5 karakter.',
            ]);
        }

        $oldStatus = $request->status;
        $request->update([
            'status' => OvertimeRequest::STATUS_CANCELLED,
            'cancelled_at' => Carbon::now('Asia/Jakarta'),
            'cancellation_reason' => $cleanReason,
        ]);

        $this->logAudit($actor, 'OVERTIME_REQUEST_CANCELLED', $request, ['status' => $oldStatus], [
            'status' => OvertimeRequest::STATUS_CANCELLED,
            'cancellation_reason' => $cleanReason,
        ]);

        $this->notificationService->notifyOvertimeStatusChanged($request, 'CANCELLED');

        return $request->refresh()->load('items.employee');
    }

    /**
     * Create or fetch monthly umbrella SPKL draft (early-month order).
     */
    public function getOrCreateMonthlyMasterOrder(int $year, int $month, User $creator): OvertimeMasterOrder
    {
        $existing = OvertimeMasterOrder::query()
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->first();

        if ($existing) {
            return $existing;
        }

        $settings = OvertimeSetting::current();
        $maxSeq = (int) (OvertimeMasterOrder::query()
            ->where('period_year', $year)
            ->max('sequence_number') ?? 0);

        $sequenceNumber = max($maxSeq + 1, (int) $settings->spkl_sequence_start);
        $letterNumber = sprintf('SPKL-%d/%s/%d', $sequenceNumber, trim($settings->unit_code), $year);

        $monthName = MonthlyPeriodClosing::MONTH_NAMES[$month] ?? (string) $month;
        $periodLabel = strtolower("{$monthName} tahun {$year}");
        $templateBody = strtr(
            $settings->spkl_template_body ?: OvertimeSetting::defaultSpklTemplate(),
            [
                '{period_label}' => $periodLabel,
                '{satker_name}' => $settings->satker_name,
                '{year}' => (string) $year,
            ]
        );

        $order = OvertimeMasterOrder::create([
            'period_year' => $year,
            'period_month' => $month,
            'sequence_number' => $sequenceNumber,
            'letter_number' => $letterNumber,
            'letter_date' => Carbon::createFromDate($year, $month, 1, 'Asia/Jakarta')->toDateString(),
            'signer_name' => $settings->signer_name,
            'signer_title' => $settings->signer_title,
            'signer_nip' => $settings->signer_nip,
            'template_body' => $templateBody,
            'status' => OvertimeMasterOrder::STATUS_DRAFT,
            'created_by' => $creator->id,
        ]);

        // Link existing month SPTPLs cleanly to this SPKL
        OvertimeRequest::query()
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->whereNull('overtime_master_order_id')
            ->update(['overtime_master_order_id' => $order->id]);

        $this->logAudit($creator, 'SPKL_DRAFT_CREATED', $order, null, [
            'letter_number' => $letterNumber,
            'period' => "{$year}-{$month}",
        ]);

        return $order;
    }

    /**
     * Issue / sign a monthly SPKL order.
     *
     * @throws AuthorizationException
     */
    public function issueMasterOrder(OvertimeMasterOrder $order, User $approver, ?string $signedPdfPath = null): OvertimeMasterOrder
    {
        if (! $approver->hasRole('atasan') && ! $approver->hasRole('admin')) {
            throw new AuthorizationException('Hanya Atasan atau Admin yang dapat mengesahkan SPKL bulanan.');
        }

        $oldStatus = $order->status;
        $order->update([
            'status' => OvertimeMasterOrder::STATUS_ISSUED,
            'approved_by' => $approver->id,
            'approved_at' => Carbon::now('Asia/Jakarta'),
            'signed_pdf_path' => $signedPdfPath ?: $order->signed_pdf_path,
        ]);

        OvertimeRequest::query()
            ->whereYear('start_date', $order->period_year)
            ->whereMonth('start_date', $order->period_month)
            ->whereNull('overtime_master_order_id')
            ->update(['overtime_master_order_id' => $order->id]);

        $this->logAudit($approver, 'SPKL_ISSUED', $order, ['status' => $oldStatus], [
            'status' => OvertimeMasterOrder::STATUS_ISSUED,
            'letter_number' => $order->letter_number,
        ]);

        return $order->refresh();
    }

    /**
     * Check whether SPKL for the request month is missing or not yet issued (non-blocking reminder).
     */
    public function checkSpklReminder(OvertimeRequest $request): ?string
    {
        if ($request->request_type !== OvertimeRequest::TYPE_WITH_LETTER || ! $request->start_date) {
            return null;
        }

        $year = (int) $request->start_date->year;
        $month = (int) $request->start_date->month;
        $monthName = MonthlyPeriodClosing::MONTH_NAMES[$month] ?? (string) $month;

        $masterOrder = OvertimeMasterOrder::query()
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->first();

        if (! $masterOrder) {
            return "SPKL induk bulan {$monthName} {$year} belum dibuat.";
        }

        if (! $masterOrder->isIssued()) {
            return "SPKL induk {$masterOrder->letter_number} belum berstatus Terbit (saat ini: {$masterOrder->getStatusLabel()}).";
        }

        return null;
    }

    /**
     * Compute monthly overtime recapitulation per employee strictly in time/hours (no Rupiah/tariff).
     *
     * @return array{
     *     year: int,
     *     month: int,
     *     month_name: string,
     *     days_in_month: int,
     *     holidays: array<int, string>,
     *     master_order: ?OvertimeMasterOrder,
     *     items: array<int, array{
     *         employee_id: int,
     *         employee_code: string,
     *         name: string,
     *         position_name: string,
     *         hours_with_letter: float,
     *         hours_without_letter: float,
     *         total_hours: float,
     *         workday_hours: float,
     *         holiday_hours: float,
     *         overtime_days_count: int,
     *         has_overtime_letter: bool,
     *         letter_numbers: list<string>,
     *         overtime_status_label: string,
     *         days: array<int, array{
     *             hours_with_letter: float,
     *             hours_without_letter: float,
     *             total_hours: float,
     *             status: string,
     *             letters: list<string>,
     *             descriptions: list<string>,
     *             attendance_check_in: ?string,
     *             attendance_check_out: ?string
     *         }>
     *     }>,
     *     totals: array{
     *         total_employees_with_overtime: int,
     *         total_employees_with_letter: int,
     *         total_hours_with_letter: float,
     *         total_hours_without_letter: float,
     *         total_hours: float
     *     }
     * }
     */
    public function getMonthlyOvertimeRecap(
        int $year,
        int $month,
        ?int $employeeId = null,
        ?Collection $preloadedEmployees = null,
        ?Collection $preloadedAttendances = null,
        bool $includeMasterAndHolidays = true
    ): array {
        $startOfMonth = Carbon::createFromDate($year, $month, 1, 'Asia/Jakarta')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;
        $monthName = MonthlyPeriodClosing::MONTH_NAMES[$month] ?? (string) $month;

        $holidaysMap = [];
        if ($includeMasterAndHolidays) {
            $holidayRecords = Holiday::query()
                ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                ->get();

            foreach ($holidayRecords as $hol) {
                if ($hol->date) {
                    $holidaysMap[(int) $hol->date->day] = $hol->name;
                }
            }
        }
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $cursor = $startOfMonth->copy()->day($d);
            if ($cursor->isWeekend() && ! isset($holidaysMap[$d])) {
                $holidaysMap[$d] = 'Akhir Pekan';
            }
        }

        if ($preloadedEmployees !== null) {
            /** @var Collection<int, Employee> $employees */
            $employees = $preloadedEmployees;
        } else {
            $employeesQuery = Employee::with('position')
                ->where('is_active', true)
                ->orderBy('id', 'asc');
            if ($employeeId !== null) {
                $employeesQuery->where('id', $employeeId);
            }
            /** @var Collection<int, Employee> $employees */
            $employees = $employeesQuery->get();
        }

        $approvedRequests = OvertimeRequest::with('items')
            ->where('status', OvertimeRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $endOfMonth->toDateString())
            ->whereDate('end_date', '>=', $startOfMonth->toDateString())
            ->get();

        if ($preloadedAttendances !== null) {
            $attendancesByEmployee = $preloadedAttendances;
        } else {
            $attendancesByEmployee = Attendance::with('position')
                ->whereIn('employee_id', $employees->pluck('id')->all())
                ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                ->whereNotNull('check_in_at')
                ->whereNotNull('check_out_at')
                ->get()
                ->groupBy('employee_id');
        }

        $items = [];
        $totalEmployeesWithOvertime = 0;
        $totalEmployeesWithLetter = 0;
        $grandHoursWithLetter = 0.0;
        $grandHoursWithoutLetter = 0.0;

        foreach ($employees as $emp) {
            $days = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $days[$d] = [
                    'hours_with_letter' => 0.0,
                    'hours_without_letter' => 0.0,
                    'total_hours' => 0.0,
                    'status' => 'NONE',
                    'letters' => [],
                    'descriptions' => [],
                    'attendance_check_in' => null,
                    'attendance_check_out' => null,
                ];
            }

            // 1. Expand approved overtime requests (WITH_LETTER and approved WITHOUT_LETTER)
            foreach ($approvedRequests as $req) {
                /** @var OvertimeRequestItem|null $empItem */
                $empItem = $req->items->firstWhere('employee_id', $emp->id);
                if (! $empItem) {
                    continue;
                }

                $cursor = Carbon::parse($req->start_date, 'Asia/Jakarta')->startOfDay();
                $reqEnd = Carbon::parse($req->end_date, 'Asia/Jakarta')->startOfDay();
                if ($cursor->lt($startOfMonth)) {
                    $cursor = $startOfMonth->copy();
                }
                if ($reqEnd->gt($endOfMonth)) {
                    $reqEnd = $endOfMonth->copy();
                }

                $hours = (float) $empItem->duration_hours;

                while ($cursor->lte($reqEnd)) {
                    $dayNum = (int) $cursor->day;
                    if ($req->request_type === OvertimeRequest::TYPE_WITH_LETTER) {
                        $days[$dayNum]['hours_with_letter'] = round($days[$dayNum]['hours_with_letter'] + $hours, 2);
                        if ($req->letter_number) {
                            $days[$dayNum]['letters'][] = $req->letter_number;
                        }
                    } else {
                        $days[$dayNum]['hours_without_letter'] = round($days[$dayNum]['hours_without_letter'] + $hours, 2);
                    }
                    $days[$dayNum]['descriptions'][] = "{$empItem->start_time}-{$empItem->end_time}: {$empItem->description}";
                    $cursor->addDay();
                }
            }

            // 2. Compare check-out time against Position schedule (Jenis B automatic overtime indicator)
            /** @var Collection<int, Attendance> $empAttendances */
            $empAttendances = $attendancesByEmployee->get($emp->id, collect());
            foreach ($empAttendances as $att) {
                if (! $att->relationLoaded('position') && $emp->position) {
                    $att->setRelation('position', $emp->position);
                }

                if (! $att->date || ! $att->check_out_at || ! $att->position) {
                    continue;
                }

                $dayNum = (int) $att->date->day;
                $checkInWib = $att->check_in_at->timezone('Asia/Jakarta');
                $checkOutWib = $att->check_out_at->timezone('Asia/Jakarta');
                $days[$dayNum]['attendance_check_in'] = $checkInWib->format('H:i');
                $days[$dayNum]['attendance_check_out'] = $checkOutWib->format('H:i');

                $scheduleEnd = $this->attendanceCalculationService->getScheduledEndTime($att);
                if ($checkOutWib->greaterThan($scheduleEnd)) {
                    $excessMinutes = (int) $scheduleEnd->diffInMinutes($checkOutWib, false);
                    // Count auto-overtime when >= 30 minutes beyond normal position schedule and no manual WITHOUT_LETTER already logged
                    if ($excessMinutes >= 30 && $days[$dayNum]['hours_with_letter'] <= 0 && $days[$dayNum]['hours_without_letter'] <= 0) {
                        $days[$dayNum]['hours_without_letter'] = round($excessMinutes / 60, 2);
                        $days[$dayNum]['descriptions'][] = "Absen pulang {$checkOutWib->format('H:i')} melebihi jadwal {$scheduleEnd->format('H:i')} WIB";
                    }
                }
            }

            // Summarize employee totals
            $empWithLetter = 0.0;
            $empWithoutLetter = 0.0;
            $workdayHours = 0.0;
            $holidayHours = 0.0;
            $overtimeDaysCount = 0;
            $letterSet = [];

            foreach ($days as $dayNum => &$cell) {
                $cell['letters'] = array_values(array_unique($cell['letters']));
                $cell['total_hours'] = round($cell['hours_with_letter'] + $cell['hours_without_letter'], 2);

                if ($cell['hours_with_letter'] > 0 && $cell['hours_without_letter'] > 0) {
                    $cell['status'] = 'PARTIAL';
                } elseif ($cell['hours_with_letter'] > 0) {
                    $cell['status'] = 'WITH_LETTER';
                } elseif ($cell['hours_without_letter'] > 0) {
                    $cell['status'] = 'WITHOUT_LETTER';
                }

                if ($cell['total_hours'] > 0) {
                    $overtimeDaysCount++;
                    if (isset($holidaysMap[$dayNum])) {
                        $holidayHours = round($holidayHours + $cell['total_hours'], 2);
                    } else {
                        $workdayHours = round($workdayHours + $cell['total_hours'], 2);
                    }
                }

                $empWithLetter = round($empWithLetter + $cell['hours_with_letter'], 2);
                $empWithoutLetter = round($empWithoutLetter + $cell['hours_without_letter'], 2);
                foreach ($cell['letters'] as $ln) {
                    $letterSet[$ln] = true;
                }
            }
            unset($cell);

            $empTotal = round($empWithLetter + $empWithoutLetter, 2);
            $letterNumbers = array_keys($letterSet);
            $hasLetter = count($letterNumbers) > 0;

            if ($empTotal > 0) {
                $totalEmployeesWithOvertime++;
            }
            if ($hasLetter) {
                $totalEmployeesWithLetter++;
            }

            $grandHoursWithLetter = round($grandHoursWithLetter + $empWithLetter, 2);
            $grandHoursWithoutLetter = round($grandHoursWithoutLetter + $empWithoutLetter, 2);

            $statusLabel = match (true) {
                $hasLetter && $empWithoutLetter > 0 => 'Ada Surat & Tanpa Surat',
                $hasLetter => 'Ada Surat Tugas ('.implode(', ', $letterNumbers).')',
                $empWithoutLetter > 0 => 'Tanpa Surat',
                default => '-',
            };

            $items[$emp->id] = [
                'employee_id' => $emp->id,
                'employee_code' => $emp->employee_code,
                'name' => $emp->full_name,
                'position_name' => $emp->position?->name ?? 'PPNPN',
                'hours_with_letter' => $empWithLetter,
                'hours_without_letter' => $empWithoutLetter,
                'total_hours' => $empTotal,
                'workday_hours' => $workdayHours,
                'holiday_hours' => $holidayHours,
                'overtime_days_count' => $overtimeDaysCount,
                'has_overtime_letter' => $hasLetter,
                'letter_numbers' => $letterNumbers,
                'overtime_status_label' => $statusLabel,
                'days' => $days,
            ];
        }

        $masterOrder = $includeMasterAndHolidays
            ? OvertimeMasterOrder::query()
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->first()
            : null;

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $monthName,
            'days_in_month' => $daysInMonth,
            'holidays' => $holidaysMap,
            'master_order' => $masterOrder,
            'items' => $items,
            'totals' => [
                'total_employees_with_overtime' => $totalEmployeesWithOvertime,
                'total_employees_with_letter' => $totalEmployeesWithLetter,
                'total_hours_with_letter' => $grandHoursWithLetter,
                'total_hours_without_letter' => $grandHoursWithoutLetter,
                'total_hours' => round($grandHoursWithLetter + $grandHoursWithoutLetter, 2),
            ],
        ];
    }

    /**
     * Validate overtime dates, time rules, locked months, and cross-request employee overlaps.
     *
     * @param  list<array{employee_id: int, start_time: string, end_time: string, description: string}>  $rawItems
     * @return list<array{employee_id: int, start_time: string, end_time: string, duration_minutes: int, duration_hours: float, description: string}>
     *
     * @throws ValidationException
     */
    private function validateDatesAndItems(
        string $requestType,
        Carbon $startDate,
        Carbon $endDate,
        array $rawItems,
        ?int $ignoreRequestId = null
    ): array {
        $nowWib = Carbon::now('Asia/Jakarta');
        $today = $nowWib->copy()->startOfDay();

        if ($endDate->lt($startDate)) {
            throw ValidationException::withMessages([
                'end_date' => 'Tanggal selesai lembur tidak boleh sebelum tanggal mulai.',
            ]);
        }

        // #5: Overtime cannot be submitted after the overtime date has already passed
        if ($startDate->lt($today)) {
            throw ValidationException::withMessages([
                'start_date' => 'Pengajuan lembur tidak dapat dibuat untuk tanggal yang sudah berlalu.',
            ]);
        }

        if (MonthlyPeriodClosing::isPeriodLocked($startDate->year, $startDate->month)
            || MonthlyPeriodClosing::isPeriodLocked($endDate->year, $endDate->month)) {
            throw ValidationException::withMessages([
                'start_date' => 'Periode tanggal lembur berada pada bulan yang sudah dikunci (READ ONLY).',
            ]);
        }

        if ($rawItems === []) {
            throw ValidationException::withMessages([
                'items' => 'Minimal satu pegawai wajib ditambahkan ke dalam pengajuan lembur.',
            ]);
        }

        $normalized = [];
        $seenEmployeeIds = [];

        foreach ($rawItems as $index => $row) {
            $employeeId = (int) ($row['employee_id'] ?? 0);
            $startTime = trim((string) ($row['start_time'] ?? ''));
            $endTime = trim((string) ($row['end_time'] ?? ''));
            $description = trim((string) ($row['description'] ?? ''));

            if ($employeeId <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.employee_id" => 'Pegawai wajib dipilih dari master pegawai.',
                ]);
            }

            if (isset($seenEmployeeIds[$employeeId])) {
                throw ValidationException::withMessages([
                    'items' => 'Pegawai yang sama tidak boleh dicantumkan dua kali dalam satu pengajuan lembur.',
                ]);
            }
            $seenEmployeeIds[$employeeId] = true;

            if ($description === '') {
                throw ValidationException::withMessages([
                    "items.{$index}.description" => 'Uraian kegiatan lembur wajib diisi.',
                ]);
            }

            $duration = $this->calculateTimeDuration($startTime, $endTime);

            // #1: WITHOUT_LETTER must be submitted at latest on day H BEFORE execution starts
            if ($requestType === OvertimeRequest::TYPE_WITHOUT_LETTER && $startDate->isSameDay($today)) {
                $scheduledStart = $today->copy()->setTimeFromTimeString($startTime.':00');
                if ($scheduledStart->lessThanOrEqualTo($nowWib)) {
                    throw ValidationException::withMessages([
                        'start_time' => 'Pengajuan lembur tanpa surat maksimal diajukan pada hari H sebelum jam mulai dilaksanakan.',
                    ]);
                }
            }

            $normalized[] = [
                'employee_id' => $employeeId,
                'start_time' => sprintf('%02d:%02d', ...array_map('intval', explode(':', $startTime))),
                'end_time' => sprintf('%02d:%02d', ...array_map('intval', explode(':', $endTime))),
                'duration_minutes' => $duration['duration_minutes'],
                'duration_hours' => $duration['duration_hours'],
                'description' => $description,
            ];
        }

        // #4: Block same employee appearing in another active overtime request on overlapping dates
        $conflictingItems = OvertimeRequestItem::with(['overtimeRequest', 'employee'])
            ->whereIn('employee_id', array_keys($seenEmployeeIds))
            ->whereHas('overtimeRequest', function ($query) use ($startDate, $endDate, $ignoreRequestId): void {
                $query->whereIn('status', [
                    OvertimeRequest::STATUS_DRAFT,
                    OvertimeRequest::STATUS_SUBMITTED,
                    OvertimeRequest::STATUS_RETURNED,
                    OvertimeRequest::STATUS_APPROVED,
                ])
                    ->whereDate('start_date', '<=', $endDate->toDateString())
                    ->whereDate('end_date', '>=', $startDate->toDateString());

                if ($ignoreRequestId !== null) {
                    $query->where('id', '!=', $ignoreRequestId);
                }
            })
            ->get();

        if ($conflictingItems->isNotEmpty()) {
            $firstConflict = $conflictingItems->first();
            $empName = $firstConflict->employee?->full_name ?? 'Pegawai';
            $conflictCode = $firstConflict->overtimeRequest?->getDisplayCode() ?? 'Pengajuan lain';

            throw ValidationException::withMessages([
                'items' => "Pegawai {$empName} sudah terdaftar pada pengajuan lembur lain ({$conflictCode}) di tanggal yang sama.",
            ]);
        }

        return $normalized;
    }

    /**
     * Persist immutable audit trail record for overtime actions.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function logAudit(
        User $actor,
        string $action,
        OvertimeRequest|OvertimeMasterOrder $entity,
        ?array $oldValues,
        ?array $newValues
    ): void {
        AuditLog::create([
            'user_id' => $actor->id,
            'action' => $action,
            'entity_type' => $entity::class,
            'entity_id' => $entity->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => Carbon::now('Asia/Jakarta'),
        ]);
    }
}
