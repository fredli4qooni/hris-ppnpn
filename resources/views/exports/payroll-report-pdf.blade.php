<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Honorarium PPNPN - {{ $period_label }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm 12mm 12mm;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print {
                display: none !important;
            }
            .page-break-inside-avoid {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            .shadow-sm, .shadow-md, .shadow-lg {
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body class="p-4 md:p-8 text-[11px] leading-normal">

    <!-- Top Action Toolbar (Screen Only) -->
    <div class="no-print max-w-7xl mx-auto mb-6 bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0058A8] flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <div>
                <h3 class="font-bold text-sm text-gray-900">Dokumen Rekapitulasi Penggajian & Potongan Resmi</h3>
                <p class="text-xs text-gray-500">Periode {{ $period_label }} &bull; SPK-20/WPB.08/PPK/2025</p>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a 
                href="{{ route('admin.payroll.export-excel', ['year' => $year, 'month' => $month]) }}" 
                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-xs transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Unduh Excel (.csv)</span>
            </a>
            <button 
                onclick="window.print()" 
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#0058A8] hover:bg-[#004787] text-white font-semibold text-xs rounded-xl shadow-xs transition cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Cetak / Simpan PDF</span>
            </button>
            <a 
                href="/admin" 
                class="inline-flex items-center gap-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-xs rounded-xl transition"
            >
                <span>Kembali ke Admin</span>
            </a>
        </div>
    </div>

    <!-- Official Printable Paper Container -->
    <div class="max-w-7xl mx-auto bg-white border border-gray-200 print:border-0 rounded-2xl p-6 md:p-10 shadow-sm print:p-0">
        
        <!-- Indonesian Ministry of Finance Official Header (KOP SURAT) -->
        <div class="border-b-4 border-black pb-3 mb-4">
            <div class="flex items-center justify-between">
                <div class="w-20 flex justify-center">
                    <!-- Kementerian Keuangan Seal Representation -->
                    <div class="w-16 h-16 rounded-full border-2 border-black flex flex-col items-center justify-center p-1 text-center font-bold">
                        <span class="text-[9px] uppercase tracking-tighter">KEMENKEU</span>
                        <span class="text-[7px]">RI</span>
                    </div>
                </div>
                <div class="text-center flex-1 px-4">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-black">KEMENTERIAN KEUANGAN REPUBLIK INDONESIA</h2>
                    <h1 class="text-base font-extrabold uppercase tracking-wide text-black mt-0.5">DIREKTORAT JENDERAL PERBENDAHARAAN</h1>
                    <h2 class="text-xs font-bold uppercase text-black mt-0.5">KANTOR WILAYAH PROVINSI LAMPUNG</h2>
                    <p class="text-[10px] text-gray-700 mt-1">
                        Jalan Gatot Subroto No. 44, Bandar Lampung 35227 &bull; Telepon: (0721) 482088 &bull; Faksimile: (0721) 482089
                    </p>
                    <p class="text-[9px] text-gray-600">
                        Laman: djpb.kemenkeu.go.id/kanwil/lampung &bull; Pos-el: djpb.lampung@kemenkeu.go.id
                    </p>
                </div>
                <div class="w-20 text-right">
                    <span class="text-[9px] uppercase font-bold px-2 py-0.5 rounded border {{ $is_locked ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-amber-50 text-amber-800 border-amber-300' }}">
                        {{ $is_locked ? 'LOCKED / FINAL' : 'DRAF BERJALAN' }}
                    </span>
                </div>
            </div>
            <!-- Standard Double Line separator -->
            <div class="border-t border-black mt-2 pt-0.5"></div>
        </div>

        <!-- Document Title & SPK Metadata -->
        <div class="text-center mb-6">
            <h3 class="text-sm font-extrabold uppercase tracking-wide underline text-black">
                DAFTAR REKAPITULASI KEHADIRAN DAN PEMOTONGAN HONORARIUM PPNPN
            </h3>
            <p class="text-xs font-semibold text-gray-800 mt-1">
                PERIODE BULAN: {{ strtoupper($period_label) }}
            </p>
            <p class="text-[10px] text-gray-600">
                Berdasarkan Surat Perintah Kerja (SPK) Nomor: <strong>SPK-20/WPB.08/PPK/2025</strong>
            </p>
        </div>

        <!-- Detail Data Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-black text-[10px]">
                <thead>
                    <tr class="bg-gray-100 text-center font-bold text-black uppercase">
                        <th rowspan="2" class="border border-black px-1.5 py-1.5 w-6">No</th>
                        <th rowspan="2" class="border border-black px-2 py-1.5 min-w-[130px]">Nama PPNPN / NIP</th>
                        <th rowspan="2" class="border border-black px-2 py-1.5">Posisi</th>
                        <th rowspan="2" class="border border-black px-2 py-1.5">Gaji Pokok (Rp)</th>
                        <th colspan="7" class="border border-black px-2 py-1 bg-gray-50">Rekapitulasi Kehadiran</th>
                        <th colspan="2" class="border border-black px-2 py-1 bg-blue-50/50">Akumulasi 1 (TL)</th>
                        <th colspan="3" class="border border-black px-2 py-1 bg-amber-50/50">Akumulasi 2 (Sanksi SP)</th>
                        <th rowspan="2" class="border border-black px-2 py-1.5">Total Potongan (Rp)</th>
                        <th rowspan="2" class="border border-black px-2 py-1.5">Honor Bersih (Rp)</th>
                    </tr>
                    <tr class="bg-gray-50 text-center font-semibold text-[9px] text-black">
                        <th class="border border-black px-1 py-1" title="Hadir Tepat Waktu">Hadir</th>
                        <th class="border border-black px-1 py-1" title="Toleransi Lunas">Tol.</th>
                        <th class="border border-black px-1 py-1" title="Terlambat 1 (0.5%)">TL1</th>
                        <th class="border border-black px-1 py-1" title="Terlambat 2 (1.0%)">TL2</th>
                        <th class="border border-black px-1 py-1" title="Terlambat 3 (1.5%)">TL3</th>
                        <th class="border border-black px-1 py-1" title="Cuti atau Izin">Cuti</th>
                        <th class="border border-black px-1 py-1" title="Alpa / Tanpa Keterangan">Alpa</th>
                        <th class="border border-black px-1.5 py-1">% TL</th>
                        <th class="border border-black px-2 py-1">Potongan TL</th>
                        <th class="border border-black px-1.5 py-1">Tingkat</th>
                        <th class="border border-black px-1.5 py-1">% SP</th>
                        <th class="border border-black px-2 py-1">Potongan SP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $index => $row)
                        <tr class="{{ $index % 2 === 1 ? 'bg-gray-50/60' : 'bg-white' }}">
                            <td class="border border-black text-center py-1.5">{{ $index + 1 }}</td>
                            <td class="border border-black px-2 py-1.5 font-semibold text-black">
                                <div>{{ $row['name'] }}</div>
                                <div class="text-[9px] text-gray-600 font-mono font-normal">{{ $row['employee_code'] }}</div>
                            </td>
                            <td class="border border-black px-2 py-1.5 text-center">{{ $row['position_name'] }}</td>
                            <td class="border border-black px-2 py-1.5 text-right font-mono font-medium">
                                {{ number_format($row['base_salary'], 0, ',', '.') }}
                            </td>
                            <td class="border border-black text-center py-1 font-mono text-emerald-800 font-semibold">{{ $row['on_time_count'] }}</td>
                            <td class="border border-black text-center py-1 font-mono">{{ $row['tolerance_compensated_count'] }}</td>
                            <td class="border border-black text-center py-1 font-mono {{ $row['tl_1_count'] > 0 ? 'text-amber-800 font-bold' : '' }}">{{ $row['tl_1_count'] }}</td>
                            <td class="border border-black text-center py-1 font-mono {{ $row['tl_2_count'] > 0 ? 'text-rose-700 font-bold' : '' }}">{{ $row['tl_2_count'] }}</td>
                            <td class="border border-black text-center py-1 font-mono {{ $row['tl_3_count'] > 0 ? 'text-rose-900 font-bold' : '' }}">{{ $row['tl_3_count'] }}</td>
                            <td class="border border-black text-center py-1 font-mono text-blue-700">{{ $row['leave_count'] }}</td>
                            <td class="border border-black text-center py-1 font-mono {{ $row['alpha_count'] > 0 ? 'text-red-700 font-bold' : '' }}">{{ $row['alpha_count'] }}</td>
                            
                            <!-- Akumulasi 1 (TL) -->
                            <td class="border border-black text-center py-1 font-mono">
                                {{ number_format($row['accumulation_1_tl_percentage'], 1, ',', '.') }}%
                            </td>
                            <td class="border border-black px-2 py-1 text-right font-mono {{ $row['accumulation_1_tl_amount'] > 0 ? 'text-amber-800 font-medium' : '' }}">
                                {{ number_format($row['accumulation_1_tl_amount'], 0, ',', '.') }}
                            </td>

                            <!-- Akumulasi 2 (Sanksi SP) -->
                            <td class="border border-black text-center py-1 text-[9px] font-semibold">
                                {{ $row['accumulation_2_sanction_type'] ? str_replace('_', ' ', $row['accumulation_2_sanction_type']) : '-' }}
                            </td>
                            <td class="border border-black text-center py-1 font-mono">
                                {{ number_format($row['accumulation_2_sanction_percentage'], 1, ',', '.') }}%
                            </td>
                            <td class="border border-black px-2 py-1 text-right font-mono {{ $row['accumulation_2_sanction_amount'] > 0 ? 'text-rose-700 font-medium' : '' }}">
                                {{ number_format($row['accumulation_2_sanction_amount'], 0, ',', '.') }}
                            </td>

                            <!-- Total Potongan & Net Honor -->
                            <td class="border border-black px-2 py-1.5 text-right font-mono font-bold {{ $row['total_deduction_amount'] > 0 ? 'text-rose-800' : 'text-gray-600' }}">
                                {{ number_format($row['total_deduction_amount'], 0, ',', '.') }}
                            </td>
                            <td class="border border-black px-2 py-1.5 text-right font-mono font-extrabold text-emerald-800">
                                {{ number_format($row['net_salary'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="19" class="border border-black text-center py-4 text-gray-500">
                                Tidak ada data pegawai aktif pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-gray-200/90 font-bold text-black border-t-2 border-black">
                        <td colspan="3" class="border border-black px-3 py-2 text-center uppercase tracking-wider">
                            Total Keseluruhan ({{ $totals['total_employees'] }} Personil)
                        </td>
                        <td class="border border-black px-2 py-2 text-right font-mono">
                            {{ number_format($totals['total_base_salary'], 0, ',', '.') }}
                        </td>
                        <td colspan="8" class="border border-black text-center text-gray-500 font-normal text-[9px]">
                            — Akumulasi 1 (TL) & Akumulasi 2 (SP) Terpisah —
                        </td>
                        <td class="border border-black px-2 py-2 text-right font-mono text-amber-900">
                            {{ number_format($totals['total_tl_deduction'], 0, ',', '.') }}
                        </td>
                        <td colspan="2" class="border border-black"></td>
                        <td class="border border-black px-2 py-2 text-right font-mono text-rose-900">
                            {{ number_format($totals['total_sp_deduction'], 0, ',', '.') }}
                        </td>
                        <td class="border border-black px-2 py-2 text-right font-mono font-extrabold text-rose-900">
                            {{ number_format($totals['total_deductions_amount'], 0, ',', '.') }}
                        </td>
                        <td class="border border-black px-2 py-2 text-right font-mono font-extrabold text-emerald-900">
                            {{ number_format($totals['total_net_salary'], 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Notes & Dual Accumulation Policy Confirmation -->
        <div class="mt-4 text-[9px] text-gray-600 space-y-0.5">
            <p><strong>Catatan Hukum & Ketentuan:</strong></p>
            <p>1. Perhitungan potongan keterlambatan masuk (Akumulasi 1) murni merujuk pada ketentuan Lampiran IV SPK-20/WPB.08/PPK/2025.</p>
            <p>2. Perhitungan potongan sanksi disiplin (Akumulasi 2) merujuk pada penetapan Surat Peringatan berdasarkan Lampiran III SPK-20/WPB.08/PPK/2025.</p>
            <p>3. Kedua akumulasi berdiri sendiri dan terpisah tanpa tumpang tindih (*Dual Accumulation Isolation*).</p>
        </div>

        <!-- Formal Approval Signature Block (PPK) -->
        <div class="mt-8 page-break-inside-avoid flex justify-between items-end">
            <div class="text-[9px] text-gray-500">
                <p>Dokumen Resmi Dicetak Melalui HRIS PPNPN Kanwil DJPb Lampung</p>
                <p>Waktu Cetak: {{ $printedAt->translatedFormat('d F Y, H:i') }} WIB</p>
                @if($closing)
                    <p class="font-medium text-gray-700">Status Kunci: Dikunci oleh {{ $closing->closedBy?->name ?? 'Admin' }} pada {{ $closing->closed_at?->translatedFormat('d F Y, H:i') }} WIB</p>
                @endif
            </div>

            <div class="text-center w-72 text-black">
                <p>Bandar Lampung, {{ $printedAt->translatedFormat('d F Y') }}</p>
                <p class="font-bold mt-0.5">Pejabat Pembuat Komitmen (PPK)</p>
                <p class="text-[10px]">Satker Kanwil DJPb Provinsi Lampung</p>
                
                <!-- Digital Signature Placeholder / Box -->
                <div class="h-20 flex items-center justify-center my-1">
                    <div class="border border-dashed border-gray-400 rounded-lg p-2 text-center text-gray-500 text-[8px] max-w-[200px]">
                        <span class="block font-bold text-gray-700">TERVERIFIKASI SISTEM ELEKTRONIK</span>
                        <span class="block text-[7px] text-gray-400">Kanwil Ditjen Perbendaharaan Provinsi Lampung</span>
                    </div>
                </div>

                <p class="font-bold underline text-xs">MOHAMAD AGUS PRIYANTO</p>
                <p class="text-[10px] font-mono text-gray-800">NIP. 197808152000031001</p>
            </div>
        </div>

    </div>

</body>
</html>
