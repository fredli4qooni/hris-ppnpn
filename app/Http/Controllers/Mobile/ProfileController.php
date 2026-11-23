<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Display the employee's self-service profile page (FR-102 / US-206).
     */
    public function show(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $employee = $user->employee()
            ->with(['position', 'supervisor', 'atasan', 'faceTemplate'])
            ->first();

        $daysRemaining = null;
        if ($employee?->contract_end_date) {
            $daysRemaining = (int) now()->startOfDay()->diffInDays($employee->contract_end_date, false);
        }

        return view('mobile.profile', [
            'user' => $user,
            'employee' => $employee,
            'daysRemaining' => $daysRemaining,
        ]);
    }
}
