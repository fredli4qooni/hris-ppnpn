<x-layouts.mobile title="Buku Panduan Operasional">
    <div 
        x-data="{ 
            activeTab: 'biometric',
            activeFaq: null,
            searchQuery: ''
        }" 
        class="space-y-4"
    >
        <!-- Header Hero Banner -->
        <div 
            class="rounded-3xl p-5 shadow-sm space-y-3 relative overflow-hidden text-white"
            style="background: linear-gradient(135deg, #0B2A4A 0%, #0058A8 50%, #0B2A4A 100%); color: #ffffff;"
        >
            <div class="relative z-10 space-y-1">
                <span 
                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide shadow-xs"
                    style="background-color: #F8B810; color: #0B2A4A;"
                >
                    SOP & Manual Book Resmi
                </span>
                <h2 class="text-base font-extrabold tracking-wide" style="color: #ffffff;">Panduan Operasional Presensi & Disiplin</h2>
                <p class="text-[11px] leading-relaxed" style="color: rgba(255, 255, 255, 0.9);">
                    Pedoman lengkap tata cara kehadiran mobile, toleransi jam kerja, perizinan, dan penegakan disiplin SPK Kanwil DJPb Lampung.
                </p>
            </div>

            <div class="relative z-10 pt-1 flex items-center gap-2">
                <a 
                    href="{{ route('mobile.guide.download-pdf') }}" 
                    target="_blank"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 font-bold text-xs rounded-xl shadow-xs transition hover:opacity-90"
                    style="background-color: #ffffff; color: #0058A8;"
                >
                    <svg class="w-4 h-4" style="color: #0058A8;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Unduh Manual Book (PDF)</span>
                </a>
                <a 
                    href="{{ route('mobile.home') }}" 
                    class="px-3.5 py-2 font-semibold text-xs rounded-xl transition border hover:bg-white/20"
                    style="background-color: rgba(255, 255, 255, 0.18); color: #ffffff; border-color: rgba(255, 255, 255, 0.35);"
                >
                    Kembali
                </a>
            </div>

            <!-- Background Decorative Shape -->
            <div class="absolute -right-6 -bottom-8 w-28 h-28 rounded-full pointer-events-none" style="background-color: rgba(255, 255, 255, 0.08);"></div>
        </div>

        <!-- Navigation Tabs (5 Core Modules) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-1.5 flex space-x-1 overflow-x-auto text-xs font-semibold no-scrollbar">
            <button 
                type="button" 
                @click="activeTab = 'biometric'" 
                :class="activeTab === 'biometric' ? 'bg-[#0058A8] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                :style="activeTab === 'biometric' ? 'background-color: #0058A8; color: #ffffff;' : ''"
                class="flex-1 py-2 px-2.5 rounded-xl transition whitespace-nowrap text-center text-[11px]"
            >
                1. Biometrik
            </button>
            <button 
                type="button" 
                @click="activeTab = 'worktime'" 
                :class="activeTab === 'worktime' ? 'bg-[#0058A8] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                :style="activeTab === 'worktime' ? 'background-color: #0058A8; color: #ffffff;' : ''"
                class="flex-1 py-2 px-2.5 rounded-xl transition whitespace-nowrap text-center text-[11px]"
            >
                2. Jam & TL
            </button>
            <button 
                type="button" 
                @click="activeTab = 'emergency'" 
                :class="activeTab === 'emergency' ? 'bg-[#0058A8] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                :style="activeTab === 'emergency' ? 'background-color: #0058A8; color: #ffffff;' : ''"
                class="flex-1 py-2 px-2.5 rounded-xl transition whitespace-nowrap text-center text-[11px]"
            >
                3. Fallback
            </button>
            <button 
                type="button" 
                @click="activeTab = 'leaves'" 
                :class="activeTab === 'leaves' ? 'bg-[#0058A8] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                :style="activeTab === 'leaves' ? 'background-color: #0058A8; color: #ffffff;' : ''"
                class="flex-1 py-2 px-2.5 rounded-xl transition whitespace-nowrap text-center text-[11px]"
            >
                4. Cuti/Kahar
            </button>
            <button 
                type="button" 
                @click="activeTab = 'discipline'" 
                :class="activeTab === 'discipline' ? 'bg-[#0058A8] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                :style="activeTab === 'discipline' ? 'background-color: #0058A8; color: #ffffff;' : ''"
                class="flex-1 py-2 px-2.5 rounded-xl transition whitespace-nowrap text-center text-[11px]"
            >
                5. Sanksi SP
            </button>
        </div>

        <!-- TAB 1: Biometrik & Liveness -->
        <div x-show="activeTab === 'biometric'" class="space-y-3.5">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3">
                <div class="flex items-center space-x-2 border-b border-gray-100 pb-2.5">
                    <span class="w-6 h-6 rounded-lg bg-blue-100 text-[#0058A8] flex items-center justify-center font-bold text-xs">1</span>
                    <h3 class="font-bold text-gray-900 text-xs uppercase tracking-wider">Pemindaian Wajah & Geofencing (FR-202, FR-203)</h3>
                </div>

                <div class="space-y-2.5 text-xs text-gray-700 leading-relaxed">
                    <p>
                        Sistem presensi menggunakan verifikasi biometrik wajah cerdas dengan deteksi keaslian hidup (*Active Liveness Detection*) guna mencegah manipulasi foto statis atau layar gawai lain.
                    </p>

                    <div class="p-3 bg-blue-50/70 rounded-xl border border-blue-200 space-y-1.5">
                        <h4 class="font-bold text-[#0058A8] text-[11px] uppercase tracking-wide">Tips Pemindaian Wajah Berhasil:</h4>
                        <ul class="list-disc list-inside space-y-1 text-gray-600 text-[11px]">
                            <li>Posisikan wajah tepat di dalam bingkai oval di layar ponsel.</li>
                            <li>Pastikan pencahayaan cukup terang (hindari silau cahaya di belakang kepala/backlight).</li>
                            <li>Lakukan interaksi liveness (tolehkan kepala perlahan atau kedipkan mata) sesuai petunjuk di layar.</li>
                            <li>Lepaskan masker penutup wajah saat proses verifikasi berlangsung.</li>
                        </ul>
                    </div>

                    <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 space-y-1">
                        <h4 class="font-bold text-emerald-800 text-[11px] uppercase tracking-wide">Batas Lokasi Kantor (Radius 100 Meter):</h4>
                        <p class="text-[11px] text-emerald-700">
                            Presensi hanya diizinkan di dalam perimeter kantor Kanwil DJPb Provinsi Lampung (maksimal 100 meter dari titik koordinat resmi). Penggunaan aplikasi Fake GPS / Mock Location otomatis diblokir sistem.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: Jam Kerja, Toleransi 15 Mnt, & TL 1-3 -->
        <div x-show="activeTab === 'worktime'" class="space-y-3.5">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3">
                <div class="flex items-center space-x-2 border-b border-gray-100 pb-2.5">
                    <span class="w-6 h-6 rounded-lg bg-blue-100 text-[#0058A8] flex items-center justify-center font-bold text-xs">2</span>
                    <h3 class="font-bold text-gray-900 text-xs uppercase tracking-wider">Jam Dinas & Kompensasi Toleransi 15 Menit (FR-207)</h3>
                </div>

                <div class="space-y-2.5 text-xs text-gray-700 leading-relaxed">
                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 space-y-1.5">
                        <h4 class="font-bold text-amber-900 text-[11px] uppercase tracking-wide">Aturan Toleransi Keterlambatan:</h4>
                        <p class="text-[11px] text-amber-800 leading-relaxed">
                            Jika Anda hadir terlambat <strong>$\le$ 15 menit</strong> dari jam masuk resmi, Anda <strong>wajib mengganti waktu kepulangan</strong> sejumlah menit keterlambatan pada hari yang sama.
                        </p>
                        <p class="text-[11px] text-amber-900 font-semibold">
                            Contoh: Masuk 07:40 WIB (telat 10 menit) &rarr; Pulang 17:10 WIB (lunas, bebas potongan 0%).
                        </p>
                    </div>

                    <h4 class="font-bold text-gray-900 text-xs mt-3">Tabel Tarif Potongan Keterlambatan (Lampiran IV SPK):</h4>
                    <div class="border border-gray-200 rounded-xl overflow-hidden shadow-2xs">
                        <table class="w-full text-left border-collapse text-[11px]">
                            <thead class="bg-gray-100 text-gray-700">
                                <tr>
                                    <th class="py-2 px-3">Kategori</th>
                                    <th class="py-2 px-3">Durasi Telat</th>
                                    <th class="py-2 px-3 text-right">Potongan Honor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr>
                                    <td class="py-2 px-3 font-bold text-amber-700">TL 1</td>
                                    <td class="py-2 px-3">1 s.d. 30 Menit</td>
                                    <td class="py-2 px-3 text-right font-mono font-bold text-rose-600">0,5% / Hari</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-bold text-amber-700">TL 2</td>
                                    <td class="py-2 px-3">31 s.d. 60 Menit</td>
                                    <td class="py-2 px-3 text-right font-mono font-bold text-rose-600">1,0% / Hari</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-bold text-rose-700">TL 3</td>
                                    <td class="py-2 px-3">> 60 Menit</td>
                                    <td class="py-2 px-3 text-right font-mono font-bold text-rose-600">1,5% / Hari</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3 bg-blue-50/80 rounded-xl border border-blue-200 text-[11px] text-blue-900">
                        <strong>Shift Malam Satpam (19:00 - 07:00 WIB):</strong> Absensi kepulangan di pagi hari esoknya otomatis terhubung ke check-in malam sebelumnya tanpa dianggap alpa.
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: Fallback Darurat & Lupa Absen -->
        <div x-show="activeTab === 'emergency'" class="space-y-3.5">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3">
                <div class="flex items-center space-x-2 border-b border-gray-100 pb-2.5">
                    <span class="w-6 h-6 rounded-lg bg-blue-100 text-[#0058A8] flex items-center justify-center font-bold text-xs">3</span>
                    <h3 class="font-bold text-gray-900 text-xs uppercase tracking-wider">Swafoto Darurat & Lupa Absen (FR-209, FR-210)</h3>
                </div>

                <div class="space-y-3 text-xs text-gray-700 leading-relaxed">
                    <div class="space-y-1.5">
                        <h4 class="font-bold text-gray-900 text-xs">A. Presensi Swafoto Manual (Fallback 3x Gagal)</h4>
                        <p class="text-[11px] text-gray-600">
                            Bila kamera perangkat rusak, pencahayaan buruk, atau pemindaian biometrik gagal &ge; 3 kali, sistem membuka tombol <strong>"Mode Darurat"</strong>. Anda dapat mengambil swafoto biasa dan menguraikan kendala yang dihadapi. Presensi berstatus *Pending* hingga diverifikasi oleh Supervisor Lapangan.
                        </p>
                    </div>

                    <div class="space-y-1.5 border-t border-gray-100 pt-2.5">
                        <h4 class="font-bold text-gray-900 text-xs">B. Permohonan Koreksi Lupa Absen (FR-210)</h4>
                        <p class="text-[11px] text-gray-600">
                            Jika Anda hadir bekerja namun lupa melakukan check-in (misal: langsung ditugaskan mengemudi mendadak ke luar kota), ajukan melalui menu <strong>Koreksi Lupa Absen</strong> disertai unggahan bukti surat tugas atau foto kegiatan dinas.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: Cuti & Dispensasi Kahar -->
        <div x-show="activeTab === 'leaves'" class="space-y-3.5">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3">
                <div class="flex items-center space-x-2 border-b border-gray-100 pb-2.5">
                    <span class="w-6 h-6 rounded-lg bg-blue-100 text-[#0058A8] flex items-center justify-center font-bold text-xs">4</span>
                    <h3 class="font-bold text-gray-900 text-xs uppercase tracking-wider">Cuti, Sakit, & Keadaan Kahar (FR-501, FR-303)</h3>
                </div>

                <div class="space-y-3 text-xs text-gray-700 leading-relaxed">
                    <div class="p-3 bg-purple-50 rounded-xl border border-purple-200 space-y-1.5">
                        <h4 class="font-bold text-purple-900 text-[11px] uppercase tracking-wide">Alur Two-Tier Approval (Persetujuan 2 Tingkat):</h4>
                        <p class="text-[11px] text-purple-800">
                            Setiap pengajuan cuti diverifikasi terlebih dahulu oleh <strong>Supervisor Lapangan (Tahap 1)</strong>, kemudian disahkan secara definitif oleh <strong>Kasubbag Umum / PPK (Tahap 2)</strong>.
                        </p>
                    </div>

                    <ul class="list-disc list-inside space-y-1.5 text-gray-600 text-[11px]">
                        <li><strong>Cuti Tahunan:</strong> Kuota 12 hari kerja per tahun. Maksimal 3 hari kerja beruntun per pengajuan.</li>
                        <li><strong>Izin Sakit:</strong> Wajib melampirkan foto Surat Keterangan Dokter dari fasilitas kesehatan resmi.</li>
                        <li><strong>Dispensasi Kahar (Force Majeure):</strong> Pembebasan potongan keterlambatan akibat bencana alam, kecelakaan, atau kendala lalu lintas ekstrem disertai bukti foto.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- TAB 5: Disiplin & Sanksi SP -->
        <div x-show="activeTab === 'discipline'" class="space-y-3.5">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3">
                <div class="flex items-center space-x-2 border-b border-gray-100 pb-2.5">
                    <span class="w-6 h-6 rounded-lg bg-blue-100 text-[#0058A8] flex items-center justify-center font-bold text-xs">5</span>
                    <h3 class="font-bold text-gray-900 text-xs uppercase tracking-wider">Buku Disiplin & Konsekuensi Sanksi SP (FR-401 s.d. FR-405)</h3>
                </div>

                <div class="space-y-3 text-xs text-gray-700 leading-relaxed">
                    <p class="text-[11px] text-gray-600">
                        Pelanggaran disiplin dicatat oleh Supervisor berdasarkan Lampiran II SPK dan dikelompokkan menjadi 3 kategori: Kelompok A (Kehadiran), Kelompok B (Kewajiban), dan Kelompok C (Kedisiplinan).
                    </p>

                    <div class="border border-gray-200 rounded-xl overflow-hidden shadow-2xs">
                        <table class="w-full text-left border-collapse text-[11px]">
                            <thead class="bg-gray-100 text-gray-700">
                                <tr>
                                    <th class="py-2 px-3">Akumulasi Poin</th>
                                    <th class="py-2 px-3">Tingkat Sanksi</th>
                                    <th class="py-2 px-3 text-right">Potongan Honor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-[10.5px]">
                                <tr>
                                    <td class="py-2 px-3 font-mono font-bold">1 s.d. 25 Poin</td>
                                    <td class="py-2 px-3 text-gray-700">Teguran Lisan</td>
                                    <td class="py-2 px-3 text-right font-medium text-emerald-600">0%</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-mono font-bold">26 s.d. 50 Poin</td>
                                    <td class="py-2 px-3 text-amber-800 font-semibold">Surat Peringatan 1 (SP 1)</td>
                                    <td class="py-2 px-3 text-right font-mono font-bold text-rose-600">5% (1 Bulan)</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-mono font-bold">51 s.d. 75 Poin</td>
                                    <td class="py-2 px-3 text-amber-800 font-semibold">Surat Peringatan 2 (SP 2)</td>
                                    <td class="py-2 px-3 text-right font-mono font-bold text-rose-600">7,5% (3 Bulan)</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-mono font-bold">&ge; 76 Poin</td>
                                    <td class="py-2 px-3 text-rose-700 font-bold">Surat Peringatan 3 (SP 3)</td>
                                    <td class="py-2 px-3 text-right font-mono font-bold text-rose-700">10% / Putus Kontrak</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3 bg-rose-50 rounded-xl border border-rose-200 text-[11px] text-rose-900 leading-relaxed">
                        <strong>Pemisahan Akumulasi Sanksi:</strong> Akumulasi 1 (Keterlambatan Jam Kerja) dan Akumulasi 2 (Poin Disiplin SP) dihitung terpisah secara independen demi keadilan hak honorarium pegawai.
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Accordion Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3">
            <h3 class="font-bold text-gray-900 text-xs uppercase tracking-wider flex items-center gap-1.5">
                <svg class="w-4 h-4 text-[#0058A8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Pertanyaan yang Sering Diajukan (FAQ)</span>
            </h3>

            <div class="divide-y divide-gray-100 text-xs">
                <!-- FAQ 1 -->
                <div class="py-2.5">
                    <button 
                        type="button" 
                        @click="activeFaq = activeFaq === 1 ? null : 1" 
                        class="w-full flex items-center justify-between text-left font-semibold text-gray-800 hover:text-[#0058A8] transition"
                    >
                        <span>Mengapa kamera tidak bisa mendeteksi wajah saya?</span>
                        <span x-text="activeFaq === 1 ? '−' : '+'" class="text-sm font-bold text-gray-400"></span>
                    </button>
                    <div x-show="activeFaq === 1" x-cloak class="mt-1.5 text-[11px] text-gray-600 leading-relaxed">
                        Pastikan pencahayaan cukup terang, hindari backlight silau, lepaskan masker penutup wajah, dan posisikan ponsel setinggi mata tepat di dalam garis oval.
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="py-2.5">
                    <button 
                        type="button" 
                        @click="activeFaq = activeFaq === 2 ? null : 2" 
                        class="w-full flex items-center justify-between text-left font-semibold text-gray-800 hover:text-[#0058A8] transition"
                    >
                        <span>Apakah cuti tahunan memotong honorarium saya?</span>
                        <span x-text="activeFaq === 2 ? '−' : '+'" class="text-sm font-bold text-gray-400"></span>
                    </button>
                    <div x-show="activeFaq === 2" x-cloak class="mt-1.5 text-[11px] text-gray-600 leading-relaxed">
                        Tidak. Cuti tahunan yang sah dan telah disetujui dua tingkat (Supervisor dan Kasubbag Umum/PPK) dibayarkan penuh dan tidak dikenakan potongan honorarium.
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="py-2.5">
                    <button 
                        type="button" 
                        @click="activeFaq = activeFaq === 3 ? null : 3" 
                        class="w-full flex items-center justify-between text-left font-semibold text-gray-800 hover:text-[#0058A8] transition"
                    >
                        <span>Bagaimana cara mengetahui sisa masa kontrak saya?</span>
                        <span x-text="activeFaq === 3 ? '−' : '+'" class="text-sm font-bold text-gray-400"></span>
                    </button>
                    <div x-show="activeFaq === 3" x-cloak class="mt-1.5 text-[11px] text-gray-600 leading-relaxed">
                        Sisa masa kontrak tampil secara transparan pada kartu Beranda dan menu Profil Mandiri. Sistem juga mengirimkan notifikasi otomatis pada H-90, H-60, H-30, dan H-7 sebelum kontrak berakhir.
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-layouts.mobile>
