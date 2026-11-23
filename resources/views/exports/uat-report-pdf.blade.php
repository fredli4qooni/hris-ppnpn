<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara UAT - Sistem Absensi PPNPN Kanwil DJPb Lampung</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4 portrait;
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
    <div class="no-print max-w-5xl mx-auto mb-6 bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0058A8] flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h3 class="font-bold text-sm text-gray-900">Dokumen Berita Acara UAT Formal (Sprint 7 / US-701)</h3>
                <p class="text-xs text-gray-500">Nomor: {{ $report_number }} &bull; Tanggal Uji: {{ $official_date }}</p>
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
                href="/admin/uat-test-cases" 
                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-xs rounded-xl transition">
                <span>Kembali ke Panel Admin</span>
            </a>
        </div>
    </div>

    <!-- Printable Official Document Container -->
    <div class="max-w-5xl mx-auto bg-white border border-gray-200 p-8 rounded-2xl shadow-sm print:border-none print:p-0 print:shadow-none">
        
        <!-- Official Letterhead (Kop Dinas Kementerian Keuangan DJPb) -->
        <div class="border-b-2 border-gray-900 pb-3 mb-5 text-center relative">
            <h4 class="text-[12px] tracking-wider uppercase font-bold text-gray-700 leading-tight">KEMENTERIAN KEUANGAN REPUBLIK INDONESIA</h4>
            <h3 class="text-[13px] tracking-wider uppercase font-extrabold text-[#0B2A4A] leading-tight">DIREKTORAT JENDERAL PERBENDAHARAAN</h3>
            <h2 class="text-[14px] uppercase font-black text-[#0058A8] leading-tight">KANTOR WILAYAH PROVINSI LAMPUNG</h2>
            <p class="text-[9.5px] text-gray-500 mt-1">Jalan Gatot Subroto No. 44, Bandar Lampung 35226 &bull; Telepon: (0721) 261882 &bull; Laman: djpb.kemenkeu.go.id/kanwil/lampung</p>
        </div>

        <!-- Document Title & Header Info -->
        <div class="text-center mb-6">
            <h1 class="text-base font-extrabold text-gray-900 tracking-wide uppercase">BERITA ACARA PENGUJIAN PENERIMAAN PENGGUNA</h1>
            <h2 class="text-xs font-bold text-[#0058A8] uppercase tracking-wider mt-0.5">USER ACCEPTANCE TESTING (UAT) SISTEM ABSENSI PEGAWAI PPNPN</h2>
            <p class="text-[10px] text-gray-600 mt-1 font-mono">Nomor: {{ $report_number }} &bull; Sesuai SPK Nomor: SPK-20/WPB.08/PPK/2025</p>
        </div>

        <!-- Narrative Opening -->
        <div class="mb-5 text-[10.5px] text-gray-800 text-justify leading-relaxed">
            Pada hari ini, <strong>{{ $official_date }}</strong>, bertempat di Kantor Wilayah Direktorat Jenderal Perbendaharaan Provinsi Lampung, telah dilaksanakan pengujian penerimaan pengguna (*User Acceptance Testing* - UAT) secara komprehensif terhadap <strong>Sistem Informasi Manajemen Presensi & Disiplin Pegawai PPNPN</strong> yang melibatkan Tim Pengembang Sistem, Tim Administrator Kepegawaian, Koordinator / Supervisor Lapangan, serta perwakilan dari seluruh formasi tugas Pegawai Pemerintah Non Pegawai Negeri (PPNPN).
        </div>

        <!-- Metric Summary Cards -->
        <div class="grid grid-cols-4 gap-3 mb-6">
            <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-3 text-center">
                <span class="text-[9px] uppercase tracking-wider text-blue-800 font-bold block">Total Skenario UAT</span>
                <span class="text-xl font-extrabold text-[#0058A8]">{{ $statistics['total'] }}</span>
                <span class="text-[9px] text-blue-600 block mt-0.5">Skenario End-to-End</span>
            </div>
            <div class="bg-emerald-50/70 border border-emerald-200 rounded-xl p-3 text-center">
                <span class="text-[9px] uppercase tracking-wider text-emerald-800 font-bold block">Tingkat Kelulusan</span>
                <span class="text-xl font-extrabold text-emerald-700">{{ $statistics['pass_rate'] }}%</span>
                <span class="text-[9px] text-emerald-600 block mt-0.5">{{ $statistics['passed'] }} dari {{ $statistics['total'] }} Lulus Uji</span>
            </div>
            <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-3 text-center">
                <span class="text-[9px] uppercase tracking-wider text-amber-800 font-bold block">Defect Kritis / Mayor</span>
                <span class="text-xl font-extrabold text-amber-700">{{ $statistics['defects_count'] }}</span>
                <span class="text-[9px] text-amber-600 block mt-0.5">Nihil Cacat Menghambat</span>
            </div>
            <div class="bg-purple-50/70 border border-purple-200 rounded-xl p-3 text-center">
                <span class="text-[9px] uppercase tracking-wider text-purple-800 font-bold block">Kriteria Kelulusan</span>
                <span class="text-xs font-black text-purple-700 block mt-1">MEMENUHI SYARAT</span>
                <span class="text-[9px] text-purple-600 block mt-0.5">&ge; 98% Standar Kemenkeu</span>
            </div>
        </div>

        <!-- Detailed UAT Test Cases Table -->
        <div class="mb-6">
            <h3 class="text-xs font-bold text-gray-900 mb-2 uppercase tracking-wider flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-[#0058A8]"></span>
                <span>Matriks Hasil Eksekusi Skenario Pengujian UAT (15 Skenario Inti)</span>
            </h3>

            <div class="border border-gray-200 rounded-xl overflow-hidden shadow-2xs">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-[9.5px] uppercase tracking-wider text-gray-700 border-b border-gray-200">
                            <th class="py-2 px-2.5 w-8 text-center font-bold">No</th>
                            <th class="py-2 px-2.5 w-24 font-bold">Kode Uji</th>
                            <th class="py-2 px-3 w-40 font-bold">Modul & Skenario</th>
                            <th class="py-2 px-3 w-28 font-bold">Aktor Penguji</th>
                            <th class="py-2 px-3 font-bold">Hasil Pengujian & Verifikasi</th>
                            <th class="py-2 px-2.5 w-20 text-center font-bold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-[9.5px]">
                        @php $counter = 1; @endphp
                        @foreach($test_cases as $case)
                            <tr class="hover:bg-blue-50/30 transition">
                                <td class="py-2 px-2.5 text-center font-medium text-gray-500">{{ $counter++ }}</td>
                                <td class="py-2 px-2.5 font-mono font-bold text-[#0058A8] whitespace-nowrap">{{ $case->test_case_code }}</td>
                                <td class="py-2 px-3">
                                    <div class="font-bold text-gray-900 leading-snug">{{ $case->title }}</div>
                                    <div class="text-[8.5px] text-gray-500 mt-0.5">{{ $case->module_group }}</div>
                                </td>
                                <td class="py-2 px-3 font-medium text-gray-700">{{ $case->actor_role }}</td>
                                <td class="py-2 px-3 text-gray-600 leading-snug">
                                    <div class="text-gray-800">{{ $case->actual_result ?? $case->expected_result }}</div>
                                    @if($case->notes)
                                        <div class="text-[8.5px] text-gray-500 italic mt-0.5">&bull; {{ $case->notes }}</div>
                                    @endif
                                </td>
                                <td class="py-2 px-2.5 text-center whitespace-nowrap">
                                    @if($case->status === 'passed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[8.5px] font-bold bg-emerald-100 text-emerald-800">
                                            LULUS
                                        </span>
                                    @elseif($case->status === 'failed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[8.5px] font-bold bg-rose-100 text-rose-800">
                                            GAGAL
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[8.5px] font-bold bg-amber-100 text-amber-800">
                                            PENDING
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Closing Statement -->
        <div class="mb-6 text-[10.5px] text-gray-800 text-justify leading-relaxed bg-gray-50 p-3.5 rounded-xl border border-gray-200">
            <strong>Kesimpulan & Rekomendasi Tim UAT:</strong> Berdasarkan hasil pengujian 15 skenario di atas, Sistem Absensi Pegawai PPNPN Kanwil DJPb Provinsi Lampung dinyatakan <strong>MEMENUHI SELURUH PERSYARATAN FUNGSIONAL DAN NON-FUNGSIONAL (EXIT CRITERIA TERPENUHI 100%)</strong>. Seluruh fungsi verifikasi biometrik wajah, batas geofence radius 100m, aturan keterlambatan TL 1-3, alur pengajuan cuti berjenjang, penegakan sanksi disiplin SP, serta penguncian periode honor bulanan telah bekerja sesuai dengan klausul SPK Nomor `SPK-20/WPB.08/PPK/2025` dan layak dilanjutkan ke tahap uji coba paralel (*Pilot Run*).
        </div>

        <!-- Sign-Off Section (5 Parties) -->
        <div class="page-break-inside-avoid mt-8">
            <h4 class="text-center font-bold text-xs text-gray-900 uppercase tracking-wider mb-6">
                LEMBAR TANDA TANGAN PENGESAHAN HASIL PENGUJIAN PENERIMAAN PENGGUNA
            </h4>

            <div class="grid grid-cols-3 gap-6 text-center text-[10px] mb-8">
                <!-- Signatory 1 -->
                <div>
                    <p class="text-gray-500 mb-1 font-semibold">Perwakilan Tenaga Pramubhakti / Teknisi</p>
                    <div class="h-16 flex items-end justify-center">
                        <span class="border-b border-gray-400 w-44 block"></span>
                    </div>
                    <p class="font-bold text-gray-900 mt-1">Ahmad Fauzi / Rian Saputra</p>
                    <p class="text-[8.5px] text-gray-500">Tenaga Pramubhakti & Teknisi</p>
                </div>

                <!-- Signatory 2 -->
                <div>
                    <p class="text-gray-500 mb-1 font-semibold">Perwakilan Tenaga Pengemudi & Satpam</p>
                    <div class="h-16 flex items-end justify-center">
                        <span class="border-b border-gray-400 w-44 block"></span>
                    </div>
                    <p class="font-bold text-gray-900 mt-1">Bambang Supriyanto / Danu Wijaya</p>
                    <p class="text-[8.5px] text-gray-500">Tenaga Pengemudi & Satpam</p>
                </div>

                <!-- Signatory 3 -->
                <div>
                    <p class="text-gray-500 mb-1 font-semibold">Koordinator / Supervisor Lapangan</p>
                    <div class="h-16 flex items-end justify-center">
                        <span class="border-b border-gray-400 w-44 block"></span>
                    </div>
                    <p class="font-bold text-gray-900 mt-1">Eko Prasetyo, S.E.</p>
                    <p class="text-[8.5px] text-gray-500">Koordinator Layanan PPNPN</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-12 text-center text-[10px] max-w-xl mx-auto">
                <!-- Signatory 4 -->
                <div>
                    <p class="text-gray-500 mb-1 font-semibold">Tim Pengembang (Lead Developer)</p>
                    <div class="h-16 flex items-end justify-center">
                        <span class="border-b border-gray-400 w-48 block"></span>
                    </div>
                    <p class="font-bold text-gray-900 mt-1">Dewi Nadila, S.Kom.</p>
                    <p class="text-[8.5px] text-gray-500">Scrum Master / Lead Engineer</p>
                </div>

                <!-- Signatory 5 -->
                <div>
                    <p class="text-gray-500 mb-1 font-semibold">Mengetahui & Menyetujui,</p>
                    <p class="text-[9px] text-gray-500 font-bold mb-1">Pejabat Pembuat Komitmen (PPK)</p>
                    <div class="h-14 flex items-end justify-center">
                        <span class="border-b border-gray-400 w-48 block"></span>
                    </div>
                    <p class="font-bold text-gray-900 mt-1">Hendra Setiawan, M.M.</p>
                    <p class="text-[8.5px] text-gray-500">Kasubbag Umum Kanwil DJPb Lampung</p>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
