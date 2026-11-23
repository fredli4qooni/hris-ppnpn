<x-layouts.mobile title="Detail Koreksi Lupa Absen">
    <div class="space-y-4">
        <!-- Top Title Bar -->
        <div class="flex items-center space-x-3">
            <a href="{{ route('mobile.corrections.index') }}" class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600 shadow-2xs hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <div>
                <h1 class="text-sm font-bold text-gray-900">Detail Koreksi Kehadiran</h1>
                <p class="text-[11px] text-gray-500">ID Pengajuan #{{ $correction->id }}</p>
            </div>
        </div>

        @php
            $badgeClass = match($correction->status) {
                \App\Models\AttendanceCorrection::STATUS_APPROVED => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                \App\Models\AttendanceCorrection::STATUS_APPROVED_SUPERVISOR => 'bg-blue-50 text-blue-800 border-blue-200',
                \App\Models\AttendanceCorrection::STATUS_REJECTED => 'bg-rose-50 text-rose-800 border-rose-200',
                default => 'bg-amber-50 text-amber-800 border-amber-200',
            };

            $statusLabel = match($correction->status) {
                \App\Models\AttendanceCorrection::STATUS_APPROVED => 'Disetujui Sah',
                \App\Models\AttendanceCorrection::STATUS_APPROVED_SUPERVISOR => 'Telaah Supervisor (Menunggu Atasan)',
                \App\Models\AttendanceCorrection::STATUS_REJECTED => 'Ditolak',
                default => 'Menunggu Telaah Supervisor',
            };

            $typeLabel = match($correction->correction_type) {
                \App\Models\AttendanceCorrection::TYPE_CHECK_IN => 'Lupa Absen Masuk',
                \App\Models\AttendanceCorrection::TYPE_CHECK_OUT => 'Lupa Absen Pulang',
                default => 'Lupa Masuk & Pulang',
            };
        @endphp

        <!-- Status Card -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider block">Status Pengajuan</span>
                    <span class="inline-block mt-1 px-3 py-1 rounded-full text-xs font-bold border {{ $badgeClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] text-gray-400 block">Tanggal Diajukan</span>
                    <span class="text-xs font-bold text-gray-800 font-mono">
                        {{ $correction->created_at->translatedFormat('d M Y, H:i') }} WIB
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2.5 pt-2 border-t border-gray-50 text-xs">
                <div>
                    <span class="text-gray-400 block text-[10px]">Tanggal Kejadian:</span>
                    <span class="font-bold text-gray-900">{{ $correction->attendance_date->translatedFormat('d F Y') }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block text-[10px]">Jenis Kehadiran:</span>
                    <span class="font-bold text-[#0058A8]">{{ $typeLabel }}</span>
                </div>
            </div>
        </div>

        <!-- Details Card -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm space-y-3 text-xs">
            <h3 class="font-bold text-gray-900 uppercase tracking-wider text-[11px] pb-1 border-b border-gray-50">
                Rincian Aktivitas Tugas Dinas
            </h3>

            <div>
                <span class="text-[10px] text-gray-400 block">Uraian Pekerjaan / Alasan:</span>
                <p class="text-gray-800 leading-relaxed mt-1 p-3 bg-gray-50 rounded-xl border border-gray-100">
                    {{ $correction->work_summary }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div class="p-2.5 bg-blue-50/40 rounded-xl border border-blue-100">
                    <span class="text-[10px] text-gray-500 block">Estimasi Jam Masuk:</span>
                    <span class="font-bold text-gray-900 font-mono">
                        {{ $correction->suggested_check_in_at ? $correction->suggested_check_in_at->format('H:i') . ' WIB' : '-' }}
                    </span>
                </div>
                <div class="p-2.5 bg-blue-50/40 rounded-xl border border-blue-100">
                    <span class="text-[10px] text-gray-500 block">Estimasi Jam Pulang:</span>
                    <span class="font-bold text-gray-900 font-mono">
                        {{ $correction->suggested_check_out_at ? $correction->suggested_check_out_at->format('H:i') . ' WIB' : '-' }}
                    </span>
                </div>
            </div>

            @if($correction->proof_document_path)
                <div class="pt-1">
                    <span class="text-[10px] text-gray-400 block mb-1">Dokumen / Bukti Pendukung:</span>
                    <a 
                        href="{{ asset('storage/' . $correction->proof_document_path) }}" 
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-50 text-[#0058A8] rounded-xl text-xs font-bold hover:bg-blue-100 transition border border-blue-200"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        <span>Lihat Berkas Lampiran</span>
                    </a>
                </div>
            @endif
        </div>

        <!-- Approval History Card -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm space-y-3 text-xs">
            <h3 class="font-bold text-gray-900 uppercase tracking-wider text-[11px] pb-1 border-b border-gray-50">
                Riwayat Persetujuan Berjenjang (Two-Tier)
            </h3>

            <div class="space-y-3">
                <!-- Stage 1: Supervisor -->
                <div class="flex items-start space-x-3">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs shrink-0 mt-0.5 {{ $correction->supervisor_reviewed_at ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500' }}">
                        1
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-gray-900">Telaah Supervisor Lapangan</span>
                            <span class="text-[10px] text-gray-400">
                                {{ $correction->supervisor_reviewed_at ? $correction->supervisor_reviewed_at->translatedFormat('d M, H:i') : 'Menunggu' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-0.5">
                            Oleh: {{ $correction->supervisor?->name ?? ($employee->supervisor?->name ?? 'Supervisor') }}
                        </p>
                        @if($correction->supervisor_notes)
                            <p class="text-[11px] text-gray-700 italic mt-1 bg-gray-50 p-2 rounded-lg border border-gray-100">
                                Catatan: "{{ $correction->supervisor_notes }}"
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Stage 2: Atasan -->
                <div class="flex items-start space-x-3 pt-2 border-t border-gray-50">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs shrink-0 mt-0.5 {{ $correction->atasan_decided_at ? ($correction->isApproved() ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800') : 'bg-gray-100 text-gray-500' }}">
                        2
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-gray-900">Putusan Final Atasan (Kasubbag / PPK)</span>
                            <span class="text-[10px] text-gray-400">
                                {{ $correction->atasan_decided_at ? $correction->atasan_decided_at->translatedFormat('d M, H:i') : 'Menunggu' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-0.5">
                            Oleh: {{ $correction->atasan?->name ?? ($employee->atasan?->name ?? 'Kasubbag Umum / PPK') }}
                        </p>
                        @if($correction->atasan_notes)
                            <p class="text-[11px] text-gray-700 italic mt-1 bg-gray-50 p-2 rounded-lg border border-gray-100">
                                Catatan: "{{ $correction->atasan_notes }}"
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.mobile>
