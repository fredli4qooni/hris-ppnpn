<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserGuideController extends Controller
{
    /**
     * Display the mobile interactive onboarding guide with 5 tab modules (US-704).
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $employee = $user->employee()->with(['position', 'supervisor', 'atasan'])->first();
        $office = OfficeLocation::where('is_active', true)->first();
        $positions = Position::where('is_active', true)->get();

        return view('mobile.guide.index', [
            'user' => $user,
            'employee' => $employee,
            'office' => $office,
            'positions' => $positions,
            'currentDateWib' => Carbon::now('Asia/Jakarta')->translatedFormat('d F Y'),
        ]);
    }

    /**
     * Render the official printable / PDF User Manual Book (US-704).
     */
    public function downloadPdf(Request $request): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $office = OfficeLocation::where('is_active', true)->first();
        $positions = Position::where('is_active', true)->get();

        return view('exports.user-guide-pdf', [
            'user' => $user,
            'office' => $office,
            'positions' => $positions,
            'documentNumber' => 'MAN-HRIS/WPB.08/2026/001',
            'printedAt' => Carbon::now('Asia/Jakarta')->translatedFormat('d F Y'),
        ]);
    }
}
