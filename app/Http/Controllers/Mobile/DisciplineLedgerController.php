<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\EmployeeViolation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DisciplineLedgerController extends Controller
{
    /**
     * Display the employee's personal Discipline Ledger / Buku Disiplin & Pelanggaran (FR-409, FR-307 / US-505).
     */
    public function index(Request $request): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $employee = $user->employee()
            ->with([
                'position',
                'supervisor',
                'atasan',
                'violations' => function ($q) {
                    $q->with(['catalog', 'supervisor'])
                        ->orderBy('incident_date', 'desc');
                },
                'sanctions' => function ($q) {
                    $q->orderBy('issued_date', 'desc');
                },
            ])
            ->first();

        if (! $employee) {
            return redirect()->route('mobile.home')->with('error', 'Profil data pegawai Anda belum terdaftar.');
        }

        $activePoints = $employee->calculateActiveViolationPoints();
        $ewsStatus = $employee->getEwsStatus();

        // Active sanction evaluation
        $activeSanctions = $employee->sanctions->where('is_active', true);
        $activeSanction = $activeSanctions->first();

        $monthlyDeductionAmount = 0.0;
        if ($activeSanction && $activeSanction->deduction_percentage > 0 && $employee->base_salary) {
            $monthlyDeductionAmount = (float) $employee->base_salary * ((float) $activeSanction->deduction_percentage / 100);
        }

        return view('mobile.discipline.index', [
            'user' => $user,
            'employee' => $employee,
            'activePoints' => $activePoints,
            'ewsStatus' => $ewsStatus,
            'activeSanction' => $activeSanction,
            'activeSanctions' => $activeSanctions,
            'allSanctions' => $employee->sanctions,
            'violations' => $employee->violations,
            'activeViolationsCount' => $employee->violations->where('status', EmployeeViolation::STATUS_ACTIVE)->count(),
            'cancelledViolationsCount' => $employee->violations->where('status', EmployeeViolation::STATUS_CANCELLED)->count(),
            'monthlyDeductionAmount' => $monthlyDeductionAmount,
        ]);
    }
}
