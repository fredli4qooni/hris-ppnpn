@use('App\Models\MonthlyPeriodClosing')
<x-filament-panels::page>
    <style>
        .egd-container { font-family: inherit; }
        .egd-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
        }
        .egd-pulse {
            animation: egd-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes egd-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .85; transform: scale(1.02); }
        }
    </style>

    @php
        $budget = $this->getBudgetEfficiencySummary();
        $compliance = $this->getDivisionCompliance();
        $activeSanctions = $this->getActiveSanctions();
        $expiringContracts = $this->getExpiringContracts();
    @endphp

    <div class="egd-container space-y-6">
        <!-- Control Header & Period Selector -->
        <div class="egd-card">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                <div>
                    <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-[#0058A8]"></span>
                        <span>Dasbor Tata Kelola Eksekutif & Analitik Pengawasan (PPK / Kasubbag Umum)</span>
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Pemantauan tren kehadiran agregat divisi, efisiensi anggaran belanja honor, sanksi aktif, & siklus kontrak (FR-703)
                    </p>
                </div>

                <!-- Period Selector -->
                <div class="flex items-center gap-2.5">
                    <div>
                        <select 
                            wire:model.live="selectedMonth" 
                            class="text-xs font-semibold bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-gray-900 focus:ring-2 focus:ring-[#0058A8] focus:border-[#0058A8]"
                        >
                            @foreach(MonthlyPeriodClosing::MONTH_NAMES as $num => $name)
                                <option value="{{ $num }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <select 
                            wire:model.live="selectedYear" 
                            class="text-xs font-semibold bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-gray-900 focus:ring-2 focus:ring-[#0058A8] focus:border-[#0058A8]"
                        >
                            @for($y = 2024; $y <= 2027; $y++)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    @if($budget['is_locked'])
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                            🔒 Terkunci (READ ONLY)
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            🔓 Periode Terbuka
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Metric Cards: Efisiensi Anggaran Belanja PPNPN (Kriteria 3) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Pagu PPNPN Bruto -->
            <div class="egd-card border-l-4 border-l-[#0058A8]">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Pagu Honor Bruto</span>
                <p class="text-xl font-black text-[#0058A8] mt-1">
                    Rp {{ number_format($budget['total_base_salary'], 0, ',', '.') }}
                </p>
                <span class="text-[10px] text-gray-500">Anggaran Belanja Pokok PPNPN</span>
            </div>

            <!-- Efisiensi Akumulasi 1 (Keterlambatan TL) -->
            <div class="egd-card border-l-4 border-l-amber-500">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Efisiensi Akumulasi 1 (TL)</span>
                <p class="text-xl font-black text-amber-700 mt-1">
                    Rp {{ number_format($budget['total_tl_deduction'], 0, ',', '.') }}
                </p>
                <span class="text-[10px] text-amber-700 font-semibold">Pemotongan Keterlambatan Denda TL</span>
            </div>

            <!-- Efisiensi Akumulasi 2 (Sanksi SP) -->
            <div class="egd-card border-l-4 border-l-purple-600">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Efisiensi Akumulasi 2 (SP)</span>
                <p class="text-xl font-black text-purple-700 mt-1">
                    Rp {{ number_format($budget['total_sp_deduction'], 0, ',', '.') }}
                </p>
                <span class="text-[10px] text-purple-700 font-semibold">Pemotongan Sanksi SP Aktif</span>
            </div>

            <!-- Total Efisiensi Anggaran Belanja -->
            <div class="egd-card border-l-4 border-l-emerald-600 bg-emerald-50/15">
                <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider block">Total Nilai Efisiensi (Hemat)</span>
                <p class="text-xl font-black text-emerald-700 mt-1">
                    Rp {{ number_format($budget['total_efficiency'], 0, ',', '.') }}
                </p>
                <span class="text-[10px] text-emerald-700 font-bold">
                    {{ $budget['efficiency_percentage'] }}% dari Pagu &bull; Net: Rp {{ number_format($budget['total_net_salary'], 0, ',', '.') }}
                </span>
            </div>
        </div>

        <!-- Section: Grafik Analitik Tren Kepatuhan Kehadiran Per Divisi (Kriteria 1) -->
        <div class="egd-card space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div>
                    <h3 class="font-bold text-sm text-gray-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#0058A8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <span>Tingkat Kepatuhan Presensi Per Divisi / Posisi Penugasan</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Agregat kehadiran tepat waktu & toleransi dibandingkan pelanggaran keterlambatan serta alpa pada bulan bersangkutan.
                    </p>
                </div>
                <span class="text-xs font-semibold text-gray-500">
                    Periode: <strong>{{ MonthlyPeriodClosing::MONTH_NAMES[$selectedMonth] }} {{ $selectedYear }}</strong>
                </span>
            </div>

            <!-- Horizontal Compliance Progress Bars -->
            <div class="space-y-4 pt-2">
                @forelse($compliance as $div)
                    <div class="space-y-1.5 p-3 rounded-xl border border-gray-100 bg-gray-50/50 hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-900">{{ $div['name'] }}</span>
                                <span class="text-[10px] font-semibold text-gray-400">({{ $div['code'] }})</span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-100 text-[#0058A8] font-bold">
                                    {{ $div['employee_count'] }} Personil
                                </span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-gray-500 text-[11px]">
                                    Hadir Sah: <strong>{{ $div['compliant_count'] }}</strong> &bull; 
                                    Terlambat TL: <strong class="text-amber-700">{{ $div['late_count'] }}</strong> &bull; 
                                    Alpa: <strong class="text-rose-700">{{ $div['alpha_count'] }}</strong>
                                </span>
                                <span class="font-black text-sm {{ $div['compliance_rate'] >= 90 ? 'text-emerald-700' : ($div['compliance_rate'] >= 75 ? 'text-amber-700' : 'text-rose-700') }}">
                                    {{ $div['compliance_rate'] }}%
                                </span>
                            </div>
                        </div>

                        <!-- Progress Bar Visual -->
                        <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden flex">
                            <div 
                                class="{{ $div['compliance_rate'] >= 90 ? 'bg-emerald-500' : ($div['compliance_rate'] >= 75 ? 'bg-amber-500' : 'bg-rose-500') }} h-2.5 rounded-full transition-all duration-500" 
                                style="width: {{ $div['compliance_rate'] }}%"
                                title="Kepatuhan {{ $div['compliance_rate'] }}%"
                            ></div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-gray-500">
                        Tidak ada data divisi penugasan yang tercatat.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Section: Widget Pemantauan Personil dengan Sanksi SP Aktif (Kriteria 2) -->
        <div class="egd-card space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div>
                    <h3 class="font-bold text-sm text-gray-900 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-rose-600"></span>
                        <span>Pengawasan Personil Menjalani Masa Sanksi SP Aktif (Discipline Watchlist)</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Daftar pegawai yang sedang dikenakan sanksi Surat Peringatan aktif dengan potongan honor berjalan.
                    </p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $activeSanctions->count() > 0 ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                    {{ $activeSanctions->count() }} Sanksi Berjalan
                </span>
            </div>

            <div class="overflow-x-auto border border-gray-200 rounded-xl">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200 text-[11px] uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-3">Pegawai / NIP</th>
                            <th class="py-3 px-3">Posisi</th>
                            <th class="py-3 px-3">Tingkat Sanksi SP</th>
                            <th class="py-3 px-3">Potongan Honor</th>
                            <th class="py-3 px-3">Snapshot Poin</th>
                            <th class="py-3 px-3">Masa Berlaku Sanksi</th>
                            <th class="py-3 px-3">Status Terminasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($activeSanctions as $sanction)
                            <tr class="hover:bg-rose-50/20 transition">
                                <td class="py-3 px-3">
                                    <div class="font-bold text-gray-900">{{ $sanction->employee?->full_name }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $sanction->employee?->employee_code }}</div>
                                </td>
                                <td class="py-3 px-3 font-semibold text-gray-800">
                                    {{ $sanction->employee?->position?->name ?? 'PPNPN' }}
                                </td>
                                <td class="py-3 px-3 font-bold text-rose-800">
                                    {{ $sanction->getTypeLabel() }}
                                </td>
                                <td class="py-3 px-3">
                                    <span class="font-black text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded">
                                        -{{ (float) $sanction->deduction_percentage }}%
                                    </span>
                                </td>
                                <td class="py-3 px-3 font-bold text-gray-900">
                                    {{ $sanction->total_points_snapshot }} Poin
                                </td>
                                <td class="py-3 px-3">
                                    <span class="font-medium text-gray-700 block">
                                        {{ $sanction->start_date?->format('d/m/Y') }} s.d. {{ $sanction->end_date?->format('d/m/Y') }}
                                    </span>
                                    <span class="text-[10px] text-gray-400 block">
                                        Jatuh tempo: {{ $sanction->end_date?->diffForHumans() }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    @if($sanction->is_termination_recommended || $sanction->sanction_type === 'SP_3')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-600 text-white shadow-xs egd-pulse">
                                            ⚠️ Rekomendasi PHK
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                            Masa Pembinaan
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-emerald-700 text-xs font-semibold bg-emerald-50/20">
                                    ✓ Nihil! Tidak ada personil yang sedang menjalani masa sanksi SP aktif saat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section: Widget Pengawasan Jatuh Tempo Kontrak (H-90 s.d. H-7) (Kriteria 4) -->
        <div class="egd-card space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div>
                    <h3 class="font-bold text-sm text-gray-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span>Kalender Pengawasan Jatuh Tempo Kontrak Kerja SPK (H-90 s.d. H-7)</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Monitoring masa berlaku SPK pegawai yang mendekati kedaluwarsa beserta tombol perpanjangan adendum cepat.
                    </p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $expiringContracts->count() > 0 ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-gray-100 text-gray-600' }}">
                    {{ $expiringContracts->count() }} Kontrak Butuh Perhatian
                </span>
            </div>

            <div class="overflow-x-auto border border-gray-200 rounded-xl">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200 text-[11px] uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-3">Pegawai / NIP</th>
                            <th class="py-3 px-3">Posisi</th>
                            <th class="py-3 px-3">Nomor SPK Aktif</th>
                            <th class="py-3 px-3">Akhir Kontrak</th>
                            <th class="py-3 px-3">Sisa Hari</th>
                            <th class="py-3 px-3">Tingkat Urgensi</th>
                            <th class="py-3 px-3 text-right">Aksi Eksekutif</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($expiringContracts as $expEmp)
                            @php
                                $days = $expEmp->getDaysUntilContractExpiry();
                            @endphp
                            <tr class="hover:bg-amber-50/20 transition">
                                <td class="py-3 px-3">
                                    <div class="font-bold text-gray-900">{{ $expEmp->full_name }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $expEmp->employee_code }}</div>
                                </td>
                                <td class="py-3 px-3 font-semibold text-gray-800">
                                    {{ $expEmp->position?->name ?? 'PPNPN' }}
                                </td>
                                <td class="py-3 px-3 font-mono text-[11px] text-gray-700">
                                    {{ $expEmp->spk_number ?? '-' }}
                                </td>
                                <td class="py-3 px-3 font-semibold text-gray-900">
                                    {{ $expEmp->contract_end_date?->format('d/m/Y') }}
                                </td>
                                <td class="py-3 px-3">
                                    <span class="font-black text-sm {{ $days <= 7 ? 'text-rose-700' : ($days <= 30 ? 'text-amber-700' : 'text-blue-700') }}">
                                        {{ $days < 0 ? 'Kedaluwarsa' : "H-{$days} ({$days} hari)" }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $expEmp->getContractStatusBadgeColor() === 'danger' ? 'bg-rose-100 text-rose-800 border border-rose-300' : ($expEmp->getContractStatusBadgeColor() === 'warning' ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-blue-100 text-blue-800 border border-blue-200') }}">
                                        {{ $expEmp->getContractStatusLabel() }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <button 
                                        type="button" 
                                        wire:click="openExtendModal({{ $expEmp->id }})" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#0058A8] hover:bg-[#004787] text-white font-bold text-xs transition shadow-xs cursor-pointer"
                                    >
                                        <span>Perpanjang Kontrak</span>
                                        <span>&rarr;</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-gray-500 text-xs">
                                    Seluruh masa kontrak kerja personil dalam kondisi aman (> 90 hari kalender).
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Inline Perpanjangan Kontrak Cepat -->
        @if($extendingEmployeeId)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl border border-gray-100">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-sm text-gray-900">
                            Perpanjangan Kontrak Kerja / Adendum SPK PPNPN
                        </h3>
                        <button 
                            type="button" 
                            wire:click="cancelExtendModal" 
                            class="text-gray-400 hover:text-gray-600 text-sm font-bold cursor-pointer"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Nomor SPK Baru</label>
                            <input 
                                type="text" 
                                wire:model="newSpkNumber" 
                                class="w-full text-xs font-semibold bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-gray-900 focus:ring-2 focus:ring-[#0058A8]"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Tanggal Mulai Berlaku</label>
                                <input 
                                    type="date" 
                                    wire:model="newStartDate" 
                                    class="w-full text-xs font-semibold bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-gray-900 focus:ring-2 focus:ring-[#0058A8]"
                                />
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Tanggal Berakhir Kontrak</label>
                                <input 
                                    type="date" 
                                    wire:model="newEndDate" 
                                    class="w-full text-xs font-semibold bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-gray-900 focus:ring-2 focus:ring-[#0058A8]"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Besaran Honor Pokok Bulanan (Rp)</label>
                            <input 
                                type="number" 
                                step="1000" 
                                wire:model="newSalary" 
                                class="w-full text-xs font-semibold bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-gray-900 focus:ring-2 focus:ring-[#0058A8]"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Catatan Telaah Pejabat Penandatangan</label>
                            <textarea 
                                wire:model="extendNotes" 
                                rows="2" 
                                class="w-full text-xs bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-gray-900 focus:ring-2 focus:ring-[#0058A8]"
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                        <button 
                            type="button" 
                            wire:click="cancelExtendModal" 
                            class="px-4 py-2 rounded-xl text-gray-600 hover:bg-gray-100 text-xs font-bold transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <button 
                            type="button" 
                            wire:click="processExtendContract" 
                            class="px-4 py-2 rounded-xl bg-[#0058A8] hover:bg-[#004787] text-white text-xs font-bold transition shadow-xs cursor-pointer"
                        >
                            Simpan & Sahkan Perpanjangan
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
