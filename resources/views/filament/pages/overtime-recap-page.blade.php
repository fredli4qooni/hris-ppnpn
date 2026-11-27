<x-filament-panels::page>
    @php
        $recap = $this->getRecapData();
        $daysInMonth = $recap['days_in_month'];
        $holidays = $recap['holidays'];
        $items = $recap['items'];
        $totals = $recap['totals'];
        $masterOrder = $recap['master_order'];
        $allEmployees = \App\Models\Employee::where('is_active', true)->orderBy('full_name')->get(['id', 'full_name', 'employee_code']);
    @endphp

    <div class="space-y-5" x-data="{ detailModal: null }">
        <!-- Filter Bar -->
        <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-xs">
            <form method="GET" action="{{ route('filament.admin.pages.overtime-recap-page') }}" class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Periode:</span>
                    <select name="month" onchange="this.form.submit()" class="rounded-xl border border-gray-300 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-800">
                        @foreach(\App\Models\MonthlyPeriodClosing::MONTH_NAMES as $num => $name)
                            <option value="{{ $num }}" {{ $selectedMonth === $num ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>

                    <select name="year" onchange="this.form.submit()" class="rounded-xl border border-gray-300 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-800">
                        @foreach([2025, 2026, 2027] as $y)
                            <option value="{{ $y }}" {{ $selectedYear === $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                        @endforeach
                    </select>

                    <select name="employee_id" onchange="this.form.submit()" class="rounded-xl border border-gray-300 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-800">
                        <option value="">Semua Pegawai</option>
                        @foreach($allEmployees as $empOpt)
                            <option value="{{ $empOpt->id }}" {{ $selectedEmployeeId === $empOpt->id ? 'selected' : '' }}>
                                {{ $empOpt->full_name }} ({{ $empOpt->employee_code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2 text-xs">
                    @if($masterOrder && $masterOrder->isIssued())
                        <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold">
                            SPKL Terbit: {{ $masterOrder->letter_number }}
                        </span>
                    @elseif($masterOrder)
                        <span class="px-3 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 font-bold">
                            Pengingat: {{ $masterOrder->letter_number }} ({{ $masterOrder->getStatusLabel() }})
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 font-bold">
                            Pengingat: SPKL {{ $recap['month_name'] }} {{ $selectedYear }} belum dibuat
                        </span>
                    @endif
                </div>
            </form>
        </div>

        {{-- Summary Cards (Waktu/Jam & Status Surat Saja) --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">Personil Lembur</span>
                <div class="text-xl font-extrabold text-gray-900 mt-1">{{ $totals['total_employees_with_overtime'] }} <span class="text-xs font-normal text-gray-500">PPNPN</span></div>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-blue-200 shadow-xs">
                <span class="text-[10px] font-bold text-[#0058A8] uppercase tracking-wider block">Memiliki Surat Tugas (SPTPL)</span>
                <div class="text-xl font-extrabold text-[#0058A8] mt-1">{{ $totals['total_employees_with_letter'] }} <span class="text-xs font-normal text-gray-500">PPNPN</span></div>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-emerald-200 shadow-xs">
                <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Jam Dengan Surat (Jenis A)</span>
                <div class="text-xl font-extrabold font-mono text-emerald-700 mt-1">{{ number_format($totals['total_hours_with_letter'], 2, ',', '.') }} <span class="text-xs font-normal">Jam</span></div>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-amber-200 shadow-xs">
                <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Jam Tanpa Surat (Jenis B)</span>
                <div class="text-xl font-extrabold font-mono text-amber-700 mt-1">{{ number_format($totals['total_hours_without_letter'], 2, ',', '.') }} <span class="text-xs font-normal">Jam</span></div>
            </div>
        </div>

        <!-- Legend -->
        <div class="flex flex-wrap items-center gap-4 px-1 text-[11px] text-gray-600">
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-100 border border-emerald-400"></span>Ada Surat (SPTPL)</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-100 border border-amber-400"></span>Tanpa Surat / Absen Pulang Lebih</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-rose-50 border border-rose-300"></span>Akhir Pekan / Hari Libur</span>
            <span class="text-gray-400 italic">Klik angka jam pada tanggal untuk melihat rincian surat, uraian &amp; absensi.</span>
        </div>

        <!-- Matrix 1..31 -->
        <div class="bg-white border border-gray-200 rounded-2xl shadow-xs overflow-x-auto">
            <table class="w-full text-[11px] text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-[10px] font-bold text-gray-700 uppercase whitespace-nowrap">
                        <th class="py-2.5 px-2.5 text-center sticky left-0 bg-gray-50 z-10">No</th>
                        <th class="py-2.5 px-3 min-w-[170px] sticky left-8 bg-gray-50 z-10 border-r border-gray-200">Nama Pegawai</th>
                        @for($d = 1; $d <= $daysInMonth; $d++)
                            @php $isHol = isset($holidays[$d]); @endphp
                            <th class="py-2 px-1.5 text-center min-w-[34px] {{ $isHol ? 'bg-rose-50 text-rose-700' : '' }}" title="{{ $holidays[$d] ?? 'Hari Kerja' }}">
                                {{ $d }}
                                @if($isHol)
                                    <span class="block text-[8px] font-extrabold text-rose-600">L</span>
                                @endif
                            </th>
                        @endfor
                        <th class="py-2.5 px-2 text-right bg-blue-50/70 text-blue-900">Hari Kerja</th>
                        <th class="py-2.5 px-2 text-right bg-rose-50/70 text-rose-900">Hari Libur</th>
                        <th class="py-2.5 px-2 text-right bg-emerald-50/70 text-emerald-900">Dengan Surat</th>
                        <th class="py-2.5 px-2 text-right bg-amber-50/70 text-amber-900">Tanpa Surat</th>
                        <th class="py-2.5 px-3">Status Surat Tugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php $rowNo = 1; @endphp
                    @forelse($items as $row)
                        <tr class="hover:bg-gray-50/70">
                            <td class="py-2 px-2.5 text-center text-gray-400 font-mono sticky left-0 bg-white">{{ $rowNo++ }}</td>
                            <td class="py-2 px-3 sticky left-8 bg-white border-r border-gray-100">
                                <div class="font-bold text-gray-900 leading-tight">{{ $row['name'] }}</div>
                                <div class="text-[9px] text-gray-500 font-mono">{{ $row['position_name'] }}</div>
                            </td>
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                @php
                                    $cell = $row['days'][$d];
                                    $isHol = isset($holidays[$d]);
                                    $cellBg = match($cell['status']) {
                                        'WITH_LETTER' => 'bg-emerald-100 text-emerald-900 font-bold hover:bg-emerald-200 cursor-pointer',
                                        'PARTIAL' => 'bg-blue-100 text-blue-900 font-bold hover:bg-blue-200 cursor-pointer',
                                        'WITHOUT_LETTER' => 'bg-amber-100 text-amber-900 font-bold hover:bg-amber-200 cursor-pointer',
                                        default => $isHol ? 'bg-rose-50/40 text-gray-300' : 'text-gray-300',
                                    };
                                @endphp
                                <td class="py-1.5 px-1 text-center font-mono border-l border-gray-50 {{ $cellBg }}">
                                    @if($cell['total_hours'] > 0)
                                        <button
                                            type="button"
                                            class="w-full h-full block cursor-pointer"
                                            @click="detailModal = {{ json_encode([
                                                'name' => $row['name'],
                                                'date' => sprintf('%02d %s %d', $d, $recap['month_name'], $selectedYear),
                                                'day_type' => $holidays[$d] ?? 'Hari Kerja Normal',
                                                'status' => $cell['status'],
                                                'hours_with_letter' => $cell['hours_with_letter'],
                                                'hours_without_letter' => $cell['hours_without_letter'],
                                                'letters' => $cell['letters'],
                                                'descriptions' => $cell['descriptions'],
                                                'check_in' => $cell['attendance_check_in'],
                                                'check_out' => $cell['attendance_check_out'],
                                            ]) }}"
                                        >
                                            {{ (float) $cell['total_hours'] }}
                                        </button>
                                    @else
                                        <span>-</span>
                                    @endif
                                </td>
                            @endfor
                            <td class="py-2 px-2 text-right font-mono font-semibold">{{ (float) $row['workday_hours'] }} j</td>
                            <td class="py-2 px-2 text-right font-mono font-semibold">{{ (float) $row['holiday_hours'] }} j</td>
                            <td class="py-2 px-2 text-right font-mono font-bold text-emerald-700">{{ (float) $row['hours_with_letter'] }} j</td>
                            <td class="py-2 px-2 text-right font-mono font-bold text-amber-700">{{ (float) $row['hours_without_letter'] }} j</td>
                            <td class="py-2 px-3 whitespace-nowrap">
                                @if($row['has_overtime_letter'])
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        Ada Surat ({{ implode(', ', $row['letter_numbers']) }})
                                    </span>
                                @elseif($row['hours_without_letter'] > 0)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        Tanpa Surat
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $daysInMonth + 7 }}" class="py-8 text-center text-gray-400">
                                Belum ada data pegawai aktif.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Cell Detail Modal -->
        <div
            x-show="detailModal !== null"
            x-cloak
            style="display: none;"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="detailModal = null"
        >
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-5 space-y-3 text-xs">
                <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                    <div>
                        <h4 class="font-bold text-gray-900 text-sm" x-text="detailModal?.name"></h4>
                        <span class="text-[11px] text-gray-500" x-text="detailModal?.date + ' (' + detailModal?.day_type + ')'"></span>
                    </div>
                    <button type="button" @click="detailModal = null" class="text-gray-400 hover:text-gray-700 font-bold">✕</button>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="bg-emerald-50 p-2.5 rounded-xl border border-emerald-100">
                        <span class="text-[10px] text-emerald-700 block">Jam Dengan Surat</span>
                        <span class="font-bold font-mono text-emerald-900" x-text="(detailModal?.hours_with_letter ?? 0) + ' Jam'"></span>
                    </div>
                    <div class="bg-amber-50 p-2.5 rounded-xl border border-amber-100">
                        <span class="text-[10px] text-amber-700 block">Jam Tanpa Surat</span>
                        <span class="font-bold font-mono text-amber-900" x-text="(detailModal?.hours_without_letter ?? 0) + ' Jam'"></span>
                    </div>
                </div>

                <div class="bg-gray-50 p-2.5 rounded-xl border border-gray-100 space-y-1">
                    <span class="text-[10px] font-bold text-gray-500 uppercase">Absensi Hari Tersebut</span>
                    <div class="font-mono text-gray-800">
                        Masuk: <strong x-text="detailModal?.check_in || '-'"></strong> &bull;
                        Pulang: <strong x-text="detailModal?.check_out || '-'"></strong>
                    </div>
                </div>

                <div class="space-y-1" x-show="detailModal?.letters?.length > 0">
                    <span class="text-[10px] font-bold text-gray-500 uppercase">Nomor SPTPL</span>
                    <div class="font-mono font-bold text-[#0058A8]" x-text="detailModal?.letters?.join(', ')"></div>
                </div>

                <div class="space-y-1" x-show="detailModal?.descriptions?.length > 0">
                    <span class="text-[10px] font-bold text-gray-500 uppercase">Uraian / Catatan</span>
                    <ul class="list-disc list-inside text-gray-700 space-y-0.5">
                        <template x-for="desc in (detailModal?.descriptions || [])" :key="desc">
                            <li x-text="desc"></li>
                        </template>
                    </ul>
                </div>

                <button type="button" @click="detailModal = null" class="w-full py-2 bg-gray-100 hover:bg-gray-200 rounded-xl font-bold text-gray-700">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
