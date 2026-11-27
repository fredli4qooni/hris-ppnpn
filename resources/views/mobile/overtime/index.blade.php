<x-layouts.mobile title="Pengajuan Lembur & SPTPL">
    <div class="space-y-4">
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-3.5 py-2.5 rounded-xl text-xs font-semibold">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-3.5 py-2.5 rounded-xl text-xs font-semibold">
                {{ session('error') }}
            </div>
        @endif

        <!-- Header & Quick Info Card -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm space-y-3">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#0058A8] flex items-center justify-center font-bold shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Lembur &amp; SPTPL</h2>
                        <p class="text-[11px] text-gray-500">Daftar SPTPL &amp; Pengajuan Tanpa Surat</p>
                    </div>
                </div>
                <a
                    href="{{ route('mobile.overtime.create') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-[#0058A8] text-white rounded-xl text-xs font-bold hover:bg-blue-800 transition shadow-xs shrink-0"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Ajukan</span>
                </a>
            </div>

            <!-- Monthly Hours Recap (Hours Only) -->
            <div class="grid grid-cols-3 gap-2 pt-1">
                <div class="bg-blue-50/70 border border-blue-100 rounded-xl p-2.5 text-center">
                    <p class="text-[10px] font-semibold text-blue-700 uppercase">Dengan SPTPL</p>
                    <p class="text-sm font-extrabold text-[#0058A8] mt-0.5">
                        {{ number_format((float) ($monthlyStats['hours_with_letter'] ?? 0), 2, ',', '.') }} Jam
                    </p>
                </div>
                <div class="bg-amber-50/70 border border-amber-100 rounded-xl p-2.5 text-center">
                    <p class="text-[10px] font-semibold text-amber-700 uppercase">Tanpa Surat</p>
                    <p class="text-sm font-extrabold text-amber-800 mt-0.5">
                        {{ number_format((float) ($monthlyStats['hours_without_letter'] ?? 0), 2, ',', '.') }} Jam
                    </p>
                </div>
                <div class="bg-emerald-50/70 border border-emerald-100 rounded-xl p-2.5 text-center">
                    <p class="text-[10px] font-semibold text-emerald-700 uppercase">Total Bulan Ini</p>
                    <p class="text-sm font-extrabold text-emerald-800 mt-0.5">
                        {{ number_format((float) ($monthlyStats['total_hours'] ?? 0), 2, ',', '.') }} Jam
                    </p>
                </div>
            </div>

            <!-- Policy Information -->
            <div class="bg-blue-50/60 p-3 rounded-xl border border-blue-200/70 text-xs text-blue-900 space-y-1">
                <div class="font-bold flex items-center gap-1.5 text-blue-950">
                    <svg class="w-4 h-4 text-[#0058A8] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Ketentuan Pengajuan Lembur:</span>
                </div>
                <p class="text-[11px] text-blue-800 leading-relaxed">
                    Pengajuan lembur <strong>Tanpa Surat</strong> hanya dapat diajukan paling lambat pada <strong>Hari-H sebelum jam mulai lembur</strong>. Surat Perintah Tugas Pelaksanaan Lembur (<strong>SPTPL</strong>) diusulkan oleh Pengelola/Admin dan diterbitkan setelah disetujui Atasan.
                </p>
            </div>
        </div>

        <!-- Overtime Requests List -->
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                    Riwayat Lembur ({{ method_exists($requests, 'total') ? $requests->total() : $requests->count() }})
                </h3>
            </div>

            @if($requests->isEmpty())
                <div class="bg-white rounded-2xl border border-gray-100 p-8 text-center space-y-3 shadow-sm">
                    <div class="w-12 h-12 mx-auto rounded-full bg-blue-50 text-[#0058A8] flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900">Belum Ada Riwayat Lembur</h4>
                        <p class="text-xs text-gray-500 mt-1 max-w-xs mx-auto">
                            Daftar SPTPL resmi maupun pengajuan lembur tanpa surat Anda akan tampil di sini.
                        </p>
                    </div>
                    <div class="pt-2">
                        <a
                            href="{{ route('mobile.overtime.create') }}"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-50 text-[#0058A8] rounded-xl text-xs font-bold hover:bg-blue-100 transition border border-blue-200"
                        >
                            <span>Ajukan Lembur Sekarang</span>
                        </a>
                    </div>
                </div>
            @else
                <div class="space-y-2.5">
                    @foreach($requests as $item)
                        @php
                            $badgeClass = match($item->status) {
                                \App\Models\OvertimeRequest::STATUS_APPROVED => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                \App\Models\OvertimeRequest::STATUS_SUBMITTED => 'bg-blue-50 text-blue-800 border-blue-200',
                                \App\Models\OvertimeRequest::STATUS_RETURNED => 'bg-amber-50 text-amber-800 border-amber-200',
                                \App\Models\OvertimeRequest::STATUS_REJECTED, \App\Models\OvertimeRequest::STATUS_CANCELLED => 'bg-rose-50 text-rose-800 border-rose-200',
                                default => 'bg-gray-50 text-gray-700 border-gray-200',
                            };
                            $myLine = $item->items->first();
                        @endphp
                        <a
                            href="{{ route('mobile.overtime.show', $item) }}"
                            class="block bg-white p-3.5 rounded-2xl border border-gray-100 shadow-2xs hover:border-blue-300 transition space-y-2"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="text-xs font-extrabold text-gray-900">
                                            {{ $item->getDateRangeLabel() }}
                                        </span>
                                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $item->request_type === \App\Models\OvertimeRequest::TYPE_WITH_LETTER ? 'bg-blue-50 text-[#0058A8] border border-blue-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                                            {{ $item->getTypeLabel() }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] font-semibold text-gray-500 mt-0.5">
                                        {{ $item->getDisplayCode() }}
                                    </p>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border shrink-0 {{ $badgeClass }}">
                                    {{ $item->getStatusLabel() }}
                                </span>
                            </div>

                            @if($myLine)
                                <p class="text-xs text-gray-700 line-clamp-2 leading-relaxed">
                                    {{ $myLine->description }}
                                </p>
                                <div class="pt-1 border-t border-gray-50 flex items-center justify-between text-[11px] text-gray-500">
                                    <span>
                                        Pukul {{ substr((string) $myLine->start_time, 0, 5) }} - {{ substr((string) $myLine->end_time, 0, 5) }} WIB
                                    </span>
                                    <span class="font-bold text-[#0058A8]">
                                        {{ $myLine->getFormattedDuration() }}
                                        @if($item->getDayCount() > 1)
                                            ({{ $item->getDayCount() }} Hari)
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>

                @if(method_exists($requests, 'links'))
                    <div class="pt-2">
                        {{ $requests->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-layouts.mobile>
