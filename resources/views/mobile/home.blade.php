<x-layouts.mobile title="Beranda Karyawan">
    @php
        $user = Auth::user();
        $emp = $user->employee;
        $contractDays = $emp?->getDaysUntilContractExpiry();
        $ews = $emp?->getEwsStatus();
        $activePoints = $emp?->calculateActiveViolationPoints() ?? 0;
    @endphp

    <div class="space-y-3.5">
        <!-- Bento Hero: Greeting & Compact Status -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-4 space-y-3.5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center space-x-3 min-w-0">
                    @if($emp?->photo_path)
                        <div class="w-11 h-11 rounded-2xl bg-white overflow-hidden ring-2 ring-blue-100 shadow-sm shrink-0">
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($emp->photo_path) }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                        </div>
                    @else
                        <div class="w-11 h-11 rounded-2xl bg-[#0058A8] text-white flex items-center justify-center font-bold text-base shadow-sm shrink-0">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <span class="text-[11px] text-gray-400 font-medium block leading-tight">Selamat datang,</span>
                        <h2 class="font-bold text-gray-900 text-sm truncate leading-snug">{{ $user->name }}</h2>
                        <p class="text-[11px] text-gray-500 font-medium truncate">
                            {{ $emp?->employee_code ?? $user->username }} &bull;
                            <span class="text-[#0058A8] font-semibold">{{ $emp?->position?->name ?? 'PPNPN' }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bento Mini Pills -->
            <div class="grid grid-cols-2 gap-2.5">
                <div class="px-3 py-2 bg-[#E6F0FA]/80 rounded-2xl border border-blue-100 flex items-center justify-between">
                    <span class="text-[10px] text-gray-600 font-medium">Status</span>
                    <span class="text-[11px] font-bold text-[#0058A8]">PPNPN Aktif</span>
                </div>
                <div class="px-3 py-2 bg-[#FEF3D0]/80 rounded-2xl border border-amber-100 flex items-center justify-between">
                    <span class="text-[10px] text-amber-800 font-medium">Sisa Cuti</span>
                    <span class="text-[11px] font-bold text-[#8A6100]">{{ $emp?->leave_quota_balance ?? 12 }} Hari</span>
                </div>
            </div>
        </div>

        <!-- Contract Expiry Alert Banner (FR-601, FR-603 / US-406) -->
        @if($contractDays !== null && $contractDays <= 90)
            <div class="rounded-2xl p-3.5 border {{ $contractDays <= 7 ? 'bg-rose-50 border-rose-200 text-rose-900' : ($contractDays <= 30 ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-blue-50 border-blue-200 text-blue-900') }} shadow-xs space-y-1.5">
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
                    SPK {{ $emp->spk_number }} berakhir pada <strong>{{ $emp->contract_end_date?->translatedFormat('d M Y') }}</strong>. Hubungi Subbagian Umum untuk perpanjangan.
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

        <!-- Bento Featured Tile: Primary Attendance CTA -->
        <div class="rounded-3xl bg-gradient-to-br from-[#0B2A4A] to-[#0058A8] text-white p-4 shadow-md space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-white/15 text-[#F8B810] flex items-center justify-center shrink-0 backdrop-blur-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-blue-200 tracking-wider uppercase block">Presensi Hari Ini</span>
                        <h3 class="font-bold text-white text-sm leading-tight">Presensi Kamera</h3>
                    </div>
                </div>
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-white/15 text-blue-100">
                    Radius 100m
                </span>
            </div>

            <a href="{{ route('mobile.attendance') }}" class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-2xl bg-[#F8B810] hover:bg-[#eab308] text-[#0B2A4A] font-extrabold text-xs shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                <span>Buka Kamera Presensi Sekarang</span>
                <span>&rarr;</span>
            </a>
        </div>

        <!-- Bento Menu Grid (2 Columns) -->
        <div class="grid grid-cols-2 gap-3">
            <!-- Bento Tile 1: Riwayat Presensi -->
            <a href="{{ route('mobile.attendance.history') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3.5 flex flex-col justify-between hover:border-blue-200 transition group min-h-[112px]">
                <div class="flex items-center justify-between">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#0058A8] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <span class="text-xs text-gray-300 group-hover:text-[#0058A8] transition">&rarr;</span>
                </div>
                <div class="mt-3">
                    <h4 class="font-bold text-gray-900 text-xs leading-snug">Riwayat Presensi</h4>
                    <p class="text-[11px] text-gray-500 mt-0.5 leading-tight">Jam masuk &amp; pulang</p>
                </div>
            </a>

            <!-- Bento Tile 2: Izin & Cuti -->
            <a href="{{ route('mobile.leaves.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3.5 flex flex-col justify-between hover:border-amber-200 transition group min-h-[112px]">
                <div class="flex items-center justify-between">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-[#8A6100] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <span class="text-xs text-gray-300 group-hover:text-[#8A6100] transition">&rarr;</span>
                </div>
                <div class="mt-3">
                    <h4 class="font-bold text-gray-900 text-xs leading-snug">Izin &amp; Cuti</h4>
                    <p class="text-[11px] text-gray-500 mt-0.5 leading-tight">Ajukan cuti atau sakit</p>
                </div>
            </a>

            <!-- Bento Tile 3: Koreksi Lupa Absen -->
            <a href="{{ route('mobile.corrections.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3.5 flex flex-col justify-between hover:border-purple-200 transition group min-h-[112px]">
                <div class="flex items-center justify-between">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </div>
                    <span class="text-xs text-gray-300 group-hover:text-purple-700 transition">&rarr;</span>
                </div>
                <div class="mt-3">
                    <h4 class="font-bold text-gray-900 text-xs leading-snug">Koreksi Absen</h4>
                    <p class="text-[11px] text-gray-500 mt-0.5 leading-tight">Lupa absen (Maks. H+2)</p>
                </div>
            </a>

            <!-- Bento Tile 4: Dispensasi Kahar -->
            <a href="{{ route('mobile.late-exceptions.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3.5 flex flex-col justify-between hover:border-amber-200 transition group min-h-[112px]">
                <div class="flex items-center justify-between">
                    <div class="w-9 h-9 rounded-xl bg-orange-50 text-orange-700 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <span class="text-xs text-gray-300 group-hover:text-orange-700 transition">&rarr;</span>
                </div>
                <div class="mt-3">
                    <h4 class="font-bold text-gray-900 text-xs leading-snug">Dispensasi Kahar</h4>
                    <p class="text-[11px] text-gray-500 mt-0.5 leading-tight">Izin telat darurat</p>
                </div>
            </a>

            <!-- Bento Tile 5: Buku Disiplin & Sanksi -->
            <a href="{{ route('mobile.discipline.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3.5 flex flex-col justify-between hover:border-rose-200 transition group min-h-[112px]">
                <div class="flex items-center justify-between">
                    <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $activePoints >= 60 ? 'bg-rose-100 text-rose-700' : 'bg-gray-100 text-gray-700' }}">
                        {{ $activePoints }} Poin
                    </span>
                </div>
                <div class="mt-3">
                    <h4 class="font-bold text-gray-900 text-xs leading-snug">Buku Disiplin</h4>
                    <p class="text-[11px] text-gray-500 mt-0.5 leading-tight">Catatan &amp; sanksi</p>
                </div>
            </a>

            <!-- Bento Tile 6: Panduan Pengguna & SOP -->
            <a href="{{ route('mobile.guide') }}" class="bg-gradient-to-br from-blue-50/90 to-white rounded-2xl shadow-sm border border-blue-100 p-3.5 flex flex-col justify-between hover:border-blue-300 transition group min-h-[112px]">
                <div class="flex items-center justify-between">
                    <div class="w-9 h-9 rounded-xl bg-[#0058A8] text-white flex items-center justify-center shrink-0 shadow-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <span class="text-[9px] bg-blue-100 text-[#0058A8] px-1.5 py-0.5 rounded font-bold">SOP</span>
                </div>
                <div class="mt-3">
                    <h4 class="font-bold text-gray-900 text-xs leading-snug">Panduan Pengguna &amp; SOP</h4>
                    <p class="text-[11px] text-gray-500 mt-0.5 leading-tight">Cara presensi &amp; aturan</p>
                </div>
            </a>
        </div>
    </div>
</x-layouts.mobile>
