<x-filament-panels::page>
    <style>
        .tac-select {
            appearance: none;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 7px 32px 7px 12px;
            font-size: 12px;
            font-weight: 600;
            color: #1e293b;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 14px;
            outline: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .tac-select:focus {
            border-color: #0058a8;
            box-shadow: 0 0 0 3px rgba(0, 88, 168, 0.15);
            background-color: #ffffff;
        }
    </style>

    @php
        $payroll = $this->getPayrollData();
        $closing = $payroll['closing'];
        $isLocked = $payroll['is_locked'];
        $items = $payroll['items'];
        $totals = $payroll['totals'];
    @endphp

    <div class="space-y-5">
        <!-- Filter & Navigation Bar -->
        <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-xs">
            <form method="GET" action="{{ route('filament.admin.pages.manage-monthly-closing') }}" class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Pilih Periode:</span>
                    <select name="month" onchange="this.form.submit()" class="tac-select">
                        @foreach(\App\Models\MonthlyPeriodClosing::MONTH_NAMES as $num => $name)
                            <option value="{{ $num }}" {{ $selectedMonth === $num ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>

                    <select name="year" onchange="this.form.submit()" class="tac-select">
                        @foreach([2025, 2026, 2027] as $y)
                            <option value="{{ $y }}" {{ $selectedYear === $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                        @endforeach
                    </select>

                    <noscript>
                        <button type="submit" class="px-3.5 py-1.5 bg-[#0058A8] hover:bg-[#004787] text-white text-xs font-bold rounded-xl transition">
                            Tampilkan Rekap
                        </button>
                    </noscript>
                </div>

                <!-- Lock Status Pill -->
                <div class="flex items-center space-x-2">
                    @if($isLocked)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-full text-xs font-bold">
                            <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                            <span>TERKUNCI (READ ONLY)</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-xs font-bold">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            <span>TERBUKA (DRAF BERJALAN)</span>
                        </span>
                    @endif
                </div>
            </form>
        </div>

        <!-- Status Alert Banner -->
        @if($isLocked)
            <div class="bg-blue-50/80 border border-blue-200 rounded-2xl p-4 flex items-start space-x-3 text-xs text-blue-900 shadow-xs">
                <svg class="w-5 h-5 text-[#0058A8] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                <div>
                    <h4 class="font-bold text-sm text-blue-950">Periode {{ $payroll['period_label'] }} Telah Dikunci Resmi</h4>
                    <p class="mt-0.5 text-blue-800">
                        Dikunci pada {{ $closing?->closed_at?->translatedFormat('d F Y, H:i') }} WIB oleh {{ $closing?->closedBy?->name ?? 'Admin' }}. Seluruh mutasi presensi dan perizinan pada bulan ini dinonaktifkan (READ ONLY permanen).
                    </p>
                    @if($closing?->notes)
                        <p class="mt-1 text-[11px] text-blue-700 italic">Catatan: "{{ $closing->notes }}"</p>
                    @endif
                </div>
            </div>
        @else
            <div class="bg-amber-50/70 border border-amber-200 rounded-2xl p-4 flex items-start space-x-3 text-xs text-amber-900 shadow-xs">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div>
                    <h4 class="font-bold text-sm text-amber-950">Kalkulasi Berjalan (Draf Periode {{ $payroll['period_label'] }})</h4>
                    <p class="mt-0.5 text-amber-800">
                        Nilai potongan dan honor bersih di bawah dihitung otomatis secara real-time dari rekaman absensi harian dan penetapan sanksi aktif. Klik tombol <strong>Kunci & Tutup Periode</strong> di atas untuk menetapkan rekapitulasi resmi.
                    </p>
                </div>
            </div>
        @endif

        <!-- KPI Summary Metrics Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="bg-white p-3.5 rounded-2xl border border-gray-200 shadow-xs">
                <span class="text-[10px] text-gray-500 uppercase font-bold tracking-wider block">Total Personil</span>
                <div class="text-xl font-extrabold text-gray-900 mt-1">
                    {{ $totals['total_employees'] }} <span class="text-xs font-normal text-gray-500">PPNPN</span>
                </div>
            </div>

            <div class="bg-white p-3.5 rounded-2xl border border-gray-200 shadow-xs">
                <span class="text-[10px] text-gray-500 uppercase font-bold tracking-wider block">Total Gaji Pokok</span>
                <div class="text-base font-extrabold font-mono text-gray-900 mt-1">
                    Rp {{ number_format($totals['total_base_salary'], 0, ',', '.') }}
                </div>
            </div>

            <div class="bg-white p-3.5 rounded-2xl border border-amber-200/80 shadow-xs">
                <span class="text-[10px] text-amber-700 uppercase font-bold tracking-wider block">Akumulasi 1 (TL)</span>
                <div class="text-base font-extrabold font-mono text-amber-700 mt-1">
                    Rp {{ number_format($totals['total_tl_deduction'], 0, ',', '.') }}
                </div>
            </div>

            <div class="bg-white p-3.5 rounded-2xl border border-rose-200/80 shadow-xs">
                <span class="text-[10px] text-rose-700 uppercase font-bold tracking-wider block">Akumulasi 2 (SP)</span>
                <div class="text-base font-extrabold font-mono text-rose-700 mt-1">
                    Rp {{ number_format($totals['total_sp_deduction'], 0, ',', '.') }}
                </div>
            </div>

            <div class="bg-white p-3.5 rounded-2xl border border-rose-200/80 shadow-xs">
                <span class="text-[10px] text-rose-800 uppercase font-bold tracking-wider block">Total Potongan</span>
                <div class="text-base font-extrabold font-mono text-rose-800 mt-1">
                    Rp {{ number_format($totals['total_deductions_amount'], 0, ',', '.') }}
                </div>
            </div>

            <div class="bg-emerald-50/60 p-3.5 rounded-2xl border border-emerald-200 shadow-xs">
                <span class="text-[10px] text-emerald-800 uppercase font-bold tracking-wider block">Honor Bersih</span>
                <div class="text-base font-extrabold font-mono text-emerald-700 mt-1">
                    Rp {{ number_format($totals['total_net_salary'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        <!-- Main Table Container -->
        <div class="bg-white border border-gray-200 rounded-2xl shadow-xs overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0058A8] flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                            Rincian Rekapitulasi Per Pegawai ({{ count($items) }} PPNPN)
                        </h3>
                        <p class="text-[11px] text-gray-500">Pemisahan Tegas Akumulasi 1 (Lampiran IV) vs Akumulasi 2 (Lampiran III SPK)</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a 
                        href="{{ route('admin.payroll.export-excel', ['year' => $selectedYear, 'month' => $selectedMonth]) }}" 
                        class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold rounded-xl transition"
                    >
                        Unduh Excel (.csv)
                    </a>
                    <a 
                        href="{{ route('admin.payroll.export-pdf', ['year' => $selectedYear, 'month' => $selectedMonth]) }}" 
                        target="_blank"
                        class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-[#0058A8] border border-blue-200 text-xs font-bold rounded-xl transition"
                    >
                        Pratinjau Cetak PDF
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-[11px] text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-[10px] font-bold text-gray-700 uppercase border-b-2 border-gray-200 whitespace-nowrap">
                            <th class="py-2.5 px-3 text-center whitespace-nowrap">No</th>
                            <th class="py-2.5 px-3 min-w-[220px] whitespace-nowrap">Pegawai</th>
                            <th class="py-2.5 px-3 text-right min-w-[110px] whitespace-nowrap">Gaji Pokok</th>
                            <th class="py-2.5 px-2 text-center text-emerald-700 whitespace-nowrap" title="Hadir Tepat Waktu">Hadir</th>
                            <th class="py-2.5 px-2 text-center text-gray-600 whitespace-nowrap" title="Toleransi Lunas">Tol.</th>
                            <th class="py-2.5 px-2 text-center whitespace-nowrap" title="TL 1">TL1</th>
                            <th class="py-2.5 px-2 text-center whitespace-nowrap" title="TL 2">TL2</th>
                            <th class="py-2.5 px-2 text-center whitespace-nowrap" title="TL 3">TL3</th>
                            <th class="py-2.5 px-2 text-center whitespace-nowrap" title="Cuti / Izin">Cuti</th>
                            <th class="py-2.5 px-2 text-center whitespace-nowrap" title="Alpa">Alpa</th>
                            <th class="py-2.5 px-3 text-right bg-blue-50/70 text-blue-900 font-bold border-x border-blue-100 min-w-[140px] whitespace-nowrap">Pot.&nbsp;TL&nbsp;(Akum&nbsp;1)</th>
                            <th class="py-2.5 px-3 text-center bg-amber-50/70 text-amber-900 font-bold min-w-[150px] whitespace-nowrap">Sanksi&nbsp;SP&nbsp;(Akum&nbsp;2)</th>
                            <th class="py-2.5 px-3 text-right bg-amber-50/70 text-amber-900 font-bold border-r border-amber-100 min-w-[105px] whitespace-nowrap">Pot.&nbsp;SP</th>
                            <th class="py-2.5 px-3 text-right text-rose-800 font-bold min-w-[115px] whitespace-nowrap">Total Potongan</th>
                            <th class="py-2.5 px-3 text-right text-emerald-800 font-bold min-w-[120px] whitespace-nowrap">Honor Bersih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($items as $idx => $row)
                            <tr class="hover:bg-blue-50/20 transition">
                                <td class="py-2.5 px-3 text-gray-400 font-mono text-center whitespace-nowrap">{{ $idx + 1 }}</td>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-gray-900 leading-tight">{{ $row['name'] }}</div>
                                    <div class="text-[10px] text-gray-500 font-mono flex items-center gap-1.5 mt-0.5 leading-tight">
                                        <span class="whitespace-nowrap">{{ $row['employee_code'] }}</span>
                                        <span class="text-gray-300 select-none">&bull;</span>
                                        <span class="text-[#0058A8] font-semibold truncate">{{ $row['position_name'] }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-medium text-gray-800 whitespace-nowrap">
                                    Rp {{ number_format($row['base_salary'], 0, ',', '.') }}
                                </td>
                                <td class="py-2 px-2 text-center font-mono font-bold text-emerald-700 whitespace-nowrap">{{ $row['on_time_count'] }}</td>
                                <td class="py-2 px-2 text-center font-mono text-gray-600 whitespace-nowrap">{{ $row['tolerance_compensated_count'] }}</td>
                                <td class="py-2 px-2 text-center font-mono whitespace-nowrap {{ $row['tl_1_count'] > 0 ? 'text-amber-700 font-bold' : 'text-gray-400' }}">{{ $row['tl_1_count'] }}</td>
                                <td class="py-2 px-2 text-center font-mono whitespace-nowrap {{ $row['tl_2_count'] > 0 ? 'text-rose-700 font-bold' : 'text-gray-400' }}">{{ $row['tl_2_count'] }}</td>
                                <td class="py-2 px-2 text-center font-mono whitespace-nowrap {{ $row['tl_3_count'] > 0 ? 'text-rose-900 font-bold' : 'text-gray-400' }}">{{ $row['tl_3_count'] }}</td>
                                <td class="py-2 px-2 text-center font-mono text-blue-700 font-medium whitespace-nowrap">{{ $row['leave_count'] }}</td>
                                <td class="py-2 px-2 text-center font-mono whitespace-nowrap {{ $row['alpha_count'] > 0 ? 'text-red-700 font-bold' : 'text-gray-400' }}">{{ $row['alpha_count'] }}</td>
                                
                                <!-- Akumulasi 1 (TL) -->
                                <td class="py-2.5 px-3 text-right font-mono bg-blue-50/20 border-x border-blue-100/60 whitespace-nowrap">
                                    <div class="{{ $row['accumulation_1_tl_amount'] > 0 ? 'text-amber-700 font-bold' : 'text-gray-400' }}">
                                        Rp {{ number_format($row['accumulation_1_tl_amount'], 0, ',', '.') }}
                                    </div>
                                    <div class="text-[9px] text-gray-500 font-mono">{{ number_format($row['accumulation_1_tl_percentage'], 1, ',', '.') }}%</div>
                                </td>

                                <!-- Akumulasi 2 (Sanksi SP) -->
                                <td class="py-2.5 px-3 text-center bg-amber-50/20 whitespace-nowrap">
                                    @if($row['accumulation_2_sanction_type'])
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-100 text-rose-800 border border-rose-200 whitespace-nowrap">
                                            {{ str_replace('_', ' ', $row['accumulation_2_sanction_type']) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-[10px]">-</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono bg-amber-50/20 border-r border-amber-100/60 whitespace-nowrap">
                                    <div class="{{ $row['accumulation_2_sanction_amount'] > 0 ? 'text-rose-700 font-bold' : 'text-gray-400' }}">
                                        Rp {{ number_format($row['accumulation_2_sanction_amount'], 0, ',', '.') }}
                                    </div>
                                    <div class="text-[9px] text-gray-500 font-mono">{{ number_format($row['accumulation_2_sanction_percentage'], 1, ',', '.') }}%</div>
                                </td>

                                <!-- Total & Net -->
                                <td class="py-2.5 px-3 text-right font-mono font-bold whitespace-nowrap {{ $row['total_deduction_amount'] > 0 ? 'text-rose-700' : 'text-gray-400' }}">
                                    Rp {{ number_format($row['total_deduction_amount'], 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-extrabold text-emerald-700 text-xs whitespace-nowrap">
                                    Rp {{ number_format($row['net_salary'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="py-8 text-center text-gray-400">
                                    Tidak ada pegawai aktif yang terdaftar dalam sistem.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 font-bold text-gray-900 border-t-2 border-gray-300 whitespace-nowrap">
                            <td colspan="2" class="py-3 px-3 uppercase tracking-wider text-[10px] text-gray-700">
                                Total Keseluruhan ({{ $totals['total_employees'] }} Personil)
                            </td>
                            <td class="py-3 px-3 text-right font-mono text-gray-900">
                                Rp {{ number_format($totals['total_base_salary'], 0, ',', '.') }}
                            </td>
                            <td colspan="7" class="py-3 px-2 text-center text-[10px] text-gray-500 font-normal">
                                &mdash; Akumulasi 1 (TL) & Akumulasi 2 (SP) Terpisah &mdash;
                            </td>
                            <td class="py-3 px-3 text-right font-mono text-amber-700 bg-blue-50/30 border-x border-blue-100/60">
                                Rp {{ number_format($totals['total_tl_deduction'], 0, ',', '.') }}
                            </td>
                            <td class="bg-amber-50/30"></td>
                            <td class="py-3 px-3 text-right font-mono text-rose-700 bg-amber-50/30 border-r border-amber-100/60">
                                Rp {{ number_format($totals['total_sp_deduction'], 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-extrabold text-rose-800">
                                Rp {{ number_format($totals['total_deductions_amount'], 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-extrabold text-emerald-700 text-xs">
                                Rp {{ number_format($totals['total_net_salary'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
