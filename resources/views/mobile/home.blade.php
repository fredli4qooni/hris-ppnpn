<x-layouts.mobile title="Beranda Karyawan">
    @php
        $hasCheckedIn = $todayAttendance && $todayAttendance->check_in_at !== null;
        $hasCheckedOut = $todayAttendance && $todayAttendance->check_out_at !== null;

        if ($hasCheckedIn && $hasCheckedOut) {
            $ctaHeadline = 'Presensi Hari Ini Selesai';
            $ctaSubtitle = 'Masuk ' . $todayAttendance->check_in_at->timezone('Asia/Jakarta')->format('H:i') . ' &bull; Pulang ' . $todayAttendance->check_out_at->timezone('Asia/Jakarta')->format('H:i') . ' WIB';
            $ctaButtonLabel = 'Lihat Ringkasan Presensi';
        } elseif ($hasCheckedIn) {
            $ctaHeadline = 'Waktunya Presensi Pulang';
            $ctaSubtitle = 'Sudah masuk pukul ' . $todayAttendance->check_in_at->timezone('Asia/Jakarta')->format('H:i') . ' WIB';
            $ctaButtonLabel = 'Buka Kamera Presensi';
        } else {
            $ctaHeadline = 'Absen Sekarang';
            $ctaSubtitle = 'Lakukan presensi untuk mencatat kehadiran Anda';
            $ctaButtonLabel = 'Buka Kamera Presensi';
        }

        $ringRadius = 32;
        $ringCircumference = round(2 * M_PI * $ringRadius, 2);
        $ringOffset = round($ringCircumference * (1 - ($monthlySummary['attendance_rate'] / 100)), 2);
    @endphp

    <div class="space-y-4">
        <!-- Hero Greeting & Identity Card -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-4 space-y-3">
            <a href="{{ route('mobile.profile') }}" class="flex items-center justify-between gap-3 group">
                <div class="flex items-center space-x-3.5 min-w-0">
                    @if($photoUrl)
                        <div class="w-13 h-13 rounded-full bg-white overflow-hidden ring-2 ring-[#0058A8]/20 shadow-sm shrink-0">
                            <img src="{{ $photoUrl }}" alt="{{ $displayName }}" class="w-full h-full object-cover">
                        </div>
                    @else
                        <div class="w-13 h-13 rounded-full bg-[#0B2A4A] text-white flex items-center justify-center font-bold text-base shadow-sm shrink-0 tracking-wide">
                            {{ $initials }}
                        </div>
                    @endif

                    <div class="min-w-0">
                        <span class="text-[11px] text-gray-500 font-medium block leading-tight">Selamat datang,</span>
                        <h2 class="font-extrabold text-gray-900 text-base truncate leading-snug group-hover:text-[#0058A8] transition">{{ $displayName }}</h2>
                        <p class="text-[11px] text-gray-500 truncate mt-0.5">
                            {{ $employee?->position?->name ?? 'PPNPN' }} &bull; Kanwil DJPb Prov. Lampung
                        </p>
                    </div>
                </div>
                <svg class="w-4 h-4 text-gray-400 group-hover:text-[#0058A8] transition shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>

            <!-- Compact Status & Date Pill Bar -->
            <div class="bg-gray-50/90 rounded-2xl border border-gray-100 px-3 py-2 flex items-center justify-between text-[11px]">
                <div class="flex items-center gap-1.5 font-semibold text-emerald-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                    <span>Aktif Hari Ini</span>
                </div>
                <span class="text-gray-300 select-none">|</span>
                <div class="flex items-center gap-1.5 text-gray-600 font-medium truncate">
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span class="truncate">{{ $todayFormatted }}</span>
                </div>
            </div>
        </div>

        <!-- Contract Expiry Alert Banner (FR-601, FR-603 / US-406) -->
        @if($contractDays !== null && $contractDays <= 90)
            <div class="rounded-2xl p-3.5 border {{ $contractDays <= 7 ? 'bg-rose-50 border-rose-200 text-rose-900 shadow-xs' : ($contractDays <= 30 ? 'bg-amber-50 border-amber-200 text-amber-900 shadow-xs' : 'bg-blue-50 border-blue-200 text-blue-900') }} shadow-xs space-y-1.5">
                <div class="flex items-center space-x-2">
                    <div class="w-7 h-7 rounded-xl {{ $contractDays <= 7 ? 'bg-rose-100 text-rose-700' : ($contractDays <= 30 ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-[#0058A8]') }} flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider block {{ $contractDays <= 7 ? 'text-rose-600' : ($contractDays <= 30 ? 'text-amber-600' : 'text-blue-600') }}">
                            Pemberitahuan Masa Kontrak SPK
                        </span>
                        <h4 class="font-bold text-xs">
                            @if($contractDays < 0)
                                Kontrak Kerja Telah Berakhir ({{ abs($contractDays) }} hari lalu)
                            @elseif($contractDays <= 7)
                                Masa Kontrak Berakhir dalam {{ $contractDays }} Hari (Kritis)
                            @else
                                Masa Kontrak Berakhir dalam {{ $contractDays }} Hari (H-{{ $contractDays }})
                            @endif
                        </h4>
                    </div>
                </div>
                <p class="text-[11px] leading-relaxed {{ $contractDays <= 7 ? 'text-rose-800' : ($contractDays <= 30 ? 'text-amber-800' : 'text-blue-800') }}">
                    SPK {{ $employee->spk_number }} berakhir pada <strong>{{ $employee->contract_end_date?->translatedFormat('d M Y') }}</strong>. Hubungi Subbagian Umum untuk perpanjangan.
                </p>
            </div>
        @endif

        <!-- Early Warning System (EWS) Alert Banner (FR-407 / US-504) -->
        @if($ews && $ews['is_alert'])
            <div class="rounded-2xl p-3.5 border {{ $ews['is_critical'] ? 'bg-rose-50 border-rose-300 text-rose-900 shadow-xs' : 'bg-amber-50 border-amber-300 text-amber-900 shadow-xs' }} space-y-1.5">
                <div class="flex items-center space-x-2">
                    <div class="w-7 h-7 rounded-xl {{ $ews['is_critical'] ? 'bg-rose-600 text-white animate-pulse' : 'bg-amber-500 text-white' }} flex items-center justify-center shrink-0 shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider block {{ $ews['is_critical'] ? 'text-rose-700' : 'text-amber-700' }}">
                            Early Warning System (EWS) Disiplin
                        </span>
                        <h4 class="font-bold text-xs">
                            {{ $ews['label'] }} &bull; {{ $ews['points'] }} Poin
                        </h4>
                    </div>
                </div>
                <p class="text-[11px] leading-relaxed {{ $ews['is_critical'] ? 'text-rose-800' : 'text-amber-800' }}">
                    {{ $ews['description'] }}
                </p>
                <div>
                    <a href="{{ route('mobile.discipline.index') }}" class="text-[11px] font-semibold underline {{ $ews['is_critical'] ? 'text-rose-900 hover:text-rose-950' : 'text-amber-900 hover:text-amber-950' }}">
                        Lihat Catatan Disiplin &rarr;
                    </a>
                </div>
            </div>
        @endif

        <!-- Featured Primary Attendance CTA Banner -->
        <div class="rounded-3xl bg-gradient-to-br from-[#0A2547] via-[#0E3668] to-[#0058A8] text-white p-4 shadow-md relative overflow-hidden">
            <!-- Right-side Building Architectural Overlay (Foto Gedung Kantor jika tersedia / fallback siluet SVG) -->
            @if($officeBannerUrl)
                <div class="absolute inset-y-0 right-0 w-3/5 pointer-events-none select-none">
                    <img src="{{ $officeBannerUrl }}" alt="Gedung Kanwil DJPb Lampung" class="w-full h-full object-cover opacity-35">
                    <div class="absolute inset-0 bg-gradient-to-r from-[#0B2A4A] via-[#0B2A4A]/75 to-transparent"></div>
                </div>
            @else
                <svg class="absolute -right-2 -bottom-1 w-44 h-36 text-white/10 pointer-events-none select-none" viewBox="0 0 200 160" fill="currentColor">
                    <rect x="30" y="35" width="140" height="125" rx="4" />
                    <rect x="20" y="25" width="160" height="14" rx="2" />
                    <rect x="45" y="50" width="20" height="18" rx="2" fill="#0B2A4A" opacity="0.55" />
                    <rect x="75" y="50" width="20" height="18" rx="2" fill="#0B2A4A" opacity="0.55" />
                    <rect x="105" y="50" width="20" height="18" rx="2" fill="#0B2A4A" opacity="0.55" />
                    <rect x="135" y="50" width="20" height="18" rx="2" fill="#0B2A4A" opacity="0.55" />
                    <rect x="45" y="78" width="20" height="18" rx="2" fill="#0B2A4A" opacity="0.55" />
                    <rect x="75" y="78" width="20" height="18" rx="2" fill="#0B2A4A" opacity="0.55" />
                    <rect x="105" y="78" width="20" height="18" rx="2" fill="#0B2A4A" opacity="0.55" />
                    <rect x="135" y="78" width="20" height="18" rx="2" fill="#0B2A4A" opacity="0.55" />
                    <rect x="85" y="110" width="30" height="50" rx="2" fill="#0B2A4A" opacity="0.65" />
                </svg>
            @endif

            <div class="relative z-10 space-y-3.5">
                <a href="{{ route('mobile.attendance') }}" class="flex items-start justify-between gap-3 group">
                    <div class="flex items-center space-x-3.5 min-w-0">
                        <!-- Camera Frame Reticle Icon -->
                        <div class="w-12 h-12 rounded-2xl bg-white/10 border border-white/15 text-white flex items-center justify-center shrink-0 backdrop-blur-xs">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8V6a2 2 0 012-2h2M17 4h2a2 2 0 012 2v2M21 16v2a2 2 0 01-2 2h-2M7 20H5a2 2 0 01-2-2v-2" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10a1 1 0 011-1h6a1 1 0 011 1v4a1 1 0 01-1 1H9a1 1 0 01-1-1v-4z" />
                                <circle cx="12" cy="12" r="1.5" fill="currentColor" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[11px] font-semibold text-blue-200 block leading-tight">Presensi Hari Ini</span>
                            <h3 class="font-extrabold text-white text-lg leading-snug truncate">{{ $ctaHeadline }}</h3>
                            <p class="text-[11px] text-blue-100/90 leading-snug mt-0.5">{!! $ctaSubtitle !!}</p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-white/75 group-hover:translate-x-0.5 transition shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>

                <a href="{{ route('mobile.attendance') }}" class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-full bg-[#F8B810] hover:bg-[#eab308] text-[#0B2A4A] font-extrabold text-xs shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span>{{ $ctaButtonLabel }}</span>
                </a>
            </div>
        </div>

        <!-- Section Header: Akses Cepat -->
        <div class="flex items-center justify-between px-0.5 pt-0.5">
            <h3 class="font-extrabold text-gray-900 text-sm tracking-tight">Akses Cepat</h3>
            <span class="text-[11px] font-bold text-[#0058A8]">7 Layanan &rarr;</span>
        </div>

        <!-- 2-Column Horizontal Service Cards -->
        <div class="grid grid-cols-2 gap-2.5">
            <!-- 1: Riwayat Presensi -->
            <a href="{{ route('mobile.attendance.history') }}" class="bg-white rounded-2xl shadow-xs border border-gray-100 p-3 flex items-center justify-between gap-2 hover:border-blue-200 transition group">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#0058A8] flex items-center justify-center shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-gray-900 text-xs leading-tight truncate">Riwayat Presensi</h4>
                        <p class="text-[10px] text-gray-500 mt-0.5 leading-tight truncate">Lihat detail kehadiran</p>
                    </div>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-[#0058A8] shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>

            <!-- 2: Izin & Cuti -->
            <a href="{{ route('mobile.leaves.index') }}" class="bg-white rounded-2xl shadow-xs border border-gray-100 p-3 flex items-center justify-between gap-2 hover:border-sky-200 transition group">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-gray-900 text-xs leading-tight truncate">Izin &amp; Cuti</h4>
                        <p class="text-[10px] text-gray-500 mt-0.5 leading-tight truncate">Ajukan izin atau cuti</p>
                    </div>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-sky-600 shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>

            <!-- 3: Koreksi Absen -->
            <a href="{{ route('mobile.corrections.index') }}" class="bg-white rounded-2xl shadow-xs border border-gray-100 p-3 flex items-center justify-between gap-2 hover:border-indigo-200 transition group">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-gray-900 text-xs leading-tight truncate">Koreksi Absen</h4>
                        <p class="text-[10px] text-gray-500 mt-0.5 leading-tight truncate">Lupa absen? Koreksi</p>
                    </div>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-indigo-700 shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>

            <!-- 4: Dispensasi Kahar -->
            <a href="{{ route('mobile.late-exceptions.index') }}" class="bg-white rounded-2xl shadow-xs border border-gray-100 p-3 flex items-center justify-between gap-2 hover:border-emerald-200 transition group">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-gray-900 text-xs leading-tight truncate">Dispensasi Kahar</h4>
                        <p class="text-[10px] text-gray-500 mt-0.5 leading-tight truncate">Izin terlambat darurat</p>
                    </div>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-emerald-600 shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>

            <!-- 5: Buku Disiplin -->
            <a href="{{ route('mobile.discipline.index') }}" class="bg-white rounded-2xl shadow-xs border border-gray-100 p-3 flex items-center justify-between gap-2 hover:border-rose-200 transition group">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-gray-900 text-xs leading-tight truncate">Buku Disiplin</h4>
                        <p class="text-[10px] text-gray-500 mt-0.5 leading-tight truncate">Catatan ({{ $activePoints }} Poin)</p>
                    </div>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-rose-600 shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>

            <!-- 6: Panduan Pengguna & SOP -->
            <a href="{{ route('mobile.guide') }}" class="bg-white rounded-2xl shadow-xs border border-gray-100 p-3 flex items-center justify-between gap-2 hover:border-blue-200 transition group">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#0058A8] flex items-center justify-center shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-gray-900 text-xs leading-tight truncate">Panduan Pengguna &amp; SOP</h4>
                        <p class="text-[10px] text-gray-500 mt-0.5 leading-tight truncate">Cara presensi &amp; aturan</p>
                    </div>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-[#0058A8] shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>

            <!-- 7: Pengajuan Lembur & SPTPL -->
            <a href="{{ route('mobile.overtime.index') }}" class="col-span-2 bg-white rounded-2xl shadow-xs border border-gray-100 p-3 flex items-center justify-between gap-2 hover:border-blue-200 transition group">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-gray-900 text-xs leading-tight truncate">Pengajuan Lembur &amp; SPTPL</h4>
                        <p class="text-[10px] text-gray-500 mt-0.5 leading-tight truncate">Lihat SPTPL, ajukan lembur tanpa surat &amp; impor PDF</p>
                    </div>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-[#0058A8] shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>

        <!-- Ringkasan Kehadiran Card -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-4 space-y-3">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="text-[#0B2A4A]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </span>
                    <h3 class="font-extrabold text-gray-900 text-sm">Ringkasan Kehadiran</h3>
                </div>

                <form method="GET" action="{{ route('mobile.home') }}">
                    <select
                        name="summary_period"
                        onchange="this.form.submit()"
                        class="text-[11px] font-semibold text-gray-700 bg-gray-50 border border-gray-200 rounded-full px-3 py-1 focus:outline-hidden focus:ring-2 focus:ring-[#0058A8] cursor-pointer"
                    >
                        <option value="this_month" {{ $monthlySummary['period_key'] === 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                        <option value="last_month" {{ $monthlySummary['period_key'] === 'last_month' ? 'selected' : '' }}>Bulan Lalu</option>
                    </select>
                </form>
            </div>

            <div class="flex items-center gap-4">
                <!-- Circular Progress Donut -->
                <div class="shrink-0">
                    <div class="relative w-20 h-20 flex items-center justify-center">
                        <svg class="w-20 h-20 -rotate-90" viewBox="0 0 80 80">
                            <circle cx="40" cy="40" r="{{ $ringRadius }}" stroke="#E5E7EB" stroke-width="7" fill="none" />
                            <circle
                                cx="40"
                                cy="40"
                                r="{{ $ringRadius }}"
                                stroke="#0058A8"
                                stroke-width="7"
                                stroke-linecap="round"
                                fill="none"
                                stroke-dasharray="{{ $ringCircumference }}"
                                stroke-dashoffset="{{ $ringOffset }}"
                            />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center leading-none">
                            <span class="text-base font-black text-gray-900">{{ $monthlySummary['attendance_rate'] }}%</span>
                            <span class="text-[9px] text-gray-500 mt-0.5">Kehadiran</span>
                        </div>
                    </div>
                </div>

                <!-- 3 Metric Grid -->
                <div class="flex-1 grid grid-cols-3 gap-2 text-xs">
                    <div class="bg-blue-50/60 border border-blue-100 rounded-2xl p-2.5 text-center space-y-1">
                        <div class="w-6 h-6 mx-auto rounded-lg bg-white text-[#0058A8] flex items-center justify-center shadow-2xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <span class="text-sm font-extrabold text-gray-900 block leading-none">{{ $monthlySummary['present_days'] }}</span>
                        <span class="text-[10px] text-gray-500 block leading-tight">Hari Hadir</span>
                    </div>

                    <div class="bg-gray-50/90 border border-gray-100 rounded-2xl p-2.5 text-center space-y-1">
                        <div class="w-6 h-6 mx-auto rounded-lg bg-white text-gray-600 flex items-center justify-center shadow-2xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="text-sm font-extrabold text-gray-900 block leading-none">{{ $monthlySummary['leave_days'] }}</span>
                        <span class="text-[10px] text-gray-500 block leading-tight">Izin</span>
                    </div>

                    <div class="bg-rose-50/60 border border-rose-100 rounded-2xl p-2.5 text-center space-y-1">
                        <div class="w-6 h-6 mx-auto rounded-lg bg-white text-rose-600 flex items-center justify-center shadow-2xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="text-sm font-extrabold text-gray-900 block leading-none">{{ $monthlySummary['alpha_days'] }}</span>
                        <span class="text-[10px] text-gray-500 block leading-tight">Alpa</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.mobile>
