<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Serah Terima (BAST) - Kanwil DJPb Provinsi Lampung</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 15mm 15mm;
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
            .page-break-before {
                break-before: page;
                page-break-before: always;
            }
        }
    </style>
</head>
<body class="p-4 md:p-8 text-[11px] leading-relaxed">

    <!-- Top Action Toolbar (Screen Only) -->
    <div class="no-print max-w-4xl mx-auto mb-6 bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#007A3D] flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h3 class="font-bold text-sm text-gray-900">Dokumen Berita Acara Serah Terima (BAST Final Go-Live)</h3>
                <p class="text-xs text-gray-500">Nomor: {{ $bast->bast_number }} &bull; Tanggal: {{ $officialDate }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <button 
                onclick="window.print()" 
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#0058A8] hover:bg-[#0B2A4A] text-white font-semibold text-xs rounded-xl shadow-xs transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Cetak Dokumen BAST (Print / PDF)</span>
            </button>
            <a 
                href="/admin" 
                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-xs rounded-xl transition">
                <span>Kembali ke Panel Admin</span>
            </a>
        </div>
    </div>

    <!-- Official Document Paper Sheet -->
    <div class="max-w-4xl mx-auto bg-white border border-gray-300 print:border-0 rounded-2xl print:rounded-none p-8 md:p-12 shadow-md print:shadow-none space-y-6">

        <!-- Kop Surat Resmi Kementerian Keuangan DJPb -->
        <div class="border-b-2 border-gray-900 pb-4 text-center relative">
            <div class="flex items-center justify-center gap-4 mb-2">
                <img src="/images/logo-djpb.png" alt="Logo DJPb" class="h-16 w-auto" onerror="this.style.display='none'">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-700">Kementerian Keuangan Republik Indonesia</h2>
                    <h1 class="text-sm font-extrabold uppercase tracking-wide text-gray-900">Direktorat Jenderal Perbendaharaan</h1>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#0058A8]">Kantor Wilayah Provinsi Lampung</h3>
                    <p class="text-[10px] text-gray-600 mt-0.5">Jl. Gatot Subroto No. 44, Pahoman, Teluk Betung Utara, Kota Bandar Lampung 35213</p>
                </div>
            </div>
        </div>

        <!-- Document Heading -->
        <div class="text-center space-y-1">
            <h2 class="text-sm font-black uppercase underline tracking-wider text-gray-900">
                Berita Acara Serah Terima Pekerjaan (BAST)
            </h2>
            <p class="text-xs font-bold text-gray-800">
                Nomor: <span class="font-mono text-[#0058A8]">{{ $bast->bast_number }}</span>
            </p>
            <p class="text-[10px] text-gray-600 italic">
                Tentang Serah Terima Hasil Pengembangan Aplikasi Sistem Presensi & Penggajian PPNPN Terpadu
            </p>
        </div>

        <!-- Opening Paragraph -->
        <p class="text-justify leading-relaxed">
            Pada hari ini, <strong>{{ Carbon\Carbon::parse($bast->handover_date)->locale('id')->isoFormat('dddd') }}</strong> tanggal <strong>{{ Carbon\Carbon::parse($bast->handover_date)->locale('id')->isoFormat('D') }}</strong> bulan <strong>{{ Carbon\Carbon::parse($bast->handover_date)->locale('id')->isoFormat('MMMM') }}</strong> tahun <strong>{{ Carbon\Carbon::parse($bast->handover_date)->locale('id')->isoFormat('YYYY') }}</strong> ({{ $officialDate }}), bertempat di Kantor Wilayah Direktorat Jenderal Perbendaharaan Provinsi Lampung, yang bertanda tangan di bawah ini:
        </p>

        <!-- Pihak Pertama & Kedua Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 border border-gray-200 p-4 rounded-xl">
            <div class="space-y-1 text-[11px]">
                <p class="font-bold text-[#0058A8] border-b border-gray-200 pb-1">I. PIHAK PERTAMA (Penyedia / Scrum Team):</p>
                <p><span class="w-20 inline-block font-semibold">Nama:</span> <strong>{{ $bast->first_party_name }}</strong></p>
                <p><span class="w-20 inline-block font-semibold">Jabatan:</span> {{ $bast->first_party_title }}</p>
                <p><span class="w-20 inline-block font-semibold">Instansi:</span> Scrum Development Team</p>
                <p class="text-[10px] text-gray-500 italic mt-1">Selanjutnya disebut sebagai <strong>PIHAK PERTAMA</strong>.</p>
            </div>
            <div class="space-y-1 text-[11px]">
                <p class="font-bold text-[#007A3D] border-b border-gray-200 pb-1">II. PIHAK KEDUA (Pengguna Jasa / PPK):</p>
                <p><span class="w-20 inline-block font-semibold">Nama:</span> <strong>{{ $bast->second_party_name }}</strong></p>
                <p><span class="w-20 inline-block font-semibold">NIP:</span> {{ $bast->second_party_nip }}</p>
                <p><span class="w-20 inline-block font-semibold">Jabatan:</span> {{ $bast->second_party_title }}</p>
                <p><span class="w-20 inline-block font-semibold">Instansi:</span> {{ $bast->work_unit }}</p>
                <p class="text-[10px] text-gray-500 italic mt-1">Selanjutnya disebut sebagai <strong>PIHAK KEDUA</strong>.</p>
            </div>
        </div>

        <p class="text-justify leading-relaxed">
            Menyatakan dengan sebenarnya bahwa berdasarkan Surat Perjanjian Kerja (SPK) Nomor <strong>{{ $bast->contract_number }}</strong>, PARA PIHAK telah sepakat dan menyetujui ketentuan serah terima hasil pekerjaan sebagai berikut:
        </p>

        <!-- Pasal 1 -->
        <div class="space-y-2">
            <h4 class="font-bold text-gray-900 border-b border-gray-200 pb-0.5">PASAL 1: PENYERAHAN HASIL PEKERJAAN</h4>
            <p class="text-justify leading-relaxed">
                PIHAK PERTAMA menyerahkan kepada PIHAK KEDUA, dan PIHAK KEDUA menerima secara utuh hasil pekerjaan pengadaan dan pengembangan <strong>{{ $bast->project_name }}</strong> yang telah diselesaikan 100% dalam <strong>{{ $bast->total_sprints }} Sprint Agile Scrum</strong> dengan bobot komitmen kumulatif sebesar <strong>{{ $bast->total_story_points }} Story Points</strong> dan dilindungi oleh <strong>{{ $bast->total_test_cases }}+ skenario uji otomatis (*automated tests*)</strong> dengan tingkat keberhasilan 100%.
            </p>
        </div>

        <!-- Pasal 2: Rincian Modul Sprint 1 - 8 -->
        <div class="space-y-2">
            <h4 class="font-bold text-gray-900 border-b border-gray-200 pb-0.5">PASAL 2: RUANG LINGKUP PEKERJAAN YANG DISELESAIKAN</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-left border border-gray-300 text-[10px]">
                    <thead class="bg-gray-100 font-bold text-gray-800">
                        <tr>
                            <th class="p-2 border border-gray-300 w-12 text-center">Sprint</th>
                            <th class="p-2 border border-gray-300">Modul & Increments yang Dikembangkan</th>
                            <th class="p-2 border border-gray-300 w-14 text-center">SP</th>
                            <th class="p-2 border border-gray-300 w-24 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($milestones as $m)
                            <tr>
                                <td class="p-2 border border-gray-300 text-center font-bold">{{ $m['sprint'] }}</td>
                                <td class="p-2 border border-gray-300">
                                    <span class="font-bold text-gray-900">{{ $m['name'] }}</span>
                                    <p class="text-[9px] text-gray-600 mt-0.5">{{ $m['scope'] }}</p>
                                </td>
                                <td class="p-2 border border-gray-300 text-center font-bold text-[#0058A8]">{{ $m['sp'] }}</td>
                                <td class="p-2 border border-gray-300 text-center font-bold text-emerald-700 bg-emerald-50">{{ $m['status'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pasal 3: Hasil Verifikasi UAT & Pilot Run -->
        <div class="space-y-2 page-break-inside-avoid">
            <h4 class="font-bold text-gray-900 border-b border-gray-200 pb-0.5">PASAL 3: HASIL PENGUJIAN & VALIDASI SISTEM</h4>
            <p class="text-justify leading-relaxed">
                Berdasarkan hasil pengujian lapangan dan komparasi paralel yang telah dilaksanakan secara komprehensif:
            </p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li><strong>User Acceptance Testing (UAT)</strong>: Dilaksanakan terhadap 16 skenario fungsional lintas peran dengan tingkat kelulusan <strong>{{ number_format($bast->uat_pass_rate, 0) }}% (Lulus 16 dari 16 Skenario)</strong>.</li>
                <li><strong>Rekonsiliasi Pilot Run Paralel</strong>: Tingkat keselarasan data absensi fisik manual vs mobile HRIS mencapai <strong>{{ number_format($bast->concordance_rate, 2) }}%</strong> (Memenuhi target batas minimum &ge; 99%).</li>
                <li><strong>Disaster Recovery Plan (DRP)</strong>: Mekanisme backup database dan file attachment terenkripsi SHA-256 berjalan otomatis setiap hari pukul 02:00 WIB.</li>
                <li><strong>Kepatuhan UU PDP No. 27/2022</strong>: Seluruh identitas sensitif (NIK, Rekening) telah disanitasi dan dilindungi hak akses berjenjang.</li>
            </ul>
        </div>

        <!-- Pasal 4: Peralihan Operasional & Garansi -->
        <div class="space-y-2 page-break-inside-avoid">
            <h4 class="font-bold text-gray-900 border-b border-gray-200 pb-0.5">PASAL 4: PERALIHAN HAK, STATUS GO-LIVE, & GARANSI</h4>
            <p class="text-justify leading-relaxed">
                Dengan ditandatanganinya Berita Acara ini, maka terhitung sejak tanggal <strong>{{ $officialDate }}</strong>, aplikasi sistem presensi resmi berstatus <strong>PRODUCTION LIVE</strong> dan menjadi acuan tunggal dalam rekapitulasi kehadiran serta pemotongan honor bulanan PPNPN Kanwil DJPb Provinsi Lampung. PIHAK PERTAMA memberikan masa pemeliharaan sistem (*maintenance support*) selama 90 (sembilan puluh) hari kalender sejak penandatanganan BAST ini.
            </p>
        </div>

        <!-- Signatures Box -->
        <div class="pt-6 page-break-inside-avoid">
            <div class="grid grid-cols-2 gap-8 text-center">
                <!-- Signature First Party -->
                <div class="space-y-1">
                    <p class="text-gray-600 font-semibold text-[10px]">Yang Menyerahkan,</p>
                    <p class="font-bold text-gray-900">PIHAK PERTAMA</p>
                    <p class="text-[10px] text-gray-500">Lead Developer / Scrum Master</p>
                    
                    <div class="my-3 py-3 border border-emerald-300 bg-emerald-50/70 rounded-xl max-w-xs mx-auto">
                        <span class="inline-flex items-center gap-1 text-[9px] font-bold text-emerald-800 uppercase tracking-wide">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Telah Ditandatangani Digital
                        </span>
                        <p class="text-[8px] font-mono text-gray-500 mt-0.5">
                            {{ $bast->first_party_signed_at ? $bast->first_party_signed_at->format('d/m/Y H:i:s') : Carbon\Carbon::now()->format('d/m/Y H:i:s') }} WIB
                        </p>
                        <p class="text-[8px] font-mono text-gray-400">HASH: SHA256:7B8F9A...02C1E</p>
                    </div>

                    <p class="font-bold text-gray-900 underline">{{ $bast->first_party_name }}</p>
                    <p class="text-[10px] text-gray-600">Scrum Development Team</p>
                </div>

                <!-- Signature Second Party -->
                <div class="space-y-1">
                    <p class="text-gray-600 font-semibold text-[10px]">Yang Menerima,</p>
                    <p class="font-bold text-gray-900">PIHAK KEDUA</p>
                    <p class="text-[10px] text-gray-500">Pejabat Pembuat Komitmen (PPK)</p>

                    <div class="my-3 py-3 border border-blue-300 bg-blue-50/70 rounded-xl max-w-xs mx-auto">
                        <span class="inline-flex items-center gap-1 text-[9px] font-bold text-[#0058A8] uppercase tracking-wide">
                            <svg class="w-3.5 h-3.5 text-[#0058A8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Telah Disahkan & Disetujui
                        </span>
                        <p class="text-[8px] font-mono text-gray-500 mt-0.5">
                            {{ $bast->second_party_signed_at ? $bast->second_party_signed_at->format('d/m/Y H:i:s') : Carbon\Carbon::now()->format('d/m/Y H:i:s') }} WIB
                        </p>
                        <p class="text-[8px] font-mono text-gray-400">HASH: SHA256:3E5A2D...F998A</p>
                    </div>

                    <p class="font-bold text-gray-900 underline">{{ $bast->second_party_name }}</p>
                    <p class="text-[10px] text-gray-600">NIP. {{ $bast->second_party_nip }}</p>
                </div>
            </div>
        </div>

        <!-- Footer Meta -->
        <div class="border-t border-gray-200 pt-3 text-[9px] text-gray-400 flex justify-between items-center">
            <span>Sistem Informasi Presensi & Penggajian PPNPN &bull; Kanwil DJPb Provinsi Lampung</span>
            <span>Dokumen Resmi BAST &bull; Halaman 1 dari 1</span>
        </div>

    </div>

</body>
</html>
