<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Rekonsiliasi Pilot Run - Kanwil DJPb Provinsi Lampung</title>
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
            .page-break-after {
                page-break-after: always;
            }
        }
    </style>
</head>
<body class="p-4 md:p-8 text-[11px] leading-relaxed">

    <!-- Top Action Toolbar (Screen Only) -->
    <div class="no-print max-w-6xl mx-auto mb-6 bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0058A8] flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <div>
                <h3 class="font-bold text-sm text-gray-900">Dokumen Berita Acara Rekonsiliasi Pilot Run Paralel (Sprint 8 / US-801)</h3>
                <p class="text-xs text-gray-500">Nomor: {{ $reportNumber }} &bull; Tanggal: {{ $officialDate }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <button 
                onclick="window.print()" 
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#0058A8] hover:bg-[#0B2A4A] text-white font-semibold text-xs rounded-xl shadow-xs transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Cetak Berita Acara (Print / PDF)</span>
            </button>
            <a 
                href="/admin/parallel-run-reconciliations" 
                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-xs rounded-xl transition">
                <span>Kembali ke Panel Admin</span>
            </a>
        </div>
    </div>

    <!-- Printable Official Document Container -->
    <div class="max-w-6xl mx-auto bg-white border border-gray-200 p-8 rounded-2xl shadow-sm print:border-none print:p-0 print:shadow-none space-y-5">
        
        <!-- Kop Surat Kementerian Keuangan DJPb -->
        <div class="border-b-2 border-gray-900 pb-3 text-center relative">
            <h4 class="text-[12px] tracking-wider uppercase font-bold text-gray-700 leading-tight">KEMENTERIAN KEUANGAN REPUBLIK INDONESIA</h4>
            <h3 class="text-[13px] tracking-wider uppercase font-extrabold text-[#0B2A4A] leading-tight">DIREKTORAT JENDERAL PERBENDAHARAAN</h3>
            <h2 class="text-[14px] uppercase font-black text-[#0058A8] leading-tight">KANTOR WILAYAH PROVINSI LAMPUNG</h2>
            <p class="text-[9.5px] text-gray-500 mt-1">Jalan Gatot Subroto No. 44, Bandar Lampung 35226 &bull; Telepon: (0721) 261882 &bull; Laman: djpb.kemenkeu.go.id/kanwil/lampung</p>
        </div>

        <!-- Document Title -->
        <div class="text-center space-y-1">
            <h1 class="text-base font-extrabold text-gray-900 tracking-wide uppercase">BERITA ACARA REKONSILIASI UJI COBA PARALEL (PARALLEL PILOT RUN)</h1>
            <h2 class="text-xs font-bold text-[#0058A8] uppercase tracking-wider">KOMPARASI REKAPITULASI PRESENSI MANUAL DENGAN SISTEM HRIS DIGITAL PPNPN</h2>
            <p class="text-[10px] text-gray-600 font-mono">Nomor: {{ $reportNumber }} &bull; Acuan SPK: SPK-20/WPB.08/PPK/2025</p>
        </div>

        <!-- Executive Narrative Summary -->
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 text-justify text-[10.5px] leading-relaxed text-gray-700">
            Pada hari ini, <strong>{{ $officialDate }}</strong>, telah diselesaikan evaluasi rekonsiliasi data kehadiran masa uji coba paralel (*Parallel Pilot Run*) selama 10 hari kerja kalender. Pengujian ini membandingkan data absensi manual fisik (buku tanda tangan/fingerprint cadangan) terhadap data kehadiran digital sistem HRIS mobile Kanwil DJPb Provinsi Lampung. Hasil rekonsiliasi menunjukkan tingkat keselarasan data (*Concordance Rate*) mencapai <strong>{{ number_format($stats['concordance_rate'], 2) }}%</strong> (standar kelulusan exit criteria: $\ge 99\%$).
        </div>

        <!-- Metric Cards Grid -->
        <div class="grid grid-cols-4 gap-3 text-center text-xs">
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-2.5">
                <span class="text-[10px] text-blue-700 uppercase font-bold block">Total Log Diuji</span>
                <span class="text-base font-extrabold text-[#0058A8] font-mono">{{ $stats['total_records'] }}</span>
                <span class="text-[9px] text-gray-500 block">Presensi Pegawai</span>
            </div>
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-2.5">
                <span class="text-[10px] text-emerald-700 uppercase font-bold block">Tingkat Keselarasan</span>
                <span class="text-base font-extrabold text-emerald-800 font-mono">{{ number_format($stats['concordance_rate'], 2) }}%</span>
                <span class="text-[9px] text-emerald-600 block">Target: &ge; 99% (Lulus)</span>
            </div>
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-2.5">
                <span class="text-[10px] text-amber-800 uppercase font-bold block">Deviasi Waktu / Status</span>
                <span class="text-base font-extrabold text-amber-900 font-mono">{{ $stats['drift_records'] + $stats['mismatch_records'] }}</span>
                <span class="text-[9px] text-gray-500 block">Selisih &gt; 5 Mnt / Status</span>
            </div>
            <div class="bg-purple-50 border border-purple-200 rounded-xl p-2.5">
                <span class="text-[10px] text-purple-700 uppercase font-bold block">Status Resolusi PPK</span>
                <span class="text-base font-extrabold text-purple-900 font-mono">{{ $stats['resolved_discrepancies'] }} / {{ $stats['pending_discrepancies'] + $stats['resolved_discrepancies'] }}</span>
                <span class="text-[9px] text-gray-500 block">Disahkan Kasubbag Umum</span>
            </div>
        </div>

        <!-- Detailed Reconciliation Ledger Table -->
        <div class="border border-gray-200 rounded-xl overflow-hidden shadow-2xs">
            <table class="w-full text-left border-collapse text-[10px]">
                <thead class="bg-gray-100 text-gray-800 font-bold border-b border-gray-300">
                    <tr>
                        <th class="py-2 px-2 text-center w-8">No</th>
                        <th class="py-2 px-2 w-24">Tanggal</th>
                        <th class="py-2 px-3">Pegawai / Formasi</th>
                        <th class="py-2 px-2 text-center bg-blue-50/50">Masuk Manual</th>
                        <th class="py-2 px-2 text-center bg-emerald-50/50">Masuk Digital</th>
                        <th class="py-2 px-2 text-center">Selisih</th>
                        <th class="py-2 px-2 text-center">Klasifikasi Selisih</th>
                        <th class="py-2 px-2 text-right">Potongan Manual</th>
                        <th class="py-2 px-2 text-right">Potongan Digital</th>
                        <th class="py-2 px-2 text-center">Status Resolusi</th>
                        <th class="py-2 px-3">Catatan Otorisasi PPK</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 font-medium">
                    @forelse($records as $idx => $record)
                        <tr class="{{ $record->discrepancy_type !== 'MATCH' ? ($record->resolution_status === 'RESOLVED' ? 'bg-purple-50/20' : 'bg-amber-50/40') : 'hover:bg-gray-50/60' }}">
                            <td class="py-1.5 px-2 text-center font-mono">{{ $idx + 1 }}</td>
                            <td class="py-1.5 px-2 font-mono whitespace-nowrap">{{ $record->presence_date?->format('d/m/Y') }}</td>
                            <td class="py-1.5 px-3">
                                <span class="font-bold text-gray-900 block">{{ $record->employee?->full_name ?? 'N/A' }}</span>
                                <span class="text-[9px] text-gray-500 font-mono">{{ $record->employee?->employee_code }} &bull; {{ $record->employee?->position?->name }}</span>
                            </td>
                            <td class="py-1.5 px-2 text-center font-mono {{ $record->manual_status === 'ALPA' ? 'text-rose-600 font-bold' : '' }}">
                                {{ $record->manual_check_in ?? '-' }}
                                <span class="text-[8.5px] block text-gray-500 uppercase">{{ $record->manual_status }}</span>
                            </td>
                            <td class="py-1.5 px-2 text-center font-mono {{ $record->digital_status === 'ALPA' ? 'text-rose-600 font-bold' : '' }}">
                                {{ $record->digital_check_in ?? '-' }}
                                <span class="text-[8.5px] block text-gray-500 uppercase">{{ $record->digital_status }}</span>
                            </td>
                            <td class="py-1.5 px-2 text-center font-mono {{ $record->time_diff_minutes > 5 ? 'font-bold text-amber-700' : 'text-gray-600' }}">
                                {{ $record->time_diff_minutes }} mnt
                            </td>
                            <td class="py-1.5 px-2 text-center">
                                @if($record->discrepancy_type === 'MATCH')
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[8.5px] font-bold bg-emerald-100 text-emerald-800">MATCH</span>
                                @elseif($record->discrepancy_type === 'TIME_DRIFT')
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[8.5px] font-bold bg-amber-100 text-amber-900">TIME DRIFT</span>
                                @elseif($record->discrepancy_type === 'STATUS_MISMATCH')
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[8.5px] font-bold bg-rose-100 text-rose-900">STATUS MISMATCH</span>
                                @else
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[8.5px] font-bold bg-blue-100 text-blue-900">{{ $record->discrepancy_type }}</span>
                                @endif
                            </td>
                            <td class="py-1.5 px-2 text-right font-mono">{{ number_format($record->manual_penalty_rate, 1) }}%</td>
                            <td class="py-1.5 px-2 text-right font-mono">{{ number_format($record->digital_penalty_rate, 1) }}%</td>
                            <td class="py-1.5 px-2 text-center">
                                @if($record->resolution_status === 'RESOLVED')
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[8.5px] font-bold bg-purple-100 text-purple-800">RESOLVED</span>
                                @elseif($record->resolution_status === 'AUTO_MATCHED')
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[8.5px] font-bold bg-emerald-100 text-emerald-800">AUTO</span>
                                @else
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[8.5px] font-bold bg-amber-100 text-amber-800">PENDING</span>
                                @endif
                            </td>
                            <td class="py-1.5 px-3 text-[9px] text-gray-600">
                                {{ $record->resolution_notes ?? '-' }}
                                @if($record->resolver)
                                    <span class="block text-[8px] text-gray-400 font-mono">Oleh: {{ $record->resolver->name }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="py-6 text-center text-gray-500">Belum ada data rekonsiliasi yang dihitung.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Official Sign-off Block -->
        <div class="page-break-inside-avoid pt-6 border-t border-gray-300">
            <div class="grid grid-cols-2 gap-12 text-center text-[10.5px]">
                <div>
                    <p class="text-gray-500 font-semibold mb-1">Diverifikasi & Direkonsiliasi Oleh,</p>
                    <p class="text-gray-800 font-bold mb-1">Tim Pengembang Sistem (Scrum Master)</p>
                    <div class="h-16 flex items-end justify-center">
                        <span class="border-b border-gray-400 w-56 block"></span>
                    </div>
                    <p class="font-bold text-gray-900 mt-1">Dewi Nadila, S.Kom.</p>
                    <p class="text-[9px] text-gray-500">Lead Developer / Verifikator Mutu</p>
                </div>

                <div>
                    <p class="text-gray-500 font-semibold mb-1">Disahkan & Ditetapkan Sebagai Acuan Cut-Off,</p>
                    <p class="text-gray-800 font-bold mb-1">Pejabat Pembuat Komitmen (PPK)</p>
                    <div class="h-16 flex items-end justify-center">
                        <span class="border-b border-gray-400 w-56 block"></span>
                    </div>
                    <p class="font-bold text-gray-900 mt-1">Hendra Setiawan, M.M.</p>
                    <p class="text-[9px] text-gray-500">Kasubbag Umum Kanwil DJPb Provinsi Lampung</p>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
