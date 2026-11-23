<?php

namespace App\Services;

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BastHandoverService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BastReportController extends Controller
{
    public function __construct(
        protected BastHandoverService $bastService
    ) {}

    /**
     * Authorize access to BAST Handover document.
     */
    protected function authorizeAccess(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || (! $user->hasRole('admin') && ! $user->hasRole('atasan') && ! $user->hasRole('supervisor'))) {
            abort(403, 'Akses ditolak. Hanya Administrator, Pengawas, dan Pejabat Pembuat Komitmen (PPK) yang berwenang mengakses naskah Berita Acara Serah Terima (BAST).');
        }
    }

    /**
     * Render the official printable / PDF view for the BAST Handover document.
     */
    public function exportPdf(Request $request): View
    {
        $this->authorizeAccess();

        $bast = $this->bastService->getOrCreateOfficialBast();
        $checklist = $this->bastService->getGoLiveChecklist();
        $milestones = $this->bastService->getSprintMilestonesSummary();

        return view('exports.bast-report-pdf', [
            'bast' => $bast,
            'checklist' => $checklist,
            'milestones' => $milestones,
            'officialDate' => Carbon::parse($bast->handover_date)->translatedFormat('d F Y'),
        ]);
    }
}
