<x-filament-panels::page>
    <style>
        .tac-container {
            font-family: inherit;
        }
        .tac-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
        }
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
        .tac-btn {
            background-color: #0058a8;
            color: #ffffff;
            padding: 7px 16px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }
        .tac-btn:hover {
            background-color: #004787;
        }
        .tac-table-wrapper {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }
        .tac-table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .tac-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 11px;
            text-align: left;
        }
        .tac-table th, .tac-table td {
            box-sizing: border-box;
        }
        /* Sticky Employee Column */
        .tac-col-employee-th {
            position: sticky;
            left: 0;
            z-index: 20;
            background-color: #f8fafc;
            min-width: 280px;
            width: 280px;
            padding: 10px 14px;
            border-right: 2px solid #e2e8f0;
            border-bottom: 2px solid #cbd5e1;
            font-weight: 700;
            color: #334155;
            box-shadow: 2px 0 4px -2px rgba(0,0,0,0.06);
        }
        .tac-col-employee-td {
            position: sticky;
            left: 0;
            z-index: 10;
            background-color: #ffffff;
            min-width: 280px;
            width: 280px;
            padding: 10px 14px;
            border-right: 2px solid #e2e8f0;
            border-bottom: 1px solid #f1f5f9;
            box-shadow: 2px 0 4px -2px rgba(0,0,0,0.06);
        }
        .tac-col-quota-th {
            min-width: 75px;
            width: 75px;
            text-align: center;
            padding: 8px 6px;
            background-color: #f8fafc;
            border-right: 1px solid #e2e8f0;
            border-bottom: 2px solid #cbd5e1;
            font-weight: 700;
            color: #334155;
        }
        .tac-col-quota-td {
            min-width: 75px;
            width: 75px;
            text-align: center;
            padding: 8px 6px;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 700;
            color: #8a6100;
            font-family: monospace;
            font-size: 12px;
        }
        /* Day Columns */
        .tac-col-day-th {
            min-width: 36px;
            width: 36px;
            max-width: 42px;
            text-align: center;
            padding: 6px 2px;
            border-right: 1px solid #f1f5f9;
            border-bottom: 2px solid #cbd5e1;
            background-color: #ffffff;
        }
        .tac-col-day-th.is-weekend {
            background-color: #f1f5f9;
            color: #94a3b8;
        }
        .tac-day-name {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            line-height: 1;
            margin-bottom: 3px;
        }
        .tac-day-num {
            font-size: 12px;
            font-weight: 800;
            font-family: monospace;
            line-height: 1;
        }
        .tac-col-day-td {
            min-width: 36px;
            width: 36px;
            max-width: 42px;
            height: 44px;
            text-align: center;
            vertical-align: middle;
            border-right: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
            padding: 4px 2px;
        }
        .tac-col-day-td.is-weekend {
            background-color: #f8fafc;
        }
        /* Status Badges */
        .tac-badge-leave {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 2px 4px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 700;
            background-color: #e0f2fe;
            color: #0058a8;
            border: 1px solid #bae6fd;
            white-space: nowrap;
        }
        .tac-badge-pending {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 2px 4px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 700;
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
            white-space: nowrap;
        }
        .tac-dot-present {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25);
        }
        .tac-dot-scheduled {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: #cbd5e1;
        }
        .tac-row:hover .tac-col-employee-td {
            background-color: #f8fafc;
        }
        .tac-row:hover .tac-col-day-td:not(.is-weekend) {
            background-color: #f0f7ff;
        }
    </style>

    <div class="tac-container space-y-5">

        <!-- Top Filter & Control Card -->
        <div class="tac-card flex flex-wrap items-center justify-between gap-4">
            <form method="GET" action="{{ route('filament.admin.pages.team-availability-calendar') }}" class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Periode:</span>
                    <!-- Select Bulan -->
                    <select name="month" id="month" onchange="this.form.submit()" class="tac-select">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $selectedMonth === $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>

                    <!-- Select Tahun -->
                    <select name="year" id="year" onchange="this.form.submit()" class="tac-select">
                        @for($y = 2024; $y <= 2027; $y++)
                            <option value="{{ $y }}" {{ $selectedYear === $y ? 'selected' : '' }}>
                                Tahun {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Posisi:</span>
                    <!-- Filter Posisi -->
                    <select name="position_id" id="position_id" onchange="this.form.submit()" class="tac-select">
                        <option value="">Semua Posisi Regu Kerja</option>
                        @foreach($positions as $pos)
                            <option value="{{ $pos->id }}" {{ $selectedPositionId === $pos->id ? 'selected' : '' }}>
                                {{ $pos->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <noscript>
                    <button type="submit" class="tac-btn">Terapkan</button>
                </noscript>
            </form>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-[#0058A8] border border-blue-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span>{{ $calendarData['total_employees'] }} Personil Terpantau</span>
                </span>
            </div>
        </div>

        <!-- Staffing Conflict Alert Banner (FR-503 Guard) -->
        @if(!empty($calendarData['conflicts']))
            <div class="bg-amber-50/90 border border-amber-300 rounded-2xl p-4 text-xs text-amber-900 space-y-2.5 shadow-xs">
                <div class="flex items-center gap-2 font-bold text-amber-900 text-sm">
                    <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span>Peringatan Potensi Kekosongan Pos Tugas (&ge; 50% Personil Absen / Cuti Bersamaan)</span>
                </div>
                <p class="text-amber-800 text-xs leading-relaxed">
                    Sistem mendeteksi jadwal kritis di mana minimal separuh personil pada unit posisi yang sama sedang cuti atau mengajukan izin pada hari kerja efektif berikut:
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 pt-1">
                    @foreach($calendarData['conflicts'] as $conflict)
                        <div class="bg-white p-2.5 rounded-xl border border-amber-200 flex items-center justify-between shadow-2xs">
                            <div>
                                <span class="font-bold text-gray-900 block text-xs">{{ $conflict['date_formatted'] }}</span>
                                <span class="text-gray-500 text-[10px] block">Posisi: <strong>{{ $conflict['position'] }}</strong></span>
                            </div>
                            <span class="px-2 py-1 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 font-extrabold text-[11px]">
                                {{ $conflict['absent_count'] }} / {{ $conflict['total_count'] }} ({{ $conflict['percentage'] }}%)
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Main Matrix Calendar Card -->
        <div class="tac-table-wrapper">
            <div class="p-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="font-bold text-sm text-gray-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#0058A8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span>Matriks Ketersediaan & Jadwal Regu &bull; {{ $calendarData['month_name'] }}</span>
                    </h3>
                    <p class="text-[11px] text-gray-500 mt-0.5">
                        Geser horizontal tabel di bawah untuk melihat seluruh tanggal kalender kerja bulan bersangkutan.
                    </p>
                </div>
            </div>

            <div class="tac-table-scroll">
                <table class="tac-table">
                    <thead>
                        <tr>
                            <th class="tac-col-employee-th">
                                Pegawai & Posisi
                            </th>
                            <th class="tac-col-quota-th">
                                Sisa Cuti
                            </th>
                            @foreach($calendarData['days'] as $day)
                                <th class="tac-col-day-th {{ $day['isWeekend'] ? 'is-weekend' : '' }}">
                                    <div class="tac-day-name {{ $day['isWeekend'] ? 'text-gray-400' : 'text-gray-600' }}">{{ $day['dayName'] }}</div>
                                    <div class="tac-day-num {{ $day['isWeekend'] ? 'text-rose-500' : 'text-gray-900' }}">{{ $day['day'] }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($calendarData['matrix'] as $row)
                            <tr class="tac-row">
                                <td class="tac-col-employee-td">
                                    <div class="font-bold text-gray-900 text-xs leading-tight truncate" title="{{ $row['name'] }}">{{ $row['name'] }}</div>
                                    <div class="text-[10px] text-gray-500 flex items-center gap-1.5 mt-1 leading-tight">
                                        <span class="font-mono text-gray-500 whitespace-nowrap shrink-0">{{ $row['code'] }}</span>
                                        <span class="text-gray-300 shrink-0 select-none">&bull;</span>
                                        <span class="text-[#0058A8] font-semibold truncate" title="{{ $row['position'] }}">{{ $row['position'] }}</span>
                                    </div>
                                </td>
                                <td class="tac-col-quota-td">
                                    {{ $row['leave_quota'] }} <span class="text-[9px] font-normal text-gray-400">Hari</span>
                                </td>
                                @foreach($calendarData['days'] as $day)
                                    @php
                                        $dState = $row['days'][$day['date']];
                                    @endphp
                                    <td class="tac-col-day-td {{ $day['isWeekend'] ? 'is-weekend' : '' }}">
                                        @if($dState['status'] === 'WEEKEND')
                                            <span class="text-gray-300 text-xs font-bold">&ndash;</span>
                                        @elseif($dState['status'] === 'LEAVE_APPROVED')
                                            <span class="tac-badge-leave" title="Cuti/Izin Disetujui: {{ $dState['label'] }}">
                                                {{ $dState['label'] }}
                                            </span>
                                        @elseif($dState['status'] === 'LEAVE_PENDING')
                                            <span class="tac-badge-pending" title="Pengajuan Menunggu Verifikasi">
                                                ⏳ {{ $dState['label'] }}
                                            </span>
                                        @elseif($dState['status'] === 'PRESENT')
                                            <span class="tac-dot-present" title="Hadir Bertugas"></span>
                                        @else
                                            <span class="tac-dot-scheduled" title="Jadwal Reguler"></span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($calendarData['days']) + 2 }}" class="p-8 text-center text-gray-400 text-xs">
                                    Tidak ada data pegawai subordinat yang ditemukan pada filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Legend Card -->
        <div class="tac-card flex flex-wrap items-center gap-x-6 gap-y-2.5 text-xs text-gray-700">
            <span class="font-bold text-gray-900">Keterangan Status:</span>
            <div class="flex items-center gap-1.5">
                <span class="tac-dot-present"></span>
                <span class="font-medium">Hadir Bertugas</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="tac-badge-leave">Cuti / Izin</span>
                <span>Disetujui Sah</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="tac-badge-pending">⏳ Menunggu</span>
                <span>Dalam Proses Verifikasi</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="tac-dot-scheduled"></span>
                <span>Jadwal Dinas Terjadwal</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-gray-400 font-bold">&ndash;</span>
                <span>Hari Libur Akhir Pekan</span>
            </div>
        </div>

    </div>
</x-filament-panels::page>
