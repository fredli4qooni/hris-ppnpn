<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Top Overview Banner -->
        <div class="p-6 rounded-2xl bg-gradient-to-r from-[#0B2A4A] via-[#0058A8] to-[#0B2A4A] text-white shadow-sm space-y-3 relative overflow-hidden">
            <div class="relative z-10 max-w-2xl space-y-1.5">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#F8B810] text-[#0B2A4A]">
                    Pedoman Operasional Backoffice & Pengawasan
                </span>
                <h2 class="text-xl font-extrabold tracking-wide">Manual Book Sistem Presensi & Disiplin Pegawai PPNPN</h2>
                <p class="text-xs text-blue-100 leading-relaxed">
                    Panduan komprehensif bagi Administrator, Koordinator / Supervisor Lapangan, dan Kasubbag Umum / PPK dalam mengoperasikan seluruh modul tata kelola kehadiran sesuai SPK Nomor <code>SPK-20/WPB.08/PPK/2025</code>.
                </p>
            </div>
            
            <div class="relative z-10 pt-2 flex items-center gap-3">
                <a 
                    href="{{ route('mobile.guide.download-pdf') }}" 
                    target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-white text-[#0058A8] hover:bg-blue-50 font-bold text-xs rounded-xl shadow-xs transition"
                >
                    <svg class="w-4 h-4 text-[#0058A8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Unduh Manual Book Resmi (Format PDF)</span>
                </a>
            </div>

            <div class="absolute -right-8 -bottom-10 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
        </div>

        <!-- 3 Role-Based Interactive Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Role 1: Supervisor -->
            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-xs space-y-4">
                <div class="flex items-center space-x-3 border-b border-gray-100 pb-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Peran Operasional</span>
                        <h3 class="font-extrabold text-sm text-gray-900">Supervisor Lapangan</h3>
                    </div>
                </div>

                <div class="space-y-2 text-xs text-gray-600 leading-relaxed">
                    <p class="font-medium text-gray-800">Tugas & Alur Kerja Utama:</p>
                    <ul class="space-y-2 list-disc list-inside">
                        <li><strong>Supervisor Command Center:</strong> Memantau live feed kehadiran anggota tim harian, badge merah keterlambatan, dan daftar yang belum absen.</li>
                        <li><strong>Verifikasi Cuti Tahap 1:</strong> Menelaah ketersediaan pengganti dan beban kerja sebelum pengajuan diteruskan ke Atasan.</li>
                        <li><strong>Verifikasi Swafoto Darurat:</strong> Menyetujui atau menolak swafoto fallback pegawai yang mengalami kendala biometrik wajah.</li>
                        <li><strong>Pencatatan Disiplin :</strong> Merekam temuan pelanggaran (A, B, C) disertai unggahan bukti foto lapangan.</li>
                    </ul>
                </div>
            </div>

            <!-- Role 2: Kasubbag Umum / PPK -->
            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-xs space-y-4">
                <div class="flex items-center space-x-3 border-b border-gray-100 pb-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0058A8] flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-[#0058A8] uppercase tracking-wider block">Otoritas Pimpinan</span>
                        <h3 class="font-extrabold text-sm text-gray-900">Kasubbag Umum / PPK</h3>
                    </div>
                </div>

                <div class="space-y-2 text-xs text-gray-600 leading-relaxed">
                    <p class="font-medium text-gray-800">Tugas & Alur Kerja Utama:</p>
                    <ul class="space-y-2 list-disc list-inside">
                        <li><strong>Persetujuan Akhir Cuti (Tahap 2):</strong> Menetapkan keputusan resmi izin/cuti tahunan dan dispensasi musibah kahar.</li>
                        <li><strong>Dasbor Eksekutif Pimpinan:</strong> Memantau radar kepatuhan kantor, tren jam kerja, dan peringatan masa kontrak pegawai.</li>
                        <li><strong>Penetapan Sanksi SP:</strong> Mengesahkan Surat Peringatan (SP 1-3) berdasar akumulasi poin pelanggaran disiplin.</li>
                        <li><strong>Otorisasi Penggajian Bulanan:</strong> Memverifikasi rekapitulasi potongan honorarium sebelum diajukan ke SPM/SP2D.</li>
                    </ul>
                </div>
            </div>

            <!-- Role 3: Administrator -->
            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-xs space-y-4">
                <div class="flex items-center space-x-3 border-b border-gray-100 pb-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-purple-700 uppercase tracking-wider block">Pengelolaan Teknis</span>
                        <h3 class="font-extrabold text-sm text-gray-900">Administrator Sistem</h3>
                    </div>
                </div>

                <div class="space-y-2 text-xs text-gray-600 leading-relaxed">
                    <p class="font-medium text-gray-800">Tugas & Alur Kerja Utama:</p>
                    <ul class="space-y-2 list-disc list-inside">
                        <li><strong>Face Enrollment:</strong> Mendaftarkan sampel biometrik wajah pegawai dan memastikan enkripsi AES-256 tersimpan aman (UU PDP).</li>
                        <li><strong>Monthly Closing Lock:</strong> Mengunci seluruh rekor absensi dan mutasi data setiap akhir bulan menjadi Read-Only permanen.</li>
                        <li><strong>Jejak Audit Sistem:</strong> Memantau histori mutasi data sensitif yang tidak dapat diubah maupun dihapus.</li>
                        <li><strong>Ekspor Dokumen Resmi:</strong> Menerbitkan rekapitulasi penggajian format XLSX dan berkas cetak PDF siap arsip.</li>
                    </ul>
                </div>
            </div>

        </div>

        <!-- System Architecture & Security Notice -->
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5 space-y-2 text-xs text-blue-950">
            <h4 class="font-bold uppercase tracking-wider flex items-center gap-1.5 text-xs text-[#0058A8]">
                <svg class="w-4 h-4 text-[#0058A8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Kepatuhan Standar CSIRT Kementerian Keuangan & UU No. 27/2022</span>
            </h4>
            <p class="leading-relaxed text-gray-700">
                Sistem presensi telah memenuhi standar keamanan siber: proteksi rate limiting bertingkat (5 req/menit untuk login, 30 req/menit untuk presensi), penguncian otomatis percobaan brute-force biometrik ($\ge 5$ kegagalan), enkripsi simetris AES-256 pada vektor wajah, serta sanitasi EXIF pada seluruh lampiran foto lapangan.
            </p>
        </div>

    </div>
</x-filament-panels::page>
