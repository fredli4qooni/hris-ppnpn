<x-layouts.mobile title="Riwayat Presensi">
    <div 
        x-data="{
            showDetailModal: false,
            selectedItem: null,
            openDetail(item) {
                this.selectedItem = item;
                this.showDetailModal = true;
            },
            closeDetail() {
                this.showDetailModal = false;
                this.selectedItem = null;
            }
        }"
        class="space-y-4"
    >
        <!-- Page Header & Period Navigation -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0058A8] flex items-center justify-center font-bold shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900 leading-tight">Riwayat Presensi</h2>
                        <span class="text-[11px] text-gray-400 font-medium">Log Kehadiran & Rekapitulasi</span>
                    </div>
                </div>
            </div>

            <!-- Month Navigator -->
            <div class="flex items-center justify-between gap-2 pt-1">
                <a 
                    href="{{ route('mobile.attendance.history', ['month' => $prevPeriod->month, 'year' => $prevPeriod->year]) }}" 
                    class="p-2 rounded-xl bg-gray-50 hover:bg-gray-100 text-gray-600 transition flex items-center justify-center shrink-0 border border-gray-200"
                    title="Bulan Sebelumnya"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </a>

                <form method="GET" action="{{ route('mobile.attendance.history') }}" class="flex-1 flex items-center justify-center gap-1.5">
                    <select 
                        name="month" 
                        onchange="this.form.submit()" 
                        class="text-xs font-bold text-gray-800 bg-gray-50 border border-gray-200 rounded-xl px-2.5 py-1.5 focus:ring-2 focus:ring-[#0058A8] focus:outline-hidden"
                    >
                        @foreach($monthOptions as $mNum => $mLabel)
                            <option value="{{ $mNum }}" {{ $selectedMonth === $mNum ? 'selected' : '' }}>
                                {{ $mLabel }}
                            </option>
                        @endforeach
                    </select>

                    <select 
                        name="year" 
                        onchange="this.form.submit()" 
                        class="text-xs font-bold text-gray-800 bg-gray-50 border border-gray-200 rounded-xl px-2.5 py-1.5 focus:ring-2 focus:ring-[#0058A8] focus:outline-hidden"
                    >
                        @for($y = 2024; $y <= 2028; $y++)
                            <option value="{{ $y }}" {{ $selectedYear === $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </form>

                <a 
                    href="{{ route('mobile.attendance.history', ['month' => $nextPeriod->month, 'year' => $nextPeriod->year]) }}" 
                    class="p-2 rounded-xl bg-gray-50 hover:bg-gray-100 text-gray-600 transition flex items-center justify-center shrink-0 border border-gray-200"
                    title="Bulan Berikutnya"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            </div>
        </div>

        <!-- Monthly Summary KPI Grid (FR-211 & FR-307) -->
        <div class="grid grid-cols-2 gap-2.5">
            <!-- Total Hadir -->
            <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Hadir</span>
                    <span class="w-6 h-6 rounded-lg bg-blue-50 text-[#0058A8] flex items-center justify-center text-xs font-bold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </span>
                </div>
                <div class="mt-2">
                    <span class="text-xl font-extrabold text-[#0B2A4A]">{{ $summary['total_days'] }}</span>
                    <span class="text-[11px] text-gray-500 font-medium ml-0.5">Hari</span>
                </div>
                <div class="mt-1 text-[10px] text-gray-400">
                    Periode {{ $monthName }} {{ $selectedYear }}
                </div>
            </div>

            <!-- Tepat Waktu -->
            <div class="bg-white rounded-2xl p-3.5 border border-emerald-100 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Tepat Waktu</span>
                    <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs font-bold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </span>
                </div>
                <div class="mt-2">
                    <span class="text-xl font-extrabold text-emerald-700">{{ $summary['on_time_count'] }}</span>
                    <span class="text-[11px] text-gray-500 font-medium ml-0.5">Hari</span>
                </div>
                <div class="mt-1 text-[10px] text-emerald-600 font-medium">
                    @if($summary['total_days'] > 0)
                        {{ round(($summary['on_time_count'] / $summary['total_days']) * 100) }}% Disiplin
                    @else
                        0%
                    @endif
                </div>
            </div>

            <!-- Toleransi 15 Menit -->
            <div class="bg-white rounded-2xl p-3.5 border border-amber-100 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">Toleransi</span>
                    <span class="w-6 h-6 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs font-bold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </span>
                </div>
                <div class="mt-2">
                    <span class="text-xl font-extrabold text-amber-800">{{ $summary['tolerance_count'] }}</span>
                    <span class="text-[11px] text-gray-500 font-medium ml-0.5">Hari</span>
                </div>
                <div class="mt-1 text-[10px] text-amber-700">
                    Lunas: <span class="font-bold text-emerald-700">{{ $summary['tolerance_compensated_count'] }}</span> &bull; 
                    Belum: <span class="font-bold text-amber-800">{{ $summary['tolerance_uncompensated_count'] }}</span>
                </div>
            </div>

            <!-- Keterlambatan TL & PSW -->
            <div class="bg-white rounded-2xl p-3.5 border border-rose-100 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-rose-600 uppercase tracking-wider">Terlambat / PSW</span>
                    <span class="w-6 h-6 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs font-bold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span class="text-xl font-extrabold text-rose-700">{{ $summary['late_tl_count'] }}</span>
                    <span class="text-[11px] text-gray-500 font-medium">TL</span>
                    <span class="text-gray-300">|</span>
                    <span class="text-xl font-extrabold text-rose-700">{{ $summary['psw_count'] }}</span>
                    <span class="text-[11px] text-gray-500 font-medium">PSW</span>
                </div>
                <div class="mt-1 text-[10px] text-rose-700">
                    TL1: {{ $summary['tl_1_count'] }} &bull; TL2: {{ $summary['tl_2_count'] }} &bull; TL3: {{ $summary['tl_3_count'] }}
                </div>
            </div>
        </div>

        <!-- Real-time Deduction Transparency Card (FR-307) -->
        <div class="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0058A8] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider block">Akumulasi 1 (Lampiran IV SPK)</span>
                        <h4 class="font-bold text-xs text-gray-900">Transparansi Potongan Honor</h4>
                    </div>
                </div>
                <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full border {{ $summary['total_deduction_percentage'] > 0 ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
                    {{ $summary['total_deduction_percentage'] > 0 ? 'Kena Potongan' : 'Nihil Potongan' }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2.5 border-t border-gray-100">
                <div class="bg-gray-50 p-2.5 rounded-xl border border-gray-100">
                    <span class="text-[10px] text-gray-500 font-medium block">Total Akumulasi TL</span>
                    <div class="text-base font-extrabold font-mono text-gray-900 mt-0.5">
                        {{ number_format($summary['total_deduction_percentage'], 2, ',', '.') }}%
                    </div>
                </div>
                <div class="bg-gray-50 p-2.5 rounded-xl border border-gray-100">
                    <span class="text-[10px] text-gray-500 font-medium block">Estimasi Potongan</span>
                    <div class="text-base font-extrabold font-mono {{ $summary['estimated_deduction_amount'] > 0 ? 'text-amber-700' : 'text-emerald-700' }} mt-0.5">
                        Rp {{ number_format($summary['estimated_deduction_amount'], 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <p class="text-[10px] text-gray-500 leading-relaxed border-t border-gray-100 pt-2.5">
                Potongan dihitung secara khusus berdasarkan keterlambatan masuk, dengan ketentuan TL 1 sebesar 0,5%, TL 2 sebesar 1,0%, dan TL 3 sebesar 1,5%, sesuai Lampiran IV SPK. Perhitungan ini terpisah dari poin pelanggaran supervisor.
            </p>
        </div>

        <!-- Attendance Day Records Feed (FR-211) -->
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                    Daftar Kehadiran Harian ({{ $attendances->count() }})
                </h3>
                <span class="text-[11px] text-gray-400">Urutan Terbaru</span>
            </div>

            @if($attendances->isEmpty())
                <!-- Empty State -->
                <div class="bg-white rounded-2xl border border-gray-100 p-8 text-center space-y-3 shadow-sm">
                    <div class="w-14 h-14 mx-auto rounded-full bg-blue-50 text-[#0058A8] flex items-center justify-center">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900">Belum Ada Catatan Presensi</h4>
                        <p class="text-xs text-gray-500 mt-1 max-w-xs mx-auto leading-relaxed">
                            Tidak ditemukan rekaman presensi pada bulan {{ $monthName }} {{ $selectedYear }}. Silakan periksa bulan lain atau lakukan presensi.
                        </p>
                    </div>
                    <div class="pt-2">
                        <a 
                            href="{{ route('mobile.attendance') }}" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#0058A8] text-white text-xs font-bold rounded-xl shadow-xs hover:bg-[#004787] transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span>Lakukan Presensi Hari Ini</span>
                        </a>
                    </div>
                </div>
            @else
                <!-- Feed List -->
                <div class="space-y-3">
                    @foreach($attendances as $att)
                        @php
                            // Status Masuk Formatting
                            $inStatusBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            $inStatusLabel = 'Tepat Waktu';
                            if ($att->isTolerance()) {
                                $inStatusBadgeClass = 'bg-amber-50 text-amber-800 border-amber-200';
                                $inStatusLabel = 'Toleransi (+'.$att->late_minutes.'m)';
                            } elseif ($att->isLateTl()) {
                                $inStatusBadgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                                $inStatusLabel = $att->tl_category . ' (-' . $att->deduction_percentage . '%)';
                            }

                            // Status Pulang Formatting
                            $outStatusBadgeClass = 'bg-slate-50 text-slate-600 border-slate-200';
                            $outStatusLabel = 'Belum Pulang';
                            if ($att->isCheckedOut()) {
                                if ($att->check_out_status === \App\Models\Attendance::CHECK_OUT_COMPENSATED) {
                                    $outStatusBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    $outStatusLabel = 'Lunas Toleransi';
                                } elseif ($att->check_out_status === \App\Models\Attendance::CHECK_OUT_UNCOMPENSATED) {
                                    $outStatusBadgeClass = 'bg-amber-50 text-amber-800 border-amber-200';
                                    $outStatusLabel = 'Belum Ganti Jam';
                                } elseif ($att->isPsw()) {
                                    $outStatusBadgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                                    $outStatusLabel = 'PSW (-'.$att->early_leave_minutes.'m)';
                                } else {
                                    $outStatusBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    $outStatusLabel = 'Sesuai Jadwal';
                                }
                            }
                        @endphp

                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3 transition hover:border-blue-200">
                            <!-- Card Header: Date & Badges -->
                            <div class="flex items-center justify-between border-b border-gray-100 pb-2.5">
                                <div class="flex items-center space-x-2">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $att->isLateTl() || $att->isPsw() ? 'bg-rose-500' : ($att->isTolerance() ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
                                    <span class="text-xs font-bold text-gray-900">{{ $att->formatted_date }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md border {{ $inStatusBadgeClass }}">
                                        {{ $inStatusLabel }}
                                    </span>
                                </div>
                            </div>

                            <!-- Position & Shift Info -->
                            <div class="flex items-center justify-between text-[11px] text-gray-500">
                                <span class="font-semibold text-gray-800 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-[#0058A8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    {{ $att->position?->name ?? 'PPNPN' }}
                                </span>
                                <span class="text-[10px] font-medium bg-gray-100 px-2 py-0.5 rounded-full text-gray-600">
                                    {{ $att->shift_type === 'SECURITY_NIGHT' ? 'Shift Malam Lintas Hari' : ($att->shift_type === 'SECURITY_DAY' ? 'Shift Satpam Siang' : 'Shift Normal') }}
                                </span>
                            </div>

                            <!-- Times & Photos Grid -->
                            <div class="grid grid-cols-2 gap-2.5 bg-gray-50/70 rounded-xl p-3 border border-gray-100">
                                <!-- Check-in Column -->
                                <div class="space-y-1.5 border-r border-gray-200/60 pr-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] uppercase font-bold text-gray-400">Jam Masuk</span>
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    </div>
                                    <div class="text-sm font-extrabold font-mono text-gray-900">
                                        {{ $att->check_in_time_formatted }}
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($att->check_in_photo_url)
                                            <img 
                                                src="{{ $att->check_in_photo_url }}" 
                                                alt="Swafoto Masuk" 
                                                class="w-8 h-10 object-cover rounded-md border border-gray-200 cursor-pointer shadow-2xs hover:opacity-90"
                                                @click="openDetail({{ json_encode($att) }})"
                                            >
                                        @else
                                            <div class="w-8 h-10 bg-gray-200 rounded-md flex items-center justify-center text-[9px] text-gray-400">
                                                N/A
                                            </div>
                                        @endif
                                        <div class="text-[10px] text-gray-500 leading-tight">
                                            @if($att->late_minutes > 0)
                                                <span class="text-rose-600 font-bold block">+{{ $att->late_minutes }}m</span>
                                            @else
                                                <span class="text-emerald-600 font-medium block">Tepat</span>
                                            @endif
                                            <span>GPS: {{ round($att->check_in_distance_meters) }}m</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Check-out Column -->
                                <div class="space-y-1.5 pl-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] uppercase font-bold text-gray-400">Jam Pulang</span>
                                        <span class="w-2 h-2 rounded-full {{ $att->isCheckedOut() ? 'bg-[#0058A8]' : 'bg-gray-300' }}"></span>
                                    </div>
                                    <div class="text-sm font-extrabold font-mono text-gray-900">
                                        {{ $att->check_out_time_formatted ?? '--:-- WIB' }}
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($att->check_out_photo_url)
                                            <img 
                                                src="{{ $att->check_out_photo_url }}" 
                                                alt="Swafoto Pulang" 
                                                class="w-8 h-10 object-cover rounded-md border border-gray-200 cursor-pointer shadow-2xs hover:opacity-90"
                                                @click="openDetail({{ json_encode($att) }})"
                                            >
                                        @else
                                            <div class="w-8 h-10 bg-gray-200 rounded-md flex items-center justify-center text-[9px] text-gray-400">
                                                N/A
                                            </div>
                                        @endif
                                        <div class="text-[10px] text-gray-500 leading-tight">
                                            <span class="font-bold block {{ $att->isCheckedOut() ? ($att->isPsw() ? 'text-rose-600' : 'text-emerald-600') : 'text-slate-400' }}">
                                                {{ $outStatusLabel }}
                                            </span>
                                            @if($att->check_out_distance_meters)
                                                <span>GPS: {{ round($att->check_out_distance_meters) }}m</span>
                                            @else
                                                <span>-</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Card Action: Detail Trigger -->
                            <div class="flex items-center justify-between pt-1">
                                <div class="text-[10px] text-gray-400 truncate max-w-[200px]">
                                    {{ $att->notes ?? 'Presensi terverifikasi Kemenkeu' }}
                                </div>
                                <button 
                                    type="button" 
                                    @click="openDetail({{ json_encode($att) }})"
                                    class="text-[11px] font-bold text-[#0058A8] hover:text-[#004787] flex items-center gap-1 cursor-pointer"
                                >
                                    <span>Bukti & Detail</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Detail Modal Dialog (Alpine.js) -->
        <div 
            x-show="showDetailModal" 
            x-cloak 
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
            @keydown.escape.window="closeDetail()"
        >
            <div 
                class="bg-white rounded-3xl max-w-sm w-full p-5 space-y-4 shadow-2xl border border-gray-100 transform transition-all"
                @click.away="closeDetail()"
            >
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h4 class="font-bold text-gray-900 text-sm" x-text="selectedItem?.formatted_date"></h4>
                        <span class="text-[11px] text-gray-400 font-medium" x-text="selectedItem?.position?.name"></span>
                    </div>
                    <button 
                        type="button" 
                        @click="closeDetail()" 
                        class="w-7 h-7 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 flex items-center justify-center cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Photos Comparison -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5 text-center">
                        <span class="text-[10px] font-bold text-gray-500 uppercase block">Swafoto Masuk</span>
                        <template x-if="selectedItem?.check_in_photo_url">
                            <img :src="selectedItem?.check_in_photo_url" alt="Swafoto Masuk" class="w-full h-36 object-cover rounded-xl border border-gray-200 shadow-xs">
                        </template>
                        <template x-if="!selectedItem?.check_in_photo_url">
                            <div class="w-full h-36 bg-gray-100 rounded-xl flex items-center justify-center text-xs text-gray-400 border border-dashed border-gray-200">
                                Tidak Tersedia
                            </div>
                        </template>
                        <span class="text-[10px] font-mono text-gray-600 font-bold block" x-text="selectedItem?.check_in_time_full"></span>
                    </div>

                    <div class="space-y-1.5 text-center">
                        <span class="text-[10px] font-bold text-gray-500 uppercase block">Swafoto Pulang</span>
                        <template x-if="selectedItem?.check_out_photo_url">
                            <img :src="selectedItem?.check_out_photo_url" alt="Swafoto Pulang" class="w-full h-36 object-cover rounded-xl border border-gray-200 shadow-xs">
                        </template>
                        <template x-if="!selectedItem?.check_out_photo_url">
                            <div class="w-full h-36 bg-gray-100 rounded-xl flex items-center justify-center text-xs text-gray-400 border border-dashed border-gray-200">
                                Belum Pulang
                            </div>
                        </template>
                        <span class="text-[10px] font-mono text-gray-600 font-bold block" x-text="selectedItem?.check_out_time_full || 'Belum Presensi'"></span>
                    </div>
                </div>

                <!-- Details Specs List -->
                <div class="bg-gray-50 rounded-xl p-3.5 text-xs space-y-2 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Status Masuk</span>
                        <span class="font-bold text-gray-900" x-text="selectedItem?.status"></span>
                    </div>
                    <div class="flex items-center justify-between" x-show="selectedItem?.late_minutes > 0">
                        <span class="text-gray-500">Menit Keterlambatan</span>
                        <span class="font-bold text-rose-600" x-text="selectedItem?.late_minutes + ' Menit'"></span>
                    </div>
                    <div class="flex items-center justify-between" x-show="selectedItem?.required_compensation_minutes > 0">
                        <span class="text-gray-500">Kewajiban Penggantian</span>
                        <span class="font-bold text-amber-700" x-text="selectedItem?.required_compensation_minutes + ' Menit'"></span>
                    </div>
                    <div class="flex items-center justify-between" x-show="selectedItem?.deduction_percentage > 0">
                        <span class="text-gray-500">Potongan Keterlambatan</span>
                        <span class="font-bold text-rose-600" x-text="selectedItem?.deduction_percentage + '% (' + selectedItem?.tl_category + ')'"></span>
                    </div>
                    <div class="flex items-center justify-between" x-show="selectedItem?.check_out_status">
                        <span class="text-gray-500">Status Kepulangan</span>
                        <span class="font-bold text-gray-900" x-text="selectedItem?.check_out_status"></span>
                    </div>
                    <div class="flex items-center justify-between" x-show="selectedItem?.early_leave_minutes > 0">
                        <span class="text-gray-500">Pulang Lebih Awal</span>
                        <span class="font-bold text-rose-600" x-text="selectedItem?.early_leave_minutes + ' Menit (PSW)'"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Jarak Masuk ke Kantor</span>
                        <span class="font-bold text-gray-900" x-text="(selectedItem?.check_in_distance_meters || '0') + ' meter'"></span>
                    </div>
                    <div class="flex items-center justify-between" x-show="selectedItem?.check_out_distance_meters">
                        <span class="text-gray-500">Jarak Pulang ke Kantor</span>
                        <span class="font-bold text-gray-900" x-text="selectedItem?.check_out_distance_meters + ' meter'"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Titik Geofence</span>
                        <span class="font-bold text-gray-900">Kanwil DJPb Lampung</span>
                    </div>
                </div>

                <!-- Notes Banner -->
                <div class="p-3 bg-blue-50/70 border border-blue-200/80 rounded-xl text-xs text-blue-900 leading-relaxed">
                    <span class="font-bold block text-[11px] text-blue-950">Catatan Sistem Presensi:</span>
                    <span x-text="selectedItem?.notes || 'Presensi terverifikasi resmi oleh sistem biometrik Kemenkeu.'"></span>
                </div>

                <!-- Close Button -->
                <button 
                    type="button" 
                    @click="closeDetail()" 
                    class="w-full py-2.5 px-4 bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold rounded-xl transition cursor-pointer"
                >
                    Tutup Rincian
                </button>
            </div>
        </div>
    </div>
</x-layouts.mobile>
