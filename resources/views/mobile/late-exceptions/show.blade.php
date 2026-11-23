<x-layouts.mobile title="Rincian Dispensasi Kahar">
    <div class="space-y-4">
        <!-- Header Card -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
            <div class="flex items-center space-x-2.5">
                <a 
                    href="{{ route('mobile.late-exceptions.index') }}" 
                    class="w-8 h-8 rounded-xl bg-gray-100 text-gray-600 flex items-center justify-center hover:bg-gray-200 transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </a>
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Rincian Dispensasi Kahar</h2>
                    <p class="text-[11px] text-gray-500">Tanggal: {{ $exception->incident_date->translatedFormat('d F Y') }}</p>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $exception->getStatusBadgeColor() === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : ($exception->getStatusBadgeColor() === 'info' ? 'bg-blue-50 text-blue-800 border-blue-200' : ($exception->getStatusBadgeColor() === 'danger' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-amber-50 text-amber-800 border-amber-200')) }}">
                {{ $exception->getStatusLabel() }}
            </span>
        </div>

        <!-- Details Card -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm space-y-3.5">
            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider pb-1 border-b border-gray-100">
                Informasi Kejadian Luar Biasa
            </h3>

            <div class="space-y-2.5 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-gray-500">Kategori Kondisi Kahar:</span>
                    <span class="font-bold text-amber-900 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-md">
                        {{ $exception->getCategoryLabel() }}
                    </span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-gray-500">Tanggal Kejadian:</span>
                    <span class="font-bold text-gray-900">{{ $exception->incident_date->translatedFormat('l, d F Y') }}</span>
                </div>

                @if($exception->incident_time)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Waktu Kejadian:</span>
                        <span class="font-mono font-bold text-gray-900">{{ substr($exception->incident_time, 0, 5) }} WIB</span>
                    </div>
                @endif

                @if($exception->attendance)
                    <div class="p-2.5 bg-gray-50 rounded-xl border border-gray-200 space-y-1">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide block">Rekaman Absensi Terhubung:</span>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-700">Jam Datang: <strong>{{ $exception->attendance->check_in_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-' }} WIB</strong></span>
                            <span class="font-bold text-rose-700">Terlambat {{ $exception->attendance->late_minutes }} Menit (TL {{ $exception->attendance->deduction_percentage }}%)</span>
                        </div>
                    </div>
                @endif

                <div class="space-y-1 pt-1">
                    <span class="text-gray-500 block font-medium">Uraian Kronologis Kejadian:</span>
                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-800 leading-relaxed text-xs">
                        {{ $exception->description }}
                    </div>
                </div>

                @if($exception->proof_file_path)
                    <div class="pt-2">
                        <a 
                            href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($exception->proof_file_path) }}" 
                            target="_blank"
                            class="inline-flex items-center gap-2 px-3 py-2 bg-blue-50 text-[#0058A8] border border-blue-200 rounded-xl text-xs font-bold hover:bg-blue-100 transition shadow-2xs"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span>Buka Lampiran Bukti Dokumen Resmi</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Two-Tier Approval Timeline Card -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm space-y-4">
            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider pb-1 border-b border-gray-100 flex items-center justify-between">
                <span>Garis Waktu Persetujuan Dua Tingkat (Two-Tier)</span>
                <span class="text-[10px] text-gray-400 font-normal">FR-303</span>
            </h3>

            <!-- Stage 1: Supervisor Review -->
            <div class="relative pl-6 pb-4 border-l-2 {{ $exception->supervisor_reviewed_at ? 'border-emerald-500' : 'border-gray-200' }} space-y-1">
                <span class="absolute -left-2 top-0 w-4 h-4 rounded-full {{ $exception->supervisor_reviewed_at ? 'bg-emerald-500 text-white' : 'bg-gray-300 text-gray-600' }} flex items-center justify-center text-[9px] font-bold">
                    1
                </span>
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-gray-900">Tahap 1: Verifikasi Lapangan (Supervisor)</span>
                    @if($exception->supervisor_reviewed_at)
                        <span class="text-[10px] text-gray-400 font-mono">{{ $exception->supervisor_reviewed_at->translatedFormat('d M Y, H:i') }}</span>
                    @endif
                </div>

                @if($exception->supervisor_reviewed_at)
                    <p class="text-xs text-gray-700">
                        Penelaah: <strong>{{ $exception->supervisor?->name ?? 'Supervisor Lapangan' }}</strong>
                    </p>
                    @if($exception->supervisor_notes)
                        <div class="p-2.5 bg-gray-50 rounded-xl text-xs text-gray-800 italic border border-gray-200 mt-1">
                            "{{ $exception->supervisor_notes }}"
                        </div>
                    @endif
                @else
                    <p class="text-xs text-amber-700 font-medium">
                        ⏳ Menunggu verifikasi fisik &amp; validasi bukti oleh Supervisor Lapangan.
                    </p>
                @endif
            </div>

            <!-- Stage 2: Atasan Final Decision -->
            <div class="relative pl-6 space-y-1">
                <span class="absolute -left-2 top-0 w-4 h-4 rounded-full {{ $exception->status === \App\Models\LateException::STATUS_APPROVED ? 'bg-emerald-500 text-white' : ($exception->status === \App\Models\LateException::STATUS_REJECTED ? 'bg-rose-500 text-white' : 'bg-gray-300 text-gray-600') }} flex items-center justify-center text-[9px] font-bold">
                    2
                </span>
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-gray-900">Tahap 2: Keputusan Pembebasan (Kasubbag Umum / PPK)</span>
                    @if($exception->atasan_decided_at)
                        <span class="text-[10px] text-gray-400 font-mono">{{ $exception->atasan_decided_at->translatedFormat('d M Y, H:i') }}</span>
                    @endif
                </div>

                @if($exception->atasan_decided_at)
                    <p class="text-xs text-gray-700">
                        Pejabat Pemutus: <strong>{{ $exception->atasan?->name ?? 'Kasubbag Umum / PPK' }}</strong>
                    </p>
                    @if($exception->atasan_notes)
                        <div class="p-2.5 bg-gray-50 rounded-xl text-xs text-gray-800 italic border border-gray-200 mt-1">
                            "{{ $exception->atasan_notes }}"
                        </div>
                    @endif
                    @if($exception->status === \App\Models\LateException::STATUS_APPROVED)
                        <div class="p-2 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-900 text-xs font-bold mt-1">
                            ✓ Potongan Keterlambatan TL pada tanggal ini telah dibebaskan secara sah (Dianulir).
                        </div>
                    @endif
                @else
                    <p class="text-xs text-gray-500">
                        Menunggu keputusan pengesahan final setelah diverifikasi Supervisor.
                    </p>
                @endif
            </div>
        </div>
    </div>
</x-layouts.mobile>
