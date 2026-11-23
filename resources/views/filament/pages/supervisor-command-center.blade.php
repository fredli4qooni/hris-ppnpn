@use('App\Models\Attendance')
<x-filament-panels::page>
    <style>
        .scc-container { font-family: inherit; }
        .scc-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
        }
        .scc-badge-pulse {
            animation: scc-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes scc-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .85; transform: scale(1.02); }
        }
    </style>

    @php
        $metrics = $this->getMetrics();
        $lateAttendances = $this->getLateAttendances();
        $teamRoster = $this->getTeamRoster();
        $fallbackQueue = $this->getFallbackQueue();
        $leaveQueue = $this->getLeaveQueue();
        $violationQueue = $this->getViolationDraftQueue();
        $positions = $this->getPositions();
    @endphp

    <div class="scc-container space-y-6">
        <!-- Control Header & Filters -->
        <div class="scc-card">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                <div>
                    <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></span>
                        <span>Pusat Kendali Operasional Supervisor Lapangan</span>
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Pemantauan langsung kehadiran personil harian, deteksi keterlambatan, & eksekusi antrean tindakan cepat (FR-702)
                    </p>
                </div>

                <!-- Global Action Badges -->
                <div class="flex items-center gap-2 flex-wrap">
                    @if($metrics['total_queue'] > 0)
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300 flex items-center gap-1.5 scc-badge-pulse">
                            <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                            <span>{{ $metrics['total_queue'] }} Tugas Butuh Tindakan Segera</span>
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1.5">
                            <span>✓</span>
                            <span>Seluruh Antrean Selesai Ditelaah</span>
                        </span>
                    @endif
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4">
                <!-- Date Filter -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tanggal Presensi</label>
                    <input 
                        type="date" 
                        wire:model.live="selectedDate" 
                        class="w-full text-xs font-semibold bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-gray-900 focus:ring-2 focus:ring-[#0058A8] focus:border-[#0058A8]"
                    />
                </div>

                <!-- Shift Filter -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Shift Tugas</label>
                    <select 
                        wire:model.live="selectedShift" 
                        class="w-full text-xs font-semibold bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-gray-900 focus:ring-2 focus:ring-[#0058A8] focus:border-[#0058A8]"
                    >
                        <option value="ALL">Semua Shift</option>
                        <option value="NORMAL">Shift Normal (07:30 - 17:00)</option>
                        <option value="SECURITY_DAY">Satpam Regu Pagi (07:00 - 15:00)</option>
                        <option value="SECURITY_NIGHT">Satpam Regu Malam (23:00 - 07:00)</option>
                    </select>
                </div>

                <!-- Position Filter -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Posisi / Kategori Penugasan</label>
                    <select 
                        wire:model.live="selectedPositionId" 
                        class="w-full text-xs font-semibold bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-gray-900 focus:ring-2 focus:ring-[#0058A8] focus:border-[#0058A8]"
                    >
                        <option value="">Semua Posisi</option>
                        @foreach($positions as $pos)
                            <option value="{{ $pos->id }}">{{ $pos->name }} ({{ $pos->code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Live KPI Metrics Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <!-- Total Anggota Regu -->
            <div class="scc-card border-l-4 border-l-[#0058A8]">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Total Regu</span>
                <p class="text-xl font-black text-[#0058A8] mt-1">{{ $metrics['total_subordinates'] }}</p>
                <span class="text-[10px] text-gray-500">Personil Aktif</span>
            </div>

            <!-- Tepat Waktu -->
            <div class="scc-card border-l-4 border-l-emerald-500">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Tepat Waktu</span>
                <p class="text-xl font-black text-emerald-600 mt-1">{{ $metrics['on_time'] }}</p>
                <span class="text-[10px] text-emerald-600 font-semibold">Sebelum 07:30</span>
            </div>

            <!-- Toleransi Penggantian Jam -->
            <div class="scc-card border-l-4 border-l-amber-500">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Toleransi</span>
                <p class="text-xl font-black text-amber-600 mt-1">{{ $metrics['tolerance'] }}</p>
                <span class="text-[10px] text-amber-600 font-semibold">07:31 - 07:45 (+Ganti Jam)</span>
            </div>

            <!-- Terlambat TL (Danger Alert) -->
            <div class="scc-card border-l-4 border-l-rose-600 bg-rose-50/20">
                <span class="text-[10px] font-bold text-rose-600 uppercase tracking-wider block">Terlambat TL</span>
                <div class="flex items-center gap-1.5 mt-1">
                    <p class="text-xl font-black text-rose-700">{{ $metrics['late'] }}</p>
                    @if($metrics['late'] > 0)
                        <span class="w-2 h-2 rounded-full bg-rose-600 animate-ping"></span>
                    @endif
                </div>
                <span class="text-[10px] text-rose-600 font-semibold">Potongan Honor Aktif</span>
            </div>

            <!-- Cuti / Izin / Sakit -->
            <div class="scc-card border-l-4 border-l-blue-400">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Cuti / Sakit</span>
                <p class="text-xl font-black text-blue-600 mt-1">{{ $metrics['leave'] }}</p>
                <span class="text-[10px] text-blue-600 font-semibold">Izin Sah</span>
            </div>

            <!-- Belum Hadir / Alpa -->
            <div class="scc-card border-l-4 border-l-slate-400">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Belum Hadir</span>
                <p class="text-xl font-black text-slate-700 mt-1">{{ $metrics['not_present'] }}</p>
                <span class="text-[10px] text-slate-500">Tanpa Keterangan</span>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex border-b border-gray-200 space-x-1 sm:space-x-2 overflow-x-auto text-xs font-bold">
            <button 
                type="button" 
                wire:click="$set('activeTab', 'live')" 
                class="px-4 py-2.5 rounded-t-xl transition whitespace-nowrap {{ $activeTab === 'live' ? 'bg-[#0058A8] text-white shadow-xs' : 'text-gray-500 hover:text-gray-900 bg-gray-100' }}"
            >
                Live Kehadiran Hari Ini ({{ $teamRoster->count() }})
            </button>

            <button 
                type="button" 
                wire:click="$set('activeTab', 'late')" 
                class="px-4 py-2.5 rounded-t-xl transition whitespace-nowrap flex items-center gap-1.5 {{ $activeTab === 'late' ? 'bg-rose-600 text-white shadow-xs' : 'text-gray-500 hover:text-gray-900 bg-gray-100' }}"
            >
                <span>Personil Terlambat (TL)</span>
                @if($lateAttendances->count() > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $activeTab === 'late' ? 'bg-white text-rose-700' : 'bg-rose-600 text-white' }} scc-badge-pulse">
                        {{ $lateAttendances->count() }}
                    </span>
                @endif
            </button>

            <button 
                type="button" 
                wire:click="$set('activeTab', 'queue_fallback')" 
                class="px-4 py-2.5 rounded-t-xl transition whitespace-nowrap flex items-center gap-1.5 {{ $activeTab === 'queue_fallback' ? 'bg-[#0058A8] text-white shadow-xs' : 'text-gray-500 hover:text-gray-900 bg-gray-100' }}"
            >
                <span>Antrean Fallback Biometrik</span>
                @if($fallbackQueue->count() > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $activeTab === 'queue_fallback' ? 'bg-white text-[#0058A8]' : 'bg-rose-500 text-white' }}">
                        {{ $fallbackQueue->count() }}
                    </span>
                @endif
            </button>

            <button 
                type="button" 
                wire:click="$set('activeTab', 'queue_leave')" 
                class="px-4 py-2.5 rounded-t-xl transition whitespace-nowrap flex items-center gap-1.5 {{ $activeTab === 'queue_leave' ? 'bg-[#0058A8] text-white shadow-xs' : 'text-gray-500 hover:text-gray-900 bg-gray-100' }}"
            >
                <span>Antrean Telaah Cuti</span>
                @if($leaveQueue->count() > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $activeTab === 'queue_leave' ? 'bg-white text-[#0058A8]' : 'bg-amber-500 text-white' }}">
                        {{ $leaveQueue->count() }}
                    </span>
                @endif
            </button>

            <button 
                type="button" 
                wire:click="$set('activeTab', 'queue_violation')" 
                class="px-4 py-2.5 rounded-t-xl transition whitespace-nowrap flex items-center gap-1.5 {{ $activeTab === 'queue_violation' ? 'bg-[#0058A8] text-white shadow-xs' : 'text-gray-500 hover:text-gray-900 bg-gray-100' }}"
            >
                <span>Antrean Draf Pelanggaran</span>
                @if($violationQueue->count() > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $activeTab === 'queue_violation' ? 'bg-white text-[#0058A8]' : 'bg-purple-600 text-white' }}">
                        {{ $violationQueue->count() }}
                    </span>
                @endif
            </button>
        </div>

        <!-- TAB 1: Live Kehadiran Hari Ini -->
        @if($activeTab === 'live')
            <div class="scc-card space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-sm text-gray-900">
                        Status Presensi Seluruh Regu Kerja ({{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }})
                    </h3>
                    <span class="text-xs text-gray-500">
                        Total Ditampilkan: <strong>{{ $teamRoster->count() }}</strong> personil
                    </span>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200 text-[11px] uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-3">Pegawai / NIP</th>
                                <th class="py-3 px-3">Posisi & Shift</th>
                                <th class="py-3 px-3">Jam Masuk</th>
                                <th class="py-3 px-3">Jam Pulang</th>
                                <th class="py-3 px-3">Jarak Geofence</th>
                                <th class="py-3 px-3">Status Kehadiran</th>
                                <th class="py-3 px-3">Verifikasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($teamRoster as $emp)
                                @php
                                    /** @var Attendance|null $att */
                                    $att = $emp->attendances->first();
                                @endphp
                                <tr class="hover:bg-blue-50/20 transition">
                                    <td class="py-3 px-3">
                                        <div class="font-bold text-gray-900">{{ $emp->full_name }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $emp->employee_code }}</div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="font-semibold text-gray-800">{{ $emp->position?->name ?? 'PPNPN' }}</span>
                                        <span class="text-[10px] text-gray-400 block">{{ $att?->shift_type ?? 'NORMAL' }}</span>
                                    </td>
                                    <td class="py-3 px-3">
                                        @if($att && $att->check_in_at)
                                            <span class="font-bold text-gray-900">{{ $att->check_in_at->format('H:i') }} WIB</span>
                                            @if($att->late_minutes > 0)
                                                <span class="text-[10px] text-rose-600 font-semibold block">+{{ $att->late_minutes }}m TL</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400 italic">Belum Masuk</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        @if($att && $att->check_out_at)
                                            <span class="font-bold text-gray-900">{{ $att->check_out_at->format('H:i') }} WIB</span>
                                        @else
                                            <span class="text-gray-400 italic">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        @if($att && $att->check_in_distance_meters !== null)
                                            <span class="font-semibold {{ $att->check_in_distance_meters <= 100 ? 'text-emerald-700' : 'text-rose-700' }}">
                                                {{ (float) $att->check_in_distance_meters }} m
                                            </span>
                                            <span class="text-[10px] text-gray-400 block">{{ $att->check_in_distance_meters <= 100 ? 'Radius Sah' : 'Luar Radius' }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        @if($att)
                                            @if($att->status === Attendance::STATUS_ON_TIME)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Tepat Waktu</span>
                                            @elseif($att->status === Attendance::STATUS_TOLERANCE)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Toleransi</span>
                                            @elseif($att->status === Attendance::STATUS_LATE_TL)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-300">Terlambat (TL)</span>
                                            @elseif(in_array($att->status, [Attendance::STATUS_LEAVE, Attendance::STATUS_PERMIT, Attendance::STATUS_SICK]))
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">{{ $att->getStatusLabel() }}</span>
                                            @elseif($att->status === Attendance::STATUS_ALPHA)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">Alpa</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700">{{ $att->getStatusLabel() }}</span>
                                            @endif
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500">Belum Presensi</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        @if($att && $att->is_fallback)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200" title="{{ $att->fallback_reason }}">
                                                Fallback Kamera
                                            </span>
                                        @elseif($att && $att->check_in_at)
                                            <span class="text-[10px] text-emerald-600 font-semibold">Biometrik Wajah</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-gray-500 text-xs">
                                        Tidak ada personil dalam cakupan regu kerja untuk filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- TAB 2: Personil Terlambat Hari Ini (Visual Merah Menyala) -->
        @if($activeTab === 'late')
            <div class="scc-card space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="font-bold text-sm text-rose-800 flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-rose-600 animate-ping"></span>
                            <span>Daftar Personil Terlambat Masuk Kerja (TL) Hari Ini</span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Personil dengan jam kehadiran melebihi batas toleransi 07:45 WIB yang memicu pemotongan honor otomatis.
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-300 scc-badge-pulse">
                        {{ $lateAttendances->count() }} Personil Terlambat
                    </span>
                </div>

                <div class="overflow-x-auto border border-rose-200 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-rose-50 text-rose-900 font-bold border-b border-rose-200 text-[11px] uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-3">Pegawai / NIP</th>
                                <th class="py-3 px-3">Posisi</th>
                                <th class="py-3 px-3">Jam Masuk Riil</th>
                                <th class="py-3 px-3">Durasi Terlambat</th>
                                <th class="py-3 px-3">Kategori Sanksi TL</th>
                                <th class="py-3 px-3">Penanda Visual</th>
                                <th class="py-3 px-3">Dispensasi Kahar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rose-100 bg-white">
                            @forelse($lateAttendances as $lateAtt)
                                <tr class="hover:bg-rose-50/30 transition">
                                    <td class="py-3 px-3">
                                        <div class="font-bold text-gray-900">{{ $lateAtt->employee?->full_name }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $lateAtt->employee?->employee_code }}</div>
                                    </td>
                                    <td class="py-3 px-3 font-semibold text-gray-800">
                                        {{ $lateAtt->employee?->position?->name ?? 'PPNPN' }}
                                    </td>
                                    <td class="py-3 px-3 font-bold text-rose-700">
                                        {{ $lateAtt->check_in_at?->format('H:i') }} WIB
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="font-black text-rose-800 text-sm">{{ $lateAtt->late_minutes }} Menit</span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                            {{ $lateAtt->getTlCategoryLabel() }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-600 text-white shadow-xs scc-badge-pulse">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                            <span>TERLAMBAT TL</span>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3">
                                        @if($lateAtt->lateExceptions->isNotEmpty())
                                            @php $disp = $lateAtt->lateExceptions->first(); @endphp
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $disp->status === 'APPROVED' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                {{ $disp->getStatusLabel() }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 italic text-[11px]">Belum Ada Pengajuan</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-emerald-700 text-xs font-semibold bg-emerald-50/30">
                                        Luar biasa! Tidak ada personil regu kerja yang terlambat pada tanggal ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- TAB 3: Antrean Fallback Biometrik Darurat (FR-209) -->
        @if($activeTab === 'queue_fallback')
            <div class="scc-card space-y-4">
                <div class="border-b border-gray-100 pb-3">
                    <h3 class="font-bold text-sm text-gray-900 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-purple-600"></span>
                        <span>Antrean Verifikasi Swafoto Kamera Darurat (Biometric Fallback)</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Verifikasi keabsahan kehadiran personil yang menggunakan swafoto darurat setelah 3x gagal deteksi biometrik wajah.
                    </p>
                </div>

                <div class="space-y-3">
                    @forelse($fallbackQueue as $fb)
                        <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex items-start space-x-3.5">
                                @if($fb->check_in_photo_path)
                                    <img 
                                        src="{{ asset('storage/' . $fb->check_in_photo_path) }}" 
                                        alt="Swafoto" 
                                        class="w-16 h-16 rounded-xl object-cover border border-gray-300 shadow-xs shrink-0"
                                    />
                                @else
                                    <div class="w-16 h-16 rounded-xl bg-gray-200 flex items-center justify-center text-gray-400 text-xs font-bold shrink-0">
                                        Foto N/A
                                    </div>
                                @endif

                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-sm text-gray-900">{{ $fb->employee?->full_name }}</h4>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-100 text-[#0058A8]">
                                            {{ $fb->employee?->position?->name }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1">
                                        <strong>Waktu:</strong> {{ $fb->check_in_at?->translatedFormat('d M Y, H:i') }} WIB &bull; 
                                        <strong>Jarak Geofence:</strong> {{ (float) $fb->check_in_distance_meters }}m
                                    </p>
                                    <p class="text-xs text-purple-800 bg-purple-50 border border-purple-100 rounded-lg p-2 mt-1.5 font-medium">
                                        <strong>Alasan Kendala:</strong> {{ $fb->fallback_reason }}
                                    </p>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-2 self-end md:self-center shrink-0">
                                <button 
                                    type="button" 
                                    wire:click="approveFallback({{ $fb->id }})" 
                                    wire:confirm="Apakah Anda yakin ingin mengesahkan presensi swafoto darurat ini?"
                                    class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition shadow-xs cursor-pointer"
                                >
                                    ✓ Sahkan Presensi
                                </button>

                                <button 
                                    type="button" 
                                    wire:click="rejectFallback({{ $fb->id }}, 'Swafoto tidak jelas atau tidak memenuhi syarat')" 
                                    wire:confirm="Tolak swafoto ini? Presensi pegawai akan diubah menjadi ALPA."
                                    class="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition shadow-xs cursor-pointer"
                                >
                                    ✕ Tolak (Jadikan ALPA)
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-gray-500 text-xs">
                            Tidak ada pengajuan swafoto fallback yang menunggu verifikasi saat ini.
                        </div>
                    @endforelse
                </div>
            </div>
        @endif

        <!-- TAB 4: Antrean Telaah Cuti Tahap 1 (FR-504) -->
        @if($activeTab === 'queue_leave')
            <div class="scc-card space-y-4">
                <div class="border-b border-gray-100 pb-3">
                    <h3 class="font-bold text-sm text-gray-900 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <span>Antrean Verifikasi Faktual Permohonan Cuti / Izin (Tahap 1 Supervisor)</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Telaah fakta lapangan atas permohonan izin/cuti personil sebelum diteruskan untuk putusan final Atasan.
                    </p>
                </div>

                <div class="space-y-3">
                    @forelse($leaveQueue as $lq)
                        <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-sm text-gray-900">{{ $lq->employee?->full_name }}</h4>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-100 text-[#0058A8]">
                                        {{ $lq->getTypeLabel() }}
                                    </span>
                                    <span class="text-[10px] font-black px-2 py-0.5 rounded bg-amber-100 text-amber-800">
                                        {{ $lq->total_days }} Hari Kerja
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600 mt-1">
                                    <strong>Rentang Tanggal:</strong> {{ $lq->start_date?->translatedFormat('d M Y') }} s.d. {{ $lq->end_date?->translatedFormat('d M Y') }}
                                </p>
                                <p class="text-xs text-gray-700 bg-white border border-gray-200 rounded-lg p-2 mt-1.5">
                                    <strong>Alasan Pengajuan:</strong> {{ $lq->reason }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2 self-end md:self-center shrink-0">
                                <button 
                                    type="button" 
                                    wire:click="verifyLeave({{ $lq->id }}, true)" 
                                    wire:confirm="Setujui dan teruskan permohonan izin ini ke Atasan?"
                                    class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition shadow-xs cursor-pointer"
                                >
                                    ✓ Setujui (Teruskan)
                                </button>

                                <button 
                                    type="button" 
                                    wire:click="verifyLeave({{ $lq->id }}, false, 'Ditolak oleh Supervisor Lapangan karena kebutuhan operasional')" 
                                    wire:confirm="Apakah Anda yakin ingin menolak permohonan izin ini?"
                                    class="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition shadow-xs cursor-pointer"
                                >
                                    ✕ Tolak
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-gray-500 text-xs">
                            Tidak ada permohonan cuti atau izin yang menunggu verifikasi supervisor.
                        </div>
                    @endforelse
                </div>
            </div>
        @endif

        <!-- TAB 5: Antrean Draf Pelanggaran Otomatis (FR-406) -->
        @if($activeTab === 'queue_violation')
            <div class="scc-card space-y-4">
                <div class="border-b border-gray-100 pb-3">
                    <h3 class="font-bold text-sm text-gray-900 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-purple-600"></span>
                        <span>Antrean Konfirmasi Draf Pelanggaran Otomatis (Violation Engine)</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Draf pelanggaran terdeteksi otomatis oleh sistem (Alpa berulang, koreksi ditolak, PSW) yang butuh pengesahan supervisor.
                    </p>
                </div>

                <div class="space-y-3">
                    @forelse($violationQueue as $vq)
                        <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-sm text-gray-900">{{ $vq->employee?->full_name }}</h4>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-purple-100 text-purple-800">
                                        Pemicu: {{ $vq->trigger_type }}
                                    </span>
                                    <span class="text-[10px] font-black px-2 py-0.5 rounded bg-rose-100 text-rose-800">
                                        +{{ $vq->catalog?->points ?? 0 }} Poin
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600 mt-1">
                                    <strong>Butir SPK:</strong> {{ $vq->catalog?->clause_number }} - {{ $vq->catalog?->name }} &bull;
                                    <strong>Tanggal Kejadian:</strong> {{ $vq->incident_date?->translatedFormat('d M Y') }}
                                </p>
                                <p class="text-xs text-gray-700 bg-white border border-gray-200 rounded-lg p-2 mt-1.5">
                                    <strong>Catatan Mesin Otomatis:</strong> {{ $vq->auto_generated_notes }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2 self-end md:self-center shrink-0">
                                <button 
                                    type="button" 
                                    wire:click="confirmViolationDraft({{ $vq->id }})" 
                                    wire:confirm="Sahkan draf ini menjadi catatan pelanggaran aktif pada Buku Disiplin?"
                                    class="px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs transition shadow-xs cursor-pointer"
                                >
                                    ✓ Sahkan Pelanggaran Aktif
                                </button>

                                <button 
                                    type="button" 
                                    wire:click="dismissViolationDraft({{ $vq->id }}, 'Telah diklarifikasi dengan bukti sah lapangan')" 
                                    wire:confirm="Gugurkan draf pelanggaran ini? Data tidak akan dicatat pada saldo sanksi."
                                    class="px-3 py-1.5 rounded-xl bg-gray-500 hover:bg-gray-600 text-white font-bold text-xs transition shadow-xs cursor-pointer"
                                >
                                    ✕ Gugurkan Draf
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-gray-500 text-xs">
                            Tidak ada draf pelanggaran otomatis yang menunggu konfirmasi supervisor.
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
