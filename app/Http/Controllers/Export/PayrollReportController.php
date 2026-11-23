<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MonthlyClosingService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollReportController extends Controller
{
    public function __construct(
        protected MonthlyClosingService $closingService
    ) {}

    /**
     * Ensure the active user is authorized (Admin or Atasan/PPK).
     */
    protected function authorizeAccess(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || (! $user->hasRole('admin') && ! $user->hasRole('atasan'))) {
            abort(403, 'Akses ditolak. Hanya Administrator dan Pejabat Pembuat Komitmen (PPK) yang berwenang mengunduh berkas penggajian.');
        }
    }

    /**
     * Export monthly payroll & deduction report to CSV format for Microsoft Excel.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $this->authorizeAccess();

        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $data = $this->closingService->getPayrollData($year, $month);
        $fileName = "Rekap_Honorarium_PPNPN_{$data['year']}_{$data['month']}.csv";

        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for Microsoft Excel auto-detect
            fwrite($handle, "\xEF\xBB\xBF");

            // Document Header Block
            fputcsv($handle, ['KEMENTERIAN KEUANGAN REPUBLIK INDONESIA']);
            fputcsv($handle, ['DIREKTORAT JENDERAL PERBENDAHARAAN']);
            fputcsv($handle, ['KANTOR WILAYAH PROVINSI LAMPUNG']);
            fputcsv($handle, ['DAFTAR REKAPITULASI KEHADIRAN DAN PEMOTONGAN HONORARIUM PPNPN']);
            fputcsv($handle, ['Surat Perintah Kerja (SPK): SPK-20/WPB.08/PPK/2025']);
            fputcsv($handle, ["Periode Bulan: {$data['period_label']}"]);
            fputcsv($handle, ['Status Periode: '.($data['is_locked'] ? 'TERKUNCI / RESMI' : 'DRAF BERJALAN')]);
            fputcsv($handle, ['Tanggal Unduh: '.Carbon::now('Asia/Jakarta')->translatedFormat('d F Y, H:i:s').' WIB']);
            fputcsv($handle, []); // Empty line separator

            // Table Column Headers
            fputcsv($handle, [
                'No',
                'NIP / Kode Pegawai',
                'Nama Lengkap PPNPN',
                'Posisi Tugas',
                'Nomor SPK',
                'Gaji Pokok (Rp)',
                'Total Hari Hadir',
                'Hadir Tepat Waktu',
                'Toleransi Lunas',
                'TL 1 (0.5%)',
                'TL 2 (1.0%)',
                'TL 3 (1.5%)',
                'Cuti / Izin',
                'Alpa',
                'Akumulasi 1: % Potongan TL',
                'Akumulasi 1: Nominal Potongan TL (Rp)',
                'Akumulasi 2: Sanksi SP Aktif',
                'Akumulasi 2: % Potongan SP',
                'Akumulasi 2: Nominal Potongan SP (Rp)',
                'Total Potongan Honor (Rp)',
                'Honor Bersih Dibayarkan (Rp)',
                'Rekomendasi SP 3',
            ]);

            // Table Body Rows
            $index = 1;
            foreach ($data['items'] as $item) {
                fputcsv($handle, [
                    $index++,
                    $item['employee_code'],
                    $item['name'],
                    $item['position_name'],
                    $item['spk_number'],
                    number_format($item['base_salary'], 2, ',', '.'),
                    $item['total_attendance_days'],
                    $item['on_time_count'],
                    $item['tolerance_compensated_count'],
                    $item['tl_1_count'],
                    $item['tl_2_count'],
                    $item['tl_3_count'],
                    $item['leave_count'],
                    $item['alpha_count'],
                    number_format($item['accumulation_1_tl_percentage'], 2, ',', '.').'%',
                    number_format($item['accumulation_1_tl_amount'], 2, ',', '.'),
                    $item['accumulation_2_sanction_type'] ?? 'Nihil Sanksi',
                    number_format($item['accumulation_2_sanction_percentage'], 2, ',', '.').'%',
                    number_format($item['accumulation_2_sanction_amount'], 2, ',', '.'),
                    number_format($item['total_deduction_amount'], 2, ',', '.'),
                    number_format($item['net_salary'], 2, ',', '.'),
                    $item['is_termination_recommended'] ? 'REKOMENDASI PEMUTUSAN (SP 3)' : 'Tidak',
                ]);
            }

            // Summary Totals Row
            fputcsv($handle, []);
            fputcsv($handle, [
                'TOTAL KESELURUHAN',
                '',
                "{$data['totals']['total_employees']} Pegawai",
                '',
                '',
                number_format($data['totals']['total_base_salary'], 2, ',', '.'),
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                number_format($data['totals']['total_tl_deduction'], 2, ',', '.'),
                '',
                '',
                number_format($data['totals']['total_sp_deduction'], 2, ',', '.'),
                number_format($data['totals']['total_deductions_amount'], 2, ',', '.'),
                number_format($data['totals']['total_net_salary'], 2, ',', '.'),
                '',
            ]);

            // Sign-off note
            fputcsv($handle, []);
            fputcsv($handle, ['Bandar Lampung, '.Carbon::now('Asia/Jakarta')->translatedFormat('d F Y')]);
            fputcsv($handle, ['Pejabat Pembuat Komitmen (PPK)']);
            fputcsv($handle, ['Kanwil DJPb Provinsi Lampung']);

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Render the official printable / PDF view for the monthly payroll and deduction report.
     */
    public function exportPdf(Request $request): View
    {
        $this->authorizeAccess();

        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $data = $this->closingService->getPayrollData($year, $month);
        $data['printedAt'] = Carbon::now('Asia/Jakarta');

        return view('exports.payroll-report-pdf', $data);
    }
}
