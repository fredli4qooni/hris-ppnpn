<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\OvertimeMasterOrder;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSetting;
use App\Models\User;
use App\Services\OvertimeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OvertimeReportController extends Controller
{
    public function __construct(
        protected OvertimeService $overtimeService
    ) {}

    /**
     * Authorize access for backoffice roles or listed employee on SPTPL.
     */
    private function authorizeRequestAccess(OvertimeRequest $overtimeRequest): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        if ($user->hasRole('admin') || $user->hasRole('atasan') || $user->hasRole('supervisor')) {
            return;
        }

        $employeeId = $user->employee?->id;
        if ($employeeId && $overtimeRequest->items()->where('employee_id', $employeeId)->exists()) {
            return;
        }

        abort(403, 'Anda tidak berwenang mengakses dokumen surat perintah lembur ini.');
    }

    /**
     * Render printable SPTPL document (guarding signature block from page splitting).
     */
    public function printRequest(OvertimeRequest $overtimeRequest): View
    {
        $this->authorizeRequestAccess($overtimeRequest);
        $overtimeRequest->load(['items.employee.position', 'proposer', 'approver', 'masterOrder']);
        $settings = OvertimeSetting::current();

        return view('exports.overtime-sptpl-pdf', [
            'overtimeRequest' => $overtimeRequest,
            'settings' => $settings,
        ]);
    }

    /**
     * Render printable SPKL monthly master order + compiled approved SPTPL attachments.
     */
    public function printMasterOrder(OvertimeMasterOrder $overtimeMasterOrder): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || (! $user->hasRole('admin') && ! $user->hasRole('atasan') && ! $user->hasRole('supervisor'))) {
            abort(403);
        }

        $attachments = OvertimeRequest::with(['items.employee'])
            ->where('request_type', OvertimeRequest::TYPE_WITH_LETTER)
            ->where('status', OvertimeRequest::STATUS_APPROVED)
            ->whereYear('start_date', $overtimeMasterOrder->period_year)
            ->whereMonth('start_date', $overtimeMasterOrder->period_month)
            ->orderBy('start_date')
            ->orderBy('sequence_number')
            ->get();

        return view('exports.overtime-spkl-pdf', [
            'masterOrder' => $overtimeMasterOrder,
            'attachments' => $attachments,
            'settings' => OvertimeSetting::current(),
        ]);
    }

    /**
     * Export monthly overtime recapitulation CSV (hours & status only, no tariff).
     */
    public function exportRecapExcel(Request $request): StreamedResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || (! $user->hasRole('admin') && ! $user->hasRole('atasan') && ! $user->hasRole('supervisor'))) {
            abort(403);
        }

        $year = (int) $request->input('year', now('Asia/Jakarta')->year);
        $month = (int) $request->input('month', now('Asia/Jakarta')->month);
        $recap = $this->overtimeService->getMonthlyOvertimeRecap($year, $month);

        $fileName = "Rekap_Lembur_PPNPN_{$year}_{$month}.csv";

        return response()->streamDownload(function () use ($recap): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['KEMENTERIAN KEUANGAN REPUBLIK INDONESIA']);
            fputcsv($handle, ['KANTOR WILAYAH DIREKTORAT JENDERAL PERBENDAHARAAN PROVINSI LAMPUNG']);
            fputcsv($handle, ["REKAPITULASI JAM LEMBUR PPNPN - {$recap['month_name']} {$recap['year']}"]);
            fputcsv($handle, []);

            $headers = ['No', 'NIP / Kode', 'Nama Pegawai', 'Jabatan'];
            for ($d = 1; $d <= $recap['days_in_month']; $d++) {
                $headers[] = (string) $d;
            }
            $headers = array_merge($headers, ['Jam Hari Kerja', 'Jam Hari Libur', 'Jam Dengan Surat', 'Jam Tanpa Surat', 'Total Jam', 'Status Surat Tugas']);
            fputcsv($handle, $headers);

            $no = 1;
            foreach ($recap['items'] as $row) {
                $line = [$no++, $row['employee_code'], $row['name'], $row['position_name']];
                for ($d = 1; $d <= $recap['days_in_month']; $d++) {
                    $hrs = (float) $row['days'][$d]['total_hours'];
                    $line[] = $hrs > 0 ? (string) $hrs : '0';
                }
                $line[] = (string) $row['workday_hours'];
                $line[] = (string) $row['holiday_hours'];
                $line[] = (string) $row['hours_with_letter'];
                $line[] = (string) $row['hours_without_letter'];
                $line[] = (string) $row['total_hours'];
                $line[] = $row['overtime_status_label'];
                fputcsv($handle, $line);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Render printable PDF monthly overtime recapitulation.
     */
    public function exportRecapPdf(Request $request): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || (! $user->hasRole('admin') && ! $user->hasRole('atasan') && ! $user->hasRole('supervisor'))) {
            abort(403);
        }

        $year = (int) $request->input('year', now('Asia/Jakarta')->year);
        $month = (int) $request->input('month', now('Asia/Jakarta')->month);
        $recap = $this->overtimeService->getMonthlyOvertimeRecap($year, $month);

        return view('exports.overtime-recap-pdf', [
            'recap' => $recap,
            'settings' => OvertimeSetting::current(),
        ]);
    }
}
