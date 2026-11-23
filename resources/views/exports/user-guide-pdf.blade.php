<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Panduan Operasional HRIS PPNPN - Kanwil DJPb Lampung</title>
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
            .page-break-after {
                page-break-after: always;
            }
        }
    </style>
</head>
<body class="p-4 md:p-8 text-[11px] leading-relaxed">

    <!-- Top Action Toolbar (Screen Only) -->
    <div class="no-print max-w-4xl mx-auto mb-6 bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0058A8] flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            </div>
            <div>
                <h3 class="font-bold text-sm text-gray-900">Buku Panduan Operasional Sistem (User Manual Guide)</h3>
                <p class="text-xs text-gray-500">Nomor: {{ $documentNumber }} &bull; Tanggal: {{ $printedAt }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button 
                onclick="window.print()" 
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#0058A8] hover:bg-[#0B2A4A] text-white font-semibold text-xs rounded-xl shadow-xs transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Cetak / Simpan PDF</span>
            </button>
            <a 
                href="/mobile/guide" 
                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-xs rounded-xl transition">
                Kembali
            </a>
        </div>
    </div>

    <!-- Official Printable Document -->
    <div class="max-w-4xl mx-auto bg-white border border-gray-200 p-8 rounded-2xl shadow-sm print:border-none print:p-0 print:shadow-none space-y-6">

        <!-- Kop Dinas Kemenkeu DJPb -->
        <div class="border-b-2 border-gray-900 pb-3 text-center relative">
            <h4 class="text-[12px] tracking-wider uppercase font-bold text-gray-700 leading-tight">KEMENTERIAN KEUANGAN REPUBLIK INDONESIA</h4>
            <h3 class="text-[13px] tracking-wider uppercase font-extrabold text-[#0B2A4A] leading-tight">DIREKTORAT JENDERAL PERBENDAHARAAN</h3>
            <h2 class="text-[14px] uppercase font-black text-[#0058A8] leading-tight">KANTOR WILAYAH PROVINSI LAMPUNG</h2>
            <p class="text-[9.5px] text-gray-500 mt-1">Jalan Gatot Subroto No. 44, Bandar Lampung 35226 &bull; Telepon: (0721) 261882</p>
        </div>

        <!-- Title -->
        <div class="text-center space-y-1">
            <h1 class="text-base font-extrabold text-gray-900 tracking-wide uppercase">BUKU PANDUAN OPERASIONAL SISTEM PRESENSI & DISIPLIN</h1>
            <h2 class="text-xs font-bold text-[#0058A8] uppercase tracking-wider">PEGAWAI PEMERINTAH NON PEGAWAI NEGERI (PPNPN)</h2>
            <p class="text-[10px] text-gray-500 font-mono">Dokumen Nomor: {{ $documentNumber }} &bull; Acuan SPK: SPK-20/WPB.08/PPK/2025</p>
        </div>

        <!-- Chapter 1: Gambaran Umum -->
        <section class="space-y-2">
            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider border-b border-gray-200 pb-1 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#0058A8]"></span>
                <span>BAB I: Gambaran Umum Sistem & Persyaratan Perangkat</span>
            </h3>
            <p class="text-justify text-gray-700">
                Aplikasi Sistem Informasi Presensi PPNPN Kanwil DJPb Provinsi Lampung dibangun untuk mewujudkan pencatatan kehadiran yang akuntabel, transparan, dan objektif berbasis biometrik wajah serta koordinat GPS. Seluruh pegawai PPNPN (Pramubhakti, Pengemudi, Resepsionis, Teknisi, dan Satpam) mengakses sistem melalui peramban ponsel pintar (*Progressive Web App - PWA*).
            </p>
            <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-[10.5px] space-y-1">
                <strong>Persyaratan Teknis Minimal:</strong>
                <ul class="list-disc list-inside text-gray-600">
                    <li>Ponsel pintar dengan kamera depan fungsional dan sensor GPS/Lokasi aktif.</li>
                    <li>Peramban modern (Google Chrome, Safari, atau Mozilla Firefox) dengan izin akses kamera dan lokasi aktif.</li>
                    <li>Koneksi internet (data seluler atau Wi-Fi kantor Kanwil DJPb Lampung).</li>
                </ul>
            </div>
        </section>

        <!-- Chapter 2: Presensi Biometrik Wajah & Geofence -->
        <section class="space-y-2">
            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider border-b border-gray-200 pb-1 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#0058A8]"></span>
                <span>BAB II: Tata Laksana Presensi Wajah & Geofencing GPS (Radius 100m)</span>
            </h3>
            <ol class="list-decimal list-inside space-y-1.5 text-gray-700 text-justify">
                <li>Buka aplikasi di alamat <code>https://presensi.djpblampung.id</code> dan login menggunakan NIP/Username serta kata sandi.</li>
                <li>Pada menu <strong>Presensi</strong>, pilih formasi tugas dinas Anda hari ini (FR-201).</li>
                <li>Pastikan Anda berada di dalam radius 100 meter dari kantor. Tombol presensi otomatis terkunci jika Anda berada di luar perimeter.</li>
                <li>Tekan tombol <strong>Aktifkan Kamera Depan</strong>, arahkan wajah tepat di dalam bingkai oval di layar, dan ikuti instruksi <em>Active Liveness</em> (tolehkan kepala atau kedipkan mata) guna membuktikan kehadiran manusia asli.</li>
                <li>Setelah tanda centang hijau muncul, tekan tombol <strong>Kirim Presensi</strong>. Jam server resmi (WIB) akan dicatat sebagai waktu hadir Anda.</li>
            </ol>
        </section>

        <!-- Chapter 3: Jam Kerja & Toleransi 15 Menit -->
        <section class="space-y-2">
            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider border-b border-gray-200 pb-1 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#0058A8]"></span>
                <span>BAB III: Jam Dinas, Shift Satpam, & Toleransi Keterlambatan (FR-207)</span>
            </h3>
            <p class="text-justify text-gray-700">
                Sesuai Lampiran IV SPK, pegawai yang hadir terlambat <strong>$\le$ 15 menit</strong> mendapatkan fasilitas kompensasi toleransi dengan kewajiban mengganti waktu kepulangan sejumlah menit keterlambatan tersebut pada hari yang sama.
            </p>
            <div class="border border-gray-200 rounded-xl overflow-hidden shadow-2xs">
                <table class="w-full text-left border-collapse text-[10.5px]">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="py-2 px-3">Klasifikasi Keterlambatan</th>
                            <th class="py-2 px-3">Rentang Waktu Keterlambatan</th>
                            <th class="py-2 px-3 text-right">Potongan Honor Harian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="py-1.5 px-3 font-semibold text-emerald-800">Toleransi Lunas</td>
                            <td class="py-1.5 px-3">$\le$ 15 Menit (Diganti saat pulang)</td>
                            <td class="py-1.5 px-3 text-right font-bold text-emerald-600">0,0% (Bebas Potongan)</td>
                        </tr>
                        <tr>
                            <td class="py-1.5 px-3 font-semibold text-amber-800">Tingkat TL 1</td>
                            <td class="py-1.5 px-3">1 s.d. 30 Menit (Tanpa kompensasi)</td>
                            <td class="py-1.5 px-3 text-right font-bold text-rose-600">0,5% / Hari</td>
                        </tr>
                        <tr>
                            <td class="py-1.5 px-3 font-semibold text-amber-800">Tingkat TL 2</td>
                            <td class="py-1.5 px-3">31 s.d. 60 Menit</td>
                            <td class="py-1.5 px-3 text-right font-bold text-rose-600">1,0% / Hari</td>
                        </tr>
                        <tr>
                            <td class="py-1.5 px-3 font-semibold text-rose-800">Tingkat TL 3</td>
                            <td class="py-1.5 px-3">> 60 Menit</td>
                            <td class="py-1.5 px-3 text-right font-bold text-rose-600">1,5% / Hari</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-[10px] text-gray-500 italic">
                * Catatan Khusus Shift Satpam Malam (19:00 - 07:00 WIB): Presensi kepulangan di pagi hari esoknya otomatis terhubung ke check-in malam hari sebelumnya.
            </p>
        </section>

        <!-- Chapter 4: Kondisi Darurat (Fallback & Lupa Absen) -->
        <section class="space-y-2">
            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider border-b border-gray-200 pb-1 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#0058A8]"></span>
                <span>BAB IV: Penanganan Darurat Fallback Swafoto & Koreksi Lupa Absen</span>
            </h3>
            <ul class="list-disc list-inside space-y-1.5 text-gray-700 text-justify">
                <li><strong>Fallback Swafoto Manual (FR-209):</strong> Bila sistem mendeteksi kegagalan biometrik &ge; 3 kali, tombol "Mode Darurat" terbuka. Pegawai mengambil swafoto biasa dan mencantumkan alasan. Presensi dinyatakan sah setelah diverifikasi oleh Supervisor Lapangan.</li>
                <li><strong>Koreksi Lupa Absen (FR-210):</strong> Pegawai yang lupa check-in karena tugas kedinasan mendesak wajib mengajukan form koreksi paling lambat 1x24 jam disertai bukti surat tugas atau foto dokumentasi.</li>
            </ul>
        </section>

        <!-- Chapter 5: Alur Cuti Two-Tier Approval -->
        <section class="space-y-2">
            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider border-b border-gray-200 pb-1 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#0058A8]"></span>
                <span>BAB V: Pengajuan Izin/Cuti Berjenjang (Two-Tier Approval)</span>
            </h3>
            <p class="text-justify text-gray-700">
                Pengajuan cuti tahunan (kuota 12 hari/tahun, maksimal 3 hari kerja beruntun) dan izin sakit bersurat dokter wajib melalui alur persetujuan dua tahap: <strong>Tahap 1 Verifikasi oleh Supervisor Lapangan</strong> &rarr; <strong>Tahap 2 Persetujuan Definitif oleh Kasubbag Umum / PPK</strong>. Pengajuan yang disetujui otomatis menganulir status Alpa pada rekap bulanan.
            </p>
        </section>

        <!-- Chapter 6: Buku Disiplin & Sanksi SP -->
        <section class="space-y-2">
            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider border-b border-gray-200 pb-1 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#0058A8]"></span>
                <span>BAB VI: Katalog Pelanggaran Disiplin & Akumulasi Sanksi SP</span>
            </h3>
            <p class="text-justify text-gray-700">
                Pelanggaran lapangan (Kelompok A, B, dan C) dicatat secara permanen oleh Supervisor. Akumulasi poin memicu sanksi otomatis: <strong>26-50 poin = SP 1 (Potongan 5% selama 1 bulan)</strong>; <strong>51-75 poin = SP 2 (Potongan 7,5% selama 3 bulan)</strong>; <strong>&ge; 76 poin = SP 3 (Potongan 10% / Rekomendasi Pemutusan Kontrak)</strong>. Seluruh saldo poin dapat dipantau mandiri di aplikasi mobile.
            </p>
        </section>

        <!-- Sign-Off Section -->
        <div class="page-break-inside-avoid pt-6 border-t border-gray-300">
            <div class="grid grid-cols-2 gap-10 text-center text-[10.5px]">
                <div>
                    <p class="text-gray-500 font-semibold mb-1">Disusun Oleh,</p>
                    <p class="text-gray-700 font-bold mb-1">Tim Pengembang (Lead Developer)</p>
                    <div class="h-14 flex items-end justify-center">
                        <span class="border-b border-gray-400 w-48 block"></span>
                    </div>
                    <p class="font-bold text-gray-900 mt-1">Dewi Nadila, S.Kom.</p>
                    <p class="text-[9px] text-gray-500">Scrum Master / Lead Engineer</p>
                </div>

                <div>
                    <p class="text-gray-500 font-semibold mb-1">Disahkan Oleh,</p>
                    <p class="text-gray-700 font-bold mb-1">Pejabat Pembuat Komitmen (PPK)</p>
                    <div class="h-14 flex items-end justify-center">
                        <span class="border-b border-gray-400 w-48 block"></span>
                    </div>
                    <p class="font-bold text-gray-900 mt-1">Hendra Setiawan, M.M.</p>
                    <p class="text-[9px] text-gray-500">Kasubbag Umum Kanwil DJPb Lampung</p>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
