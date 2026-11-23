<x-layouts.mobile title="Beranda Karyawan">
    <div class="space-y-4">
        <!-- User Greeting Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-4">
            <div class="flex items-center space-x-3.5 border-b border-gray-100 pb-4">
                <div class="w-12 h-12 rounded-full bg-[#0058A8] text-white flex items-center justify-center font-bold text-lg shadow shrink-0">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div class="overflow-hidden">
                    <span class="text-xs text-gray-400 font-medium">Selamat datang,</span>
                    <h2 class="font-bold text-gray-900 text-base truncate">{{ Auth::user()->name }}</h2>
                    <p class="text-xs text-gray-500 font-medium truncate">
                        ID: {{ Auth::user()->employee?->employee_code ?? Auth::user()->username }} &bull;
                        <span class="text-[#0058A8] font-semibold">{{ Auth::user()->employee?->position?->name ?? 'PPNPN' }}</span>
                    </p>
                </div>
            </div>

            <!-- Quick Status Metrics -->
            <div class="grid grid-cols-2 gap-3 text-center">
                <div class="p-3 bg-[#E6F0FA] rounded-xl border border-blue-100">
                    <span class="text-[11px] text-gray-500 font-medium block">Status Akun</span>
                    <p class="text-xs font-bold text-[#0058A8]">PPNPN Aktif</p>
                </div>
                <div class="p-3 bg-[#FEF3D0] rounded-xl border border-amber-100">
                    <span class="text-[11px] text-gray-500 font-medium block">Sisa Cuti Tahunan</span>
                    <p class="text-xs font-bold text-[#8A6100]">{{ Auth::user()->employee?->leave_quota_balance ?? 12 }} Hari</p>
                </div>
            </div>

            <!-- Profile Shortcut Button -->
            <a href="{{ route('mobile.profile') }}" class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-blue-50 text-[#0058A8] font-semibold text-xs border border-blue-200 hover:bg-blue-100 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span>Lihat Profil Lengkap Mandiri</span>
                <span>&rarr;</span>
            </a>
        </div>

        <!-- Contract Expiry Alert Banner (FR-601, FR-603 / US-406) -->
        @php
            $emp = Auth::user()->employee;
            $contractDays = $emp?->getDaysUntilContractExpiry();
        @endphp

        @if($contractDays !== null && $contractDays <= 90)
            <div class="rounded-2xl p-4 border {{ $contractDays <= 7 ? 'bg-rose-50 border-rose-200 text-rose-900' : ($contractDays <= 30 ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-blue-50 border-blue-200 text-blue-900') }} shadow-xs space-y-2">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl {{ $contractDays <= 7 ? 'bg-rose-100 text-rose-700' : ($contractDays <= 30 ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-[#0058A8]') }} flex items-center justify-center shrink-0">
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
                    Masa berlaku SPK ({{ $emp->spk_number }}) Anda jatuh tempo pada <strong>{{ $emp->contract_end_date?->translatedFormat('d F Y') }}</strong>. Silakan berkoordinasi dengan pengelola kepegawaian Subbagian Umum Kanwil DJPb Lampung untuk perpanjangan atau adendum.
                </p>
            </div>
        @endif

        <!-- Early Warning System (EWS) Alert Banner (FR-407 / US-504) -->
        @php
            $ews = $emp?->getEwsStatus();
        @endphp

        @if($ews && $ews['is_alert'])
            <div class="rounded-2xl p-4 border {{ $ews['is_critical'] ? 'bg-rose-50 border-rose-300 text-rose-900 shadow-xs' : 'bg-amber-50 border-amber-300 text-amber-900 shadow-xs' }} space-y-2">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl {{ $ews['is_critical'] ? 'bg-rose-600 text-white animate-pulse' : 'bg-amber-500 text-white' }} flex items-center justify-center shrink-0 shadow-xs">
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
                <div class="pt-1">
                    <a href="{{ route('mobile.profile') }}" class="text-[11px] font-semibold underline {{ $ews['is_critical'] ? 'text-rose-900 hover:text-rose-950' : 'text-amber-900 hover:text-amber-950' }}">
                        Lihat Rincian Catatan Disiplin pada Profil &rarr;
                    </a>
                </div>
            </div>
        @endif

        <!-- Attendance Action Card -->
        <div class="p-5 rounded-2xl bg-white border border-gray-100 shadow-sm space-y-3.5">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0058A8] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <div>
                        <span class="text-[11px] font-semibold text-gray-400 tracking-wide uppercase">Presensi Hari Ini</span>
                        <h3 class="font-bold text-gray-900 text-sm">Pemindai Biometrik Wajah</h3>
                    </div>
                </div>
            </div>

            <p class="text-xs text-gray-600 leading-relaxed">
                Arahkan wajah ke kamera dengan akselerasi WebGL, deteksi liveness anti-spoofing, dan validasi radius geofence kantor 100m.
            </p>

            <a href="{{ route('mobile.attendance') }}" class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-[#0058A8] hover:bg-[#004787] text-white font-bold text-xs shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                <span>Buka Kamera Presensi Sekarang</span>
                <span>&rarr;</span>
            </a>
        </div>

        <!-- Attendance History Shortcut Card (FR-211) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#0058A8] flex items-center justify-center font-bold shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-xs">Riwayat Presensi Personal</h4>
                        <p class="text-[11px] text-gray-500">Log kehadiran bulanan & transparansi potongan</p>
                    </div>
                </div>
                <a href="{{ route('mobile.attendance.history') }}" class="text-xs font-bold text-[#0058A8] hover:underline flex items-center gap-1">
                    <span>Buka</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>

        <!-- Leave / Permit Shortcut Card (FR-501) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-[#8A6100] flex items-center justify-center font-bold shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-xs">Permohonan Izin & Cuti</h4>
                        <p class="text-[11px] text-gray-500">Pengajuan mandiri & persetujuan berjenjang</p>
                    </div>
                </div>
                <a href="{{ route('mobile.leaves.index') }}" class="text-xs font-bold text-[#0058A8] hover:underline flex items-center gap-1">
                    <span>Ajukan</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>

        <!-- Attendance Correction Shortcut Card (FR-210) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-xs">Koreksi Lupa Absen</h4>
                        <p class="text-[11px] text-gray-500">Pengesahan tugas dinas (Maks. H+2)</p>
                    </div>
                </div>
                <a href="{{ route('mobile.corrections.index') }}" class="text-xs font-bold text-[#0058A8] hover:underline flex items-center gap-1">
                    <span>Ajukan</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>

        <!-- Force Majeure Late Exception Shortcut Card (FR-303 / US-502) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-xs">Dispensasi Kahar (Force Majeure)</h4>
                        <p class="text-[11px] text-gray-500">Pembebasan potongan honor keterlambatan</p>
                    </div>
                </div>
                <a href="{{ route('mobile.late-exceptions.index') }}" class="text-xs font-bold text-amber-700 hover:underline flex items-center gap-1">
                    <span>Ajukan</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>

        <!-- Discipline Ledger Shortcut Card (FR-409, FR-307 / US-505) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center font-bold shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-xs">Buku Disiplin & Sanksi</h4>
                        <p class="text-[11px] text-gray-500">Saldo poin aktif: <strong class="{{ ($emp?->calculateActiveViolationPoints() ?? 0) >= 60 ? 'text-rose-700' : 'text-gray-700' }}">{{ $emp?->calculateActiveViolationPoints() ?? 0 }} Poin</strong></p>
                    </div>
                </div>
                <a href="{{ route('mobile.discipline.index') }}" class="text-xs font-bold text-[#0058A8] hover:underline flex items-center gap-1">
                    <span>Buka</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>

        <!-- Interactive User Guide & Manual Book Shortcut Card (FR-804 / US-704) -->
        <div class="bg-gradient-to-r from-blue-50/80 via-white to-blue-50/40 rounded-2xl shadow-sm border border-blue-100 p-4 space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-[#0058A8] text-white flex items-center justify-center font-bold shrink-0 shadow-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-1.5">
                            <h4 class="font-bold text-gray-900 text-xs">Panduan Pengguna & SOP</h4>
                            <span class="text-[9px] bg-blue-100 text-[#0058A8] px-1.5 py-0.5 rounded font-bold">Digital</span>
                        </div>
                        <p class="text-[11px] text-gray-500">Tutorial biometrik, jam kerja, cuti & sanksi</p>
                    </div>
                </div>
                <a href="{{ route('mobile.guide') }}" class="text-xs font-bold text-[#0058A8] hover:underline flex items-center gap-1">
                    <span>Pelajari</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>
    </div>
</x-layouts.mobile>
