<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeViolation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeDisciplineReportController extends Controller
{
    /**
     * Show comprehensive printable discipline summary / Berita Acara Pembinaan (FR-407 / US-504).
     */
    public function showSummary(Request $request, Employee $employee): View
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            abort(401);
        }

        // Authorization: Admin, Atasan, Supervisor of this employee, or the employee themself
        $isAuthorized = $user->hasRole('admin')
            || $user->hasRole('atasan')
            || ($user->hasRole('supervisor') && $employee->supervisor_id === $user->id)
            || ($user->employee?->id === $employee->id);

        if (! $isAuthorized) {
            abort(403, 'Akses ditolak. Anda tidak memiliki wewenang untuk melihat berkas rekam jejak pembinaan disiplin ini.');
        }

        $employee->load([
            'position',
            'supervisor',
            'atasan',
            'violations' => function ($query) {
                $query->with(['catalog', 'supervisor'])
                    ->where('status', EmployeeViolation::STATUS_ACTIVE)
                    ->orderBy('incident_date', 'desc');
            },
            'sanctions' => function ($query) {
                $query->orderBy('issued_date', 'desc');
            },
        ]);

        $activePoints = $employee->calculateActiveViolationPoints();
        $ewsStatus = $employee->getEwsStatus();
        $generatedAt = Carbon::now('Asia/Jakarta');

        return view('exports.employee-discipline-summary', [
            'employee' => $employee,
            'ewsStatus' => $ewsStatus,
            'activePoints' => $activePoints,
            'violations' => $employee->violations,
            'sanctions' => $employee->sanctions,
            'generatedAt' => $generatedAt,
        ]);
    }
}
