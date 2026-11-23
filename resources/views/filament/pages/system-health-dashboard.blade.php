<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Top Hero Status Banner -->
        <div class="rounded-2xl p-5 border shadow-sm transition {{ $metrics['overall_status'] === 'HEALTHY' ? 'bg-gradient-to-r from-emerald-50 via-teal-50 to-white border-emerald-200 text-emerald-950' : ($metrics['overall_status'] === 'WARNING' ? 'bg-gradient-to-r from-amber-50 via-orange-50 to-white border-amber-200 text-amber-950' : 'bg-gradient-to-r from-rose-50 via-red-50 to-white border-rose-200 text-rose-950') }}">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center space-x-3.5">
                    <span class="flex h-4 w-4 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $metrics['overall_status'] === 'HEALTHY' ? 'bg-emerald-400 opacity-75' : ($metrics['overall_status'] === 'WARNING' ? 'bg-amber-400 opacity-75' : 'bg-rose-400 opacity-75') }}"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 {{ $metrics['overall_status'] === 'HEALTHY' ? 'bg-emerald-600' : ($metrics['overall_status'] === 'WARNING' ? 'bg-amber-600' : 'bg-rose-600') }}"></span>
                    </span>
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider block {{ $metrics['overall_status'] === 'HEALTHY' ? 'text-emerald-800' : ($metrics['overall_status'] === 'WARNING' ? 'text-amber-800' : 'text-rose-800') }}">
                            Status Operasional Utama Sistem
                        </span>
                        <h2 class="text-base font-extrabold tracking-tight">
                            {{ $metrics['overall_label'] }}
                        </h2>
                    </div>
                </div>

                <div class="text-right text-xs">
                    <span class="text-gray-500 block text-[10.5px]">Pemeriksaan Terakhir:</span>
                    <span class="font-mono font-bold text-gray-800">{{ $metrics['checked_at'] }}</span>
                </div>
            </div>
        </div>

        <!-- 4 Grid Telemetry Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Card 1: Database Connectivity -->
            <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Koneksi Basis Data</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono {{ $metrics['database']['status'] === 'ONLINE' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                        {{ $metrics['database']['status'] }}
                    </span>
                </div>
                <div>
                    <span class="text-2xl font-black font-mono text-gray-900">{{ $metrics['database']['latency_ms'] }}</span>
                    <span class="text-xs text-gray-500 font-bold"> ms</span>
                    <p class="text-[11px] text-gray-500 mt-0.5">Latensi respons kueri ke server DB</p>
                </div>
                <div class="pt-2 border-t border-gray-100 text-[10.5px] text-gray-600 flex justify-between">
                    <span>Driver: <strong class="font-mono">{{ $metrics['database']['driver'] }}</strong></span>
                    <span class="text-emerald-600 font-semibold">● Responsif</span>
                </div>
            </div>

            <!-- Card 2: Disk & Storage Space -->
            <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Kapasitas Storage</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono {{ $metrics['storage']['status'] === 'HEALTHY' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $metrics['storage']['used_percentage'] }}% Terpakai
                    </span>
                </div>
                <div>
                    <span class="text-2xl font-black font-mono text-gray-900">{{ $metrics['storage']['free_space_gb'] }}</span>
                    <span class="text-xs text-gray-500 font-bold"> GB Bebas</span>
                    <p class="text-[11px] text-gray-500 mt-0.5">Total Kapasitas: {{ $metrics['storage']['total_space_gb'] }} GB</p>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                    <div class="h-1.5 rounded-full {{ $metrics['storage']['used_percentage'] >= 80 ? 'bg-amber-500' : 'bg-[#0058A8]' }}" style="width: {{ min(100, $metrics['storage']['used_percentage']) }}%"></div>
                </div>
                <div class="pt-1 border-t border-gray-100 text-[10.5px] text-gray-600 flex justify-between">
                    <span>Lampiran: <strong class="font-mono">{{ $metrics['storage']['public_files_count'] }} Berkas</strong></span>
                    <span class="font-mono">({{ $metrics['storage']['public_size_mb'] }} MB)</span>
                </div>
            </div>

            <!-- Card 3: PHP Memory & Runtime -->
            <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Memori PHP Runtime</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-blue-50 text-[#0058A8]">
                        PHP {{ $metrics['runtime']['php_version'] }}
                    </span>
                </div>
                <div>
                    <span class="text-2xl font-black font-mono text-gray-900">{{ $metrics['runtime']['memory_usage_mb'] }}</span>
                    <span class="text-xs text-gray-500 font-bold"> MB</span>
                    <p class="text-[11px] text-gray-500 mt-0.5">Puncak (Peak): {{ $metrics['runtime']['memory_peak_mb'] }} MB (Batas: {{ $metrics['runtime']['memory_limit'] }})</p>
                </div>
                <div class="pt-2 border-t border-gray-100 text-[10.5px] text-gray-600 flex justify-between">
                    <span>Framework: <strong class="font-mono">Laravel {{ $metrics['runtime']['laravel_version'] }}</strong></span>
                    <span class="font-mono uppercase">{{ $metrics['runtime']['environment'] }}</span>
                </div>
            </div>

            <!-- Card 4: Daily Operational Throughput -->
            <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Aktivitas Hari Ini</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-purple-50 text-purple-700">
                        {{ $metrics['operational']['total_backups'] }} Cadangan
                    </span>
                </div>
                <div>
                    <span class="text-2xl font-black font-mono text-gray-900">{{ $metrics['operational']['attendances_today'] }}</span>
                    <span class="text-xs text-gray-500 font-bold"> Presensi</span>
                    <p class="text-[11px] text-gray-500 mt-0.5">Dari {{ $metrics['operational']['active_employees'] }} Pegawai PPNPN Aktif</p>
                </div>
                <div class="pt-2 border-t border-gray-100 text-[10.5px] text-gray-600 flex justify-between">
                    <span>Audit Trail 24 Jam: <strong class="font-mono">{{ $metrics['operational']['audit_logs_24h'] }} Log</strong></span>
                </div>
            </div>

        </div>

        <!-- Scheduled Jobs Daemon Table & Incident Search Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left: Scheduled Background Tasks (2 Cols) -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 p-5 shadow-xs space-y-3.5">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0058A8] flex items-center justify-center font-bold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-gray-900">Jadwal Tugas Otomatis (Scheduler Daemon)</h3>
                            <p class="text-[11px] text-gray-500">Pekerjaan cron latar belakang yang terdaftar aktif di aplikasi</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                        {{ $metrics['scheduler']['total_jobs'] }} Tugas Aktif
                    </span>
                </div>

                <div class="border border-gray-200 rounded-xl overflow-hidden text-xs">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-700 font-bold border-b border-gray-200">
                            <tr>
                                <th class="py-2.5 px-3">Perintah Artisan (Command)</th>
                                <th class="py-2.5 px-3">Jadwal Cron</th>
                                <th class="py-2.5 px-3">Keterangan Tugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-[11px]">
                            @forelse($metrics['scheduler']['jobs'] as $job)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="py-2 px-3 font-mono font-bold text-[#0058A8]">{{ $job['command'] }}</td>
                                    <td class="py-2 px-3 font-mono whitespace-nowrap bg-gray-50/40 text-gray-600">{{ $job['expression'] }}</td>
                                    <td class="py-2 px-3 text-gray-700">{{ $job['description'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-4 text-center text-gray-500">Belum ada tugas scheduler terdaftar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right: Incident Reference Ticket Lookup (1 Col) -->
            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs space-y-4">
                <div class="border-b border-gray-100 pb-3">
                    <h3 class="font-bold text-sm text-gray-900">Penelusuran Tiket Insiden</h3>
                    <p class="text-[11px] text-gray-500">Lacak riwayat galat berdasarkan kode tiket pengguna (ERR-DJPB-XXXXXX)</p>
                </div>

                <form wire:submit.prevent="searchIncident" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Kode Tiket Insiden:</label>
                        <input 
                            type="text" 
                            wire:model.defer="searchTicketCode" 
                            placeholder="Contoh: ERR-DJPB-9A8B7C"
                            class="w-full text-xs font-mono px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#0058A8] focus:border-transparent uppercase"
                        />
                    </div>
                    <button 
                        type="submit" 
                        class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-3 bg-[#0058A8] hover:bg-[#0B2A4A] text-white text-xs font-bold rounded-xl transition shadow-xs"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <span>Cari Konteks Insiden</span>
                    </button>
                </form>

                @if($foundIncident)
                    <div class="p-3.5 bg-rose-50/60 border border-rose-200 rounded-xl space-y-2 text-xs">
                        <div class="flex items-center justify-between border-b border-rose-200/60 pb-1.5">
                            <span class="font-mono font-extrabold text-rose-800">{{ $foundIncident['ticket_code'] }}</span>
                            <span class="text-[10px] text-gray-500">{{ $foundIncident['recorded_at'] }}</span>
                        </div>
                        <div class="space-y-1 text-[11px] text-gray-700">
                            <p><strong>Pengecualian:</strong> <span class="font-mono text-[10px] text-rose-900">{{ $foundIncident['exception'] }}</span></p>
                            <p><strong>Pesan:</strong> {{ $foundIncident['message'] }}</p>
                            <p><strong>Lokasi:</strong> <span class="font-mono text-[10px]">{{ $foundIncident['file'] }}</span></p>
                            <p><strong>IP Klien:</strong> <span class="font-mono">{{ $foundIncident['ip_address'] }}</span></p>
                        </div>
                    </div>
                @endif
            </div>

        </div>

    </div>
</x-filament-panels::page>
