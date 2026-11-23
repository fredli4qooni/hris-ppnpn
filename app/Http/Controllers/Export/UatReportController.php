<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UatExecutionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UatReportController extends Controller
{
    public function __construct(
        protected UatExecutionService $uatService
    ) {}

    /**
     * Authorize access to UAT reporting (Admin, Atasan/PPK, or Supervisor).
     */
    protected function authorizeAccess(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || (! $user->hasRole('admin') && ! $user->hasRole('atasan') && ! $user->hasRole('supervisor'))) {
            abort(403, 'Akses ditolak. Hanya Administrator, Pengawas, dan Pejabat Pembuat Komitmen (PPK) yang berwenang mengunduh dokumen Berita Acara UAT.');
        }
    }

    /**
     * Render the official printable / PDF view for the UAT Handover & Verification Report.
     */
    public function exportPdf(Request $request): View
    {
        $this->authorizeAccess();

        $data = $this->uatService->getReportData();

        return view('exports.uat-report-pdf', $data);
    }
}
