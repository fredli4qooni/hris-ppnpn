<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\ParallelRunReconciliation;
use App\Models\User;
use App\Services\ParallelRunReconciliationService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParallelRunReportController extends Controller
{
    public function __construct(
        protected ParallelRunReconciliationService $reconciliationService
    ) {}

    /**
     * Authorize access to Parallel Run reconciliation report.
     */
    protected function authorizeAccess(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || (! $user->hasRole('admin') && ! $user->hasRole('atasan') && ! $user->hasRole('supervisor'))) {
            abort(403, 'Akses ditolak. Hanya Administrator, Pengawas, dan Pejabat Pembuat Komitmen (PPK) yang berwenang mengakses laporan rekonsiliasi pilot run.');
        }
    }

    /**
     * Render the official printable / PDF view for the Parallel Run Reconciliation Report.
     */
    public function exportPdf(Request $request): View
    {
        $this->authorizeAccess();

        $stats = $this->reconciliationService->getConcordanceStatistics();
        $records = ParallelRunReconciliation::with(['employee.position', 'resolver'])
            ->orderBy('presence_date', 'desc')
            ->orderBy('employee_id')
            ->get();

        return view('exports.parallel-run-report-pdf', [
            'stats' => $stats,
            'records' => $records,
            'reportNumber' => 'BA-RECON/WPB.08/PPK/2026/001',
            'officialDate' => Carbon::now('Asia/Jakarta')->translatedFormat('d F Y'),
        ]);
    }
}
