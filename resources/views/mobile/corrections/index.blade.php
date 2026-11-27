<x-layouts.mobile title="Koreksi Lupa Absen">
    <div class="space-y-4">
        <!-- Header & Policy Card -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#0058A8] flex items-center justify-center font-bold shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Koreksi Lupa Absen</h2>
                        <p class="text-[11px] text-gray-500">Pengajuan Koreksi Kehadiran</p>
                    </div>
                </div>
                <a 
                    href="{{ route('mobile.corrections.create') }}" 
                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-[#0058A8] text-white rounded-xl text-xs font-bold hover:bg-[#004787] transition shadow-xs"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Ajukan</span>
                </a>
            </div>

            <!-- Policy Warning -->
            <div class="bg-amber-50/70 p-3 rounded-xl border border-amber-200/80 text-xs text-amber-900 space-y-1">
                <div class="font-bold flex items-center gap-1.5 text-amber-950">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Ketentuan Batas Waktu H+2 Kerja:</span>
                </div>
                <p class="text-[11px] text-amber-800 leading-relaxed">
                    Koreksi diajukan maksimal <strong>2 hari kerja (H+2)</strong> setelah kejadian beserta ringkasan aktivitas & bukti.
                </p>
            </div>
        </div>

        <!-- Correction Requests List -->
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                    Daftar Pengajuan Koreksi ({{ $corrections->total() }})
                </h3>
            </div>

            @if($corrections->isEmpty())
                <div class="bg-white rounded-2xl border border-gray-100 p-8 text-center space-y-3 shadow-sm">
                    <div class="w-12 h-12 mx-auto rounded-full bg-blue-50 text-[#0058A8] flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900">Belum Ada Pengajuan</h4>
                        <p class="text-xs text-gray-500 mt-1 max-w-xs mx-auto">
                            Anda belum pernah mengajukan koreksi lupa absen. Tekan tombol <strong>Ajukan</strong> di atas jika Anda lupa absen karena penugasan dinas.
                        </p>
                    </div>
                    <div class="pt-2">
                        <a 
                            href="{{ route('mobile.corrections.create') }}" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-50 text-[#0058A8] rounded-xl text-xs font-bold hover:bg-blue-100 transition"
                        >
                            <span>Buat Pengajuan Baru</span>
                        </a>
                    </div>
                </div>
            @else
                <div class="space-y-2.5">
                    @foreach($corrections as $item)
                        @php
                            $badgeClass = match($item->status) {
                                \App\Models\AttendanceCorrection::STATUS_APPROVED => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                \App\Models\AttendanceCorrection::STATUS_APPROVED_SUPERVISOR => 'bg-blue-50 text-blue-800 border-blue-200',
                                \App\Models\AttendanceCorrection::STATUS_REJECTED => 'bg-rose-50 text-rose-800 border-rose-200',
                                default => 'bg-amber-50 text-amber-800 border-amber-200',
                            };

                            $statusLabel = match($item->status) {
                                \App\Models\AttendanceCorrection::STATUS_APPROVED => 'Disetujui Sah',
                                \App\Models\AttendanceCorrection::STATUS_APPROVED_SUPERVISOR => 'Telaah Supervisor (Menunggu Atasan)',
                                \App\Models\AttendanceCorrection::STATUS_REJECTED => 'Ditolak',
                                default => 'Menunggu Telaah Supervisor',
                            };

                            $typeLabel = match($item->correction_type) {
                                \App\Models\AttendanceCorrection::TYPE_CHECK_IN => 'Lupa Absen Masuk',
                                \App\Models\AttendanceCorrection::TYPE_CHECK_OUT => 'Lupa Absen Pulang',
                                default => 'Lupa Masuk & Pulang',
                            };
                        @endphp
                        <a 
                            href="{{ route('mobile.corrections.show', $item) }}" 
                            class="block bg-white p-3.5 rounded-2xl border border-gray-100 shadow-2xs hover:border-blue-200 transition space-y-2.5"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-extrabold text-gray-900">
                                        {{ $item->attendance_date->translatedFormat('d F Y') }}
                                    </span>
                                    <span class="text-[10px] text-gray-500 font-mono bg-gray-100 px-1.5 py-0.5 rounded">
                                        {{ $typeLabel }}
                                    </span>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <p class="text-xs text-gray-600 line-clamp-2 leading-relaxed">
                                {{ $item->work_summary }}
                            </p>

                            <div class="flex items-center justify-between text-[11px] text-gray-400 pt-1 border-t border-gray-50">
                                <span>Diajukan: {{ $item->created_at->translatedFormat('d M Y, H:i') }} WIB</span>
                                <span class="text-[#0058A8] font-bold flex items-center gap-0.5">
                                    Lihat Detail &rarr;
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="pt-2">
                    {{ $corrections->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.mobile>
