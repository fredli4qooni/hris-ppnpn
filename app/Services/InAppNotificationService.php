<?php

namespace App\Services;

use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\EmployeeViolation;
use App\Models\LateException;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Notifications\AttendanceCorrectionFinalDecisionNotification;
use App\Notifications\AttendanceCorrectionSubmittedNotification;
use App\Notifications\ContractExpiringAlertNotification;
use App\Notifications\LateExceptionFinalDecisionNotification;
use App\Notifications\LateExceptionSubmittedNotification;
use App\Notifications\LeaveRequestApprovedTier1Notification;
use App\Notifications\LeaveRequestFinalDecisionNotification;
use App\Notifications\LeaveRequestSubmittedNotification;
use App\Notifications\OvertimeStatusChangedNotification;
use App\Notifications\ViolationRecordedNotification;
use Illuminate\Support\Facades\Notification;

class InAppNotificationService
{
    /**
     * Dispatch notification when an employee submits a new leave request.
     */
    public function notifyLeaveRequestSubmitted(LeaveRequest $leaveRequest): void
    {
        $supervisor = $leaveRequest->employee?->supervisor;
        if ($supervisor) {
            $supervisor->notify(new LeaveRequestSubmittedNotification($leaveRequest));
        }
    }

    /**
     * Dispatch notification when a supervisor approves tier 1 of a leave request.
     */
    public function notifyLeaveRequestTier1Approved(LeaveRequest $leaveRequest): void
    {
        $atasan = $leaveRequest->employee?->atasan ?? User::role('atasan')->first();
        if ($atasan) {
            $atasan->notify(new LeaveRequestApprovedTier1Notification($leaveRequest));
        }
    }

    /**
     * Dispatch notification when the final decision on a leave request is reached.
     */
    public function notifyLeaveRequestFinalDecision(LeaveRequest $leaveRequest): void
    {
        $user = $leaveRequest->employee?->user;
        if ($user) {
            $user->notify(new LeaveRequestFinalDecisionNotification($leaveRequest));
        }
    }

    /**
     * Dispatch notification to an employee when an indisipliner violation is recorded.
     */
    public function notifyViolationRecorded(EmployeeViolation $violation): void
    {
        $user = $violation->employee?->user;
        if ($user) {
            $user->notify(new ViolationRecordedNotification($violation));
        }
    }

    /**
     * Dispatch contract expiration alert to employee and administrators.
     */
    public function notifyContractExpiring(Employee $employee, int $daysRemaining): void
    {
        $user = $employee->user;
        if ($user) {
            $user->notify(new ContractExpiringAlertNotification($employee, $daysRemaining));
        }

        $admins = User::role('admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new ContractExpiringAlertNotification($employee, $daysRemaining));
        }
    }

    /**
     * Dispatch notification when an employee submits an attendance correction.
     */
    public function notifyCorrectionSubmitted(AttendanceCorrection $correction): void
    {
        $supervisor = $correction->employee?->supervisor;
        if ($supervisor) {
            $supervisor->notify(new AttendanceCorrectionSubmittedNotification($correction));
        }
    }

    /**
     * Dispatch notification when an attendance correction is decided.
     */
    public function notifyCorrectionFinalDecision(AttendanceCorrection $correction): void
    {
        $user = $correction->employee?->user;
        if ($user) {
            $user->notify(new AttendanceCorrectionFinalDecisionNotification($correction));
        }
    }

    /**
     * Dispatch notification when an employee submits a late exception (force majeure).
     */
    public function notifyLateExceptionSubmitted(LateException $lateException): void
    {
        $supervisor = $lateException->employee?->supervisor;
        if ($supervisor) {
            $supervisor->notify(new LateExceptionSubmittedNotification($lateException));
        }
    }

    /**
     * Dispatch notification when a late exception is decided.
     */
    public function notifyLateExceptionFinalDecision(LateException $lateException): void
    {
        $user = $lateException->employee?->user;
        if ($user) {
            $user->notify(new LateExceptionFinalDecisionNotification($lateException));
        }
    }

    /**
     * Dispatch notification for overtime request lifecycle events.
     */
    public function notifyOvertimeStatusChanged(OvertimeRequest $overtimeRequest, string $eventType, ?string $spklReminder = null): void
    {
        $overtimeRequest->loadMissing(['proposer', 'items.employee.user']);

        if ($eventType === 'SUBMITTED') {
            $approvers = User::role('atasan')->get();
            if ($approvers->isNotEmpty()) {
                Notification::send($approvers, new OvertimeStatusChangedNotification($overtimeRequest, $eventType, $spklReminder));
            }

            return;
        }

        if ($overtimeRequest->proposer) {
            $overtimeRequest->proposer->notify(new OvertimeStatusChangedNotification($overtimeRequest, $eventType, $spklReminder));
        }

        if ($eventType === 'APPROVED') {
            $employeeUsers = $overtimeRequest->items
                ->map(fn ($item): ?User => $item->employee?->user)
                ->filter()
                ->unique('id');

            if ($employeeUsers->isNotEmpty()) {
                Notification::send($employeeUsers, new OvertimeStatusChangedNotification($overtimeRequest, $eventType, $spklReminder));
            }
        }
    }
}
