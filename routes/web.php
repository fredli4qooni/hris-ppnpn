<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Export\BackupDownloadController;
use App\Http\Controllers\Export\BastReportController;
use App\Http\Controllers\Export\EmployeeDisciplineReportController;
use App\Http\Controllers\Export\ParallelRunReportController;
use App\Http\Controllers\Export\PayrollReportController;
use App\Http\Controllers\Export\UatReportController;
use App\Http\Controllers\Mobile\AttendanceController;
use App\Http\Controllers\Mobile\AttendanceCorrectionController;
use App\Http\Controllers\Mobile\AttendanceHistoryController;
use App\Http\Controllers\Mobile\DisciplineLedgerController;
use App\Http\Controllers\Mobile\LateExceptionController;
use App\Http\Controllers\Mobile\LeaveRequestController;
use App\Http\Controllers\Mobile\NotificationController;
use App\Http\Controllers\Mobile\ProfileController;
use App\Http\Controllers\Mobile\UserGuideController;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Root & Guest Authentication
Route::get('/', [AuthController::class, 'showLoginForm'])->name('root');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware(['guest', 'throttle:auth-attempt'])->name('login.post');

// PWA Offline Fallback Shell (FR-208 / US-702)
Route::get('/mobile/offline', function () {
    return view('mobile.offline');
})->name('mobile.offline');

// Global Home Redirection
Route::get('/home', function () {
    /** @var User|null $user */
    $user = Auth::user();
    if ($user && method_exists($user, 'hasRole') && $user->hasRole('karyawan')) {
        return redirect()->route('mobile.home');
    }

    return redirect()->to('/admin');
})->name('home');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // First Password Change Form (FR-101 / US-105)
    Route::get('/password/first-change', [AuthController::class, 'showFirstPasswordChangeForm'])->name('password.first-change');
    Route::post('/password/first-change', [AuthController::class, 'updateFirstPassword'])->name('password.first-change.update');

    // Admin Payroll Export & Print Routes (FR-302, FR-305, FR-306, FR-307)
    Route::get('/admin/payroll/export-excel', [PayrollReportController::class, 'exportExcel'])->name('admin.payroll.export-excel');
    Route::get('/admin/payroll/export-pdf', [PayrollReportController::class, 'exportPdf'])->name('admin.payroll.export-pdf');

    // Admin UAT Berita Acara Export & Print (US-701)
    Route::get('/admin/uat/export-pdf', [UatReportController::class, 'exportPdf'])->name('admin.uat.export-pdf');

    // Admin Parallel Pilot Run Reconciliation Export & Print (US-801)
    Route::get('/admin/reconciliation/export-pdf', [ParallelRunReportController::class, 'exportPdf'])->name('admin.reconciliation.export-pdf');

    // Admin BAST Handover Export & Print (US-804)
    Route::get('/admin/bast/export-pdf', [BastReportController::class, 'exportPdf'])->name('admin.bast.export-pdf');

    // Admin DRP Backup Archive Download (Strictly Admin / US-802)
    Route::get('/admin/backups/{record}/download', [BackupDownloadController::class, 'download'])->name('admin.backups.download');

    // Employee Discipline History & BAP Print / Export (FR-407 / US-504)
    Route::get('/admin/employees/{employee}/discipline-summary', [EmployeeDisciplineReportController::class, 'showSummary'])->name('admin.employees.discipline-summary');

    // Protected Employee Mobile Portal
    Route::middleware(EnsurePasswordIsChanged::class)->group(function () {
        Route::get('/mobile/home', function () {
            return view('mobile.home');
        })->name('mobile.home');

        Route::get('/mobile/attendance', [AttendanceController::class, 'showScanner'])->name('mobile.attendance');

        // Throttled Attendance APIs (US-703: 30 req/min)
        Route::middleware('throttle:attendance-api')->group(function () {
            Route::post('/mobile/attendance/verify-geofence', [AttendanceController::class, 'verifyGeofence'])->name('mobile.attendance.verify-geofence');
            Route::post('/mobile/attendance/verify-liveness', [AttendanceController::class, 'verifyLiveness'])->name('mobile.attendance.verify-liveness');
            Route::post('/mobile/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('mobile.attendance.check-in');
            Route::post('/mobile/attendance/fallback-check-in', [AttendanceController::class, 'fallbackCheckIn'])->name('mobile.attendance.fallback-check-in');
            Route::post('/mobile/attendance/check-out', [AttendanceController::class, 'checkOut'])->name('mobile.attendance.check-out');
        });
        Route::get('/mobile/attendance/history', [AttendanceHistoryController::class, 'index'])->name('mobile.attendance.history');
        Route::get('/mobile/profile', [ProfileController::class, 'show'])->name('mobile.profile');
        Route::get('/mobile/discipline', [DisciplineLedgerController::class, 'index'])->name('mobile.discipline.index');

        // Attendance Corrections / Lupa Absen (FR-210 / US-501)
        Route::get('/mobile/corrections', [AttendanceCorrectionController::class, 'index'])->name('mobile.corrections.index');
        Route::get('/mobile/corrections/create', [AttendanceCorrectionController::class, 'create'])->name('mobile.corrections.create');
        Route::post('/mobile/corrections', [AttendanceCorrectionController::class, 'store'])->name('mobile.corrections.store');
        Route::get('/mobile/corrections/{correction}', [AttendanceCorrectionController::class, 'show'])->name('mobile.corrections.show');

        // Late Exceptions / Force Majeure Dispensation (FR-303 / US-502)
        Route::get('/mobile/late-exceptions', [LateExceptionController::class, 'index'])->name('mobile.late-exceptions.index');
        Route::get('/mobile/late-exceptions/create', [LateExceptionController::class, 'create'])->name('mobile.late-exceptions.create');
        Route::post('/mobile/late-exceptions', [LateExceptionController::class, 'store'])->name('mobile.late-exceptions.store');
        Route::get('/mobile/late-exceptions/{lateException}', [LateExceptionController::class, 'show'])->name('mobile.late-exceptions.show');

        // Leave & Permit Requests (FR-501, FR-502, FR-504, FR-505)
        Route::get('/mobile/leaves', [LeaveRequestController::class, 'index'])->name('mobile.leaves.index');
        Route::get('/mobile/leaves/create', [LeaveRequestController::class, 'create'])->name('mobile.leaves.create');
        Route::post('/mobile/leaves', [LeaveRequestController::class, 'store'])->name('mobile.leaves.store');
        Route::get('/mobile/leaves/{leaveRequest}', [LeaveRequestController::class, 'show'])->name('mobile.leaves.show');
        Route::post('/mobile/leaves/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])->name('mobile.leaves.cancel');

        // In-App Notification Hub (FR-701 / US-601)
        Route::get('/mobile/notifications', [NotificationController::class, 'index'])->name('mobile.notifications.index');
        Route::post('/mobile/notifications/{id}/mark-read', [NotificationController::class, 'markRead'])->name('mobile.notifications.mark-read');
        Route::post('/mobile/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('mobile.notifications.mark-all-read');

        // Interactive User Guide & Digital Manual Book (FR-804 / US-704)
        Route::get('/mobile/guide', [UserGuideController::class, 'index'])->name('mobile.guide');
        Route::get('/mobile/guide/download-pdf', [UserGuideController::class, 'downloadPdf'])->name('mobile.guide.download-pdf');
    });
});
