<x-layouts.mobile title="Profil Pegawai">
    <div class="space-y-4" x-data="{ showSalary: false }">
        <!-- Profile Header Card (Solid DJPb Navy Top Banner) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-16 bg-[#0B2A4A]"></div>

            <div class="relative pt-4">
                <!-- Avatar / Photo -->
                <div class="w-20 h-20 mx-auto rounded-full ring-4 ring-white shadow-md bg-white overflow-hidden flex items-center justify-center">
                    @if($employee?->photo_path)
                        <img src="{{ asset('storage/' . $employee->photo_path) }}" alt="{{ $employee->full_name }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full bg-[#0058A8] text-white flex items-center justify-center font-bold text-2xl">
                            {{ strtoupper(substr($employee?->full_name ?? $user->name, 0, 1)) }}
                        </div>
                    @endif
                </div>

                <!-- Name & Identification -->
                <h2 class="mt-3 font-bold text-gray-900 text-base leading-snug">
                    {{ $employee?->full_name ?? $user->name }}
                </h2>
                <p class="text-xs text-gray-500 font-mono mt-0.5">
                    NIP: {{ $employee?->employee_code ?? $user->username }}
                </p>

                <div class="mt-2.5 flex items-center justify-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-[#0058A8] border border-blue-200">
                        {{ $employee?->position?->name ?? 'PPNPN' }}
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        ● Aktif
                    </span>
                </div>
            </div>
        </div>

        <!-- Early Warning System (EWS) Alert Banner (FR-407 / US-504) -->
        @php
            $profileEws = $employee?->getEwsStatus();
        @endphp

        @if($profileEws && $profileEws['is_alert'])
            <div class="rounded-2xl p-4 border {{ $profileEws['is_critical'] ? 'bg-rose-50 border-rose-300 text-rose-900 shadow-xs' : 'bg-amber-50 border-amber-300 text-amber-900 shadow-xs' }} space-y-2">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl {{ $profileEws['is_critical'] ? 'bg-rose-600 text-white animate-pulse' : 'bg-amber-500 text-white' }} flex items-center justify-center shrink-0 shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider block {{ $profileEws['is_critical'] ? 'text-rose-700' : 'text-amber-700' }}">
                            Early Warning System (EWS) Disiplin
                        </span>
                        <h4 class="font-bold text-xs">
                            {{ $profileEws['label'] }} &bull; Saldo: {{ $profileEws['points'] }} Poin
                        </h4>
                    </div>
                </div>
                <p class="text-[11px] leading-relaxed {{ $profileEws['is_critical'] ? 'text-rose-800' : 'text-amber-800' }}">
                    {{ $profileEws['description'] }}
                </p>
                @if($employee)
                    <div class="pt-1 flex items-center justify-between">
                        <a href="{{ route('admin.employees.discipline-summary', $employee) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold border transition {{ $profileEws['is_critical'] ? 'bg-rose-100 hover:bg-rose-200 border-rose-300 text-rose-900' : 'bg-amber-100 hover:bg-amber-200 border-amber-300 text-amber-900' }}">
                            <span>Unduh Ringkasan Pembinaan (BAP)</span>
                            <span>↗</span>
                        </a>
                    </div>
                @endif
            </div>
        @endif

        <!-- Metric Badges -->
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-white rounded-xl p-3.5 border border-gray-100 shadow-sm flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-amber-50 text-[#8A6100] border border-amber-200 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-[#8A6100]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                    <span class="text-[11px] text-gray-400 block font-medium">Sisa Cuti Tahunan</span>
                    <span class="text-base font-bold text-gray-900">{{ $employee?->leave_quota_balance ?? 12 }}</span>
                    <span class="text-xs text-gray-500">hari</span>
                </div>
            </div>

            <div class="bg-white rounded-xl p-3.5 border border-gray-100 shadow-sm flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg {{ $employee?->hasFaceEnrolled() ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-rose-50 text-rose-600 border border-rose-200' }} flex items-center justify-center shrink-0">
                    @if($employee?->hasFaceEnrolled())
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    @else
                        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    @endif
                </div>
                <div>
                    <span class="text-[11px] text-gray-400 block font-medium">Status Biometrik</span>
                    @if($employee?->hasFaceEnrolled())
                        <span class="text-xs font-bold text-emerald-700 block">Terdaftar</span>
                        <span class="text-[10px] text-gray-400">AES-256 (UU PDP)</span>
                    @else
                        <span class="text-xs font-bold text-rose-600 block">Belum Terdaftar</span>
                        <span class="text-[10px] text-gray-400">Hubungi Admin</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Detail Kepegawaian & Penempatan Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3">
            <h3 class="text-xs font-bold text-[#0B2A4A] uppercase tracking-wider border-b border-gray-100 pb-2 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-[#0058A8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                <span>Data Kepegawaian & Jam Kerja</span>
            </h3>

            <div class="grid grid-cols-1 divide-y divide-gray-50 text-xs">
                <div class="py-2 flex justify-between items-center">
                    <span class="text-gray-500">Jenis Kelamin</span>
                    <span class="font-medium text-gray-900">{{ ($employee?->gender ?? 'L') === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                </div>
                <div class="py-2 flex justify-between items-center">
                    <span class="text-gray-500">Nomor WhatsApp / Kontak</span>
                    <span class="font-medium text-gray-900">{{ $employee?->phone_number ?? '-' }}</span>
                </div>
                <div class="py-2 flex justify-between items-center">
                    <span class="text-gray-500">Posisi Tugas Utama</span>
                    <span class="font-semibold text-[#0058A8]">{{ $employee?->position?->name ?? 'Pramubhakti' }}</span>
                </div>
                <div class="py-2 flex justify-between items-center">
                    <span class="text-gray-500">Jam Kerja Standar</span>
                    <span class="font-mono text-gray-900">
                        {{ $employee?->position?->standard_start_time ? substr($employee->position->standard_start_time, 0, 5) : '07:30' }} -
                        {{ $employee?->position?->standard_end_time ? substr($employee->position->standard_end_time, 0, 5) : '17:00' }} WIB
                    </span>
                </div>
                <div class="py-2 flex justify-between items-center">
                    <span class="text-gray-500">Toleransi Keterlambatan</span>
                    <span class="font-medium text-amber-700">15 Menit (Wajib Ganti Jam)</span>
                </div>
            </div>
        </div>

        <!-- Hirarki Penugasan Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3">
            <h3 class="text-xs font-bold text-[#0B2A4A] uppercase tracking-wider border-b border-gray-100 pb-2 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-[#0058A8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <span>Hirarki Penugasan Kerja</span>
            </h3>

            <div class="grid grid-cols-1 divide-y divide-gray-50 text-xs">
                <div class="py-2 flex justify-between items-center">
                    <div>
                        <span class="text-gray-500 block">Supervisor Lapangan (Level 1)</span>
                        <span class="text-[11px] text-gray-400">Pencatat Pelanggaran & Verifikasi Izin</span>
                    </div>
                    <span class="font-semibold text-gray-900 text-right">{{ $employee?->supervisor?->name ?? 'Belum Ditentukan' }}</span>
                </div>
                <div class="py-2 flex justify-between items-center">
                    <div>
                        <span class="text-gray-500 block">Pejabat Penyetuju (Level 2)</span>
                        <span class="text-[11px] text-gray-400">Persetujuan Final Cuti & Sanksi</span>
                    </div>
                    <span class="font-semibold text-gray-900 text-right">{{ $employee?->atasan?->name ?? 'Kepala Bagian Umum' }}</span>
                </div>
            </div>
        </div>

        <!-- Buku Pelanggaran & Disiplin (Discipline Ledger FR-409, FR-307 / US-505) Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3">
            <h3 class="text-xs font-bold text-[#0B2A4A] uppercase tracking-wider border-b border-gray-100 pb-2 flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    <span>Buku Catatan Disiplin & Pelanggaran</span>
                </div>
                <span class="text-[10px] text-gray-400 font-bold">Akumulasi 2</span>
            </h3>

            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="p-2.5 rounded-xl bg-gray-50 border border-gray-100">
                    <span class="text-[10px] text-gray-400 block font-medium">Saldo Poin Aktif</span>
                    <span class="text-base font-bold font-mono {{ ($employee?->calculateActiveViolationPoints() ?? 0) >= 60 ? 'text-rose-700' : 'text-gray-900' }}">
                        {{ $employee?->calculateActiveViolationPoints() ?? 0 }} Poin
                    </span>
                </div>

                <div class="p-2.5 rounded-xl bg-gray-50 border border-gray-100">
                    <span class="text-[10px] text-gray-400 block font-medium">Status Disiplin</span>
                    <span class="text-xs font-bold {{ $employee?->getEwsStatus()['color'] === 'danger' ? 'text-rose-700' : ($employee?->getEwsStatus()['color'] === 'warning' ? 'text-amber-700' : 'text-emerald-700') }} block mt-0.5 truncate">
                        {{ $employee?->getEwsStatus()['badge_label'] ?? 'Aman' }}
                    </span>
                </div>
            </div>

            <a href="{{ route('mobile.discipline.index') }}" class="w-full inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 font-bold text-xs transition">
                <span>Buka Buku Catatan Disiplin & Sanksi</span>
                <span>&rarr;</span>
            </a>
        </div>

        <!-- Kontrak SPK & Honorarium Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3">
            <h3 class="text-xs font-bold text-[#0B2A4A] uppercase tracking-wider border-b border-gray-100 pb-2 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-[#0058A8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span>Kontrak Kerja & Hak Pegawai</span>
            </h3>

            <div class="grid grid-cols-1 divide-y divide-gray-50 text-xs">
                <div class="py-2 flex justify-between items-center">
                    <span class="text-gray-500">Nomor SPK</span>
                    <span class="font-mono font-medium text-gray-900">{{ $employee?->spk_number ?? 'SPK-20/WPB.08/PPK/2025' }}</span>
                </div>
                <div class="py-2 flex justify-between items-center">
                    <span class="text-gray-500">Masa Berlaku Kontrak</span>
                    <span class="font-medium text-gray-900">
                        {{ $employee?->contract_start_date ? $employee->contract_start_date->format('d M Y') : '01 Okt 2025' }} s.d.
                        {{ $employee?->contract_end_date ? $employee->contract_end_date->format('d M Y') : '30 Sep 2026' }}
                    </span>
                </div>
                @if($daysRemaining !== null)
                <div class="py-2 flex justify-between items-center">
                    <span class="text-gray-500">Status Masa Kontrak</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $employee?->getContractStatusBadgeColor() === 'danger' ? 'bg-rose-50 text-rose-700 border border-rose-200' : ($employee?->getContractStatusBadgeColor() === 'warning' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200') }}">
                        {{ $employee?->getContractStatusLabel() }}
                    </span>
                </div>
                @endif
                @if($employee?->contract_value)
                <div class="py-2 flex justify-between items-center">
                    <span class="text-gray-500">Nilai Kontrak Total SPK</span>
                    <span class="font-mono font-medium text-gray-900">
                        Rp {{ number_format($employee->contract_value, 0, ',', '.') }}
                    </span>
                </div>
                @endif
                <div class="py-2.5 flex justify-between items-center bg-gray-50/70 -mx-4 px-4 rounded-lg mt-1">
                    <div>
                        <span class="text-gray-600 font-semibold block">Honorarium Bulanan</span>
                        <span class="text-[10px] text-gray-400">Hak Tetap Sesuai Lampiran SPK</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="font-bold text-sm text-[#0058A8]" x-show="showSalary">
                            Rp {{ number_format($employee?->base_salary ?? 3200000, 0, ',', '.') }}
                        </span>
                        <span class="font-bold text-sm text-gray-400 tracking-widest" x-show="!showSalary">
                            ••••••••••
                        </span>
                        <button type="button" @click="showSalary = !showSalary" class="text-gray-400 hover:text-gray-600 p-1 focus:outline-none transition cursor-pointer" title="Tampilkan/Sembunyikan">
                            <svg x-show="!showSalary" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"></circle>
                            </svg>
                            <svg x-show="showSalary" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23" stroke-linecap="round" stroke-linejoin="round"></line>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Notice -->
        <p class="text-center text-[11px] text-gray-400 pt-2 pb-1">
            Data dilindungi secara rahasia oleh Kanwil DJPb Provinsi Lampung sesuai ketentuan UU PDP No. 27/2022.
        </p>
    </div>
</x-layouts.mobile>
