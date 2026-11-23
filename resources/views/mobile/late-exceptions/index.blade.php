<x-layouts.mobile title="Dispensasi Keterlambatan Kahar">
    <div class="space-y-4">
        <!-- Header & Policy Card -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Dispensasi Kahar (Force Majeure)</h2>
                        <p class="text-[11px] text-gray-500">Pembebasan Sanksi Potongan TL (FR-303 / US-502)</p>
                    </div>
                </div>
                <a 
                    href="{{ route('mobile.late-exceptions.create') }}" 
                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-600 text-white rounded-xl text-xs font-bold hover:bg-amber-700 transition shadow-xs"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Ajukan</span>
                </a>
            </div>

            <!-- Policy Information -->
            <div class="bg-blue-50/70 p-3 rounded-xl border border-blue-200/80 text-xs text-blue-900 space-y-1">
                <div class="font-bold flex items-center gap-1.5 text-blue-950">
                    <svg class="w-4 h-4 text-[#0058A8] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Ketentuan Pembebasan Potongan Keterlambatan:</span>
                </div>
                <p class="text-[11px] text-blue-800 leading-relaxed">
                    Karyawan yang terlambat hadir akibat peristiwa di luar kendali wajar (Bencana Alam, Kecelakaan Lalu Lintas, Musibah Keluarga Inti) dapat mengajukan pembebasan potongan honor TL dengan melampirkan bukti resmi dan disahkan berjenjang oleh <strong>Supervisor Lapangan</strong> &amp; <strong>Kasubbag Umum / PPK</strong>.
                </p>
            </div>
        </div>

        <!-- Dispensation Requests List -->
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                    Daftar Pengajuan Dispensasi ({{ $exceptions->total() }})
                </h3>
            </div>

            @if($exceptions->isEmpty())
                <div class="bg-white rounded-2xl border border-gray-100 p-8 text-center space-y-3 shadow-sm">
                    <div class="w-12 h-12 mx-auto rounded-full bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900">Belum Ada Pengajuan Dispensasi</h4>
                        <p class="text-xs text-gray-500 mt-1 max-w-xs mx-auto">
                            Anda belum pernah mengajukan pembebasan sanksi keterlambatan akibat kondisi kahar.
                        </p>
                    </div>
                    <div class="pt-2">
                        <a 
                            href="{{ route('mobile.late-exceptions.create') }}" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-50 text-amber-800 rounded-xl text-xs font-bold hover:bg-amber-100 transition border border-amber-200"
                        >
                            <span>Buat Pengajuan Baru</span>
                        </a>
                    </div>
                </div>
            @else
                <div class="space-y-2.5">
                    @foreach($exceptions as $item)
                        @php
                            $badgeClass = match($item->status) {
                                \App\Models\LateException::STATUS_APPROVED => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                \App\Models\LateException::STATUS_APPROVED_SUPERVISOR => 'bg-blue-50 text-blue-800 border-blue-200',
                                \App\Models\LateException::STATUS_REJECTED, \App\Models\LateException::STATUS_REJECTED_SUPERVISOR => 'bg-rose-50 text-rose-800 border-rose-200',
                                default => 'bg-amber-50 text-amber-800 border-amber-200',
                            };
                        @endphp
                        <a 
                            href="{{ route('mobile.late-exceptions.show', $item) }}" 
                            class="block bg-white p-3.5 rounded-2xl border border-gray-100 shadow-2xs hover:border-amber-300 transition space-y-2.5"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-extrabold text-gray-900">
                                        {{ $item->incident_date->translatedFormat('d F Y') }}
                                    </span>
                                    <span class="text-[10px] text-amber-900 font-semibold bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded">
                                        {{ $item->getCategoryLabel() }}
                                    </span>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                                    {{ $item->getStatusLabel() }}
                                </span>
                            </div>

                            <p class="text-xs text-gray-600 line-clamp-2 leading-relaxed">
                                {{ $item->description }}
                            </p>

                            <div class="pt-1 border-t border-gray-50 flex items-center justify-between text-[11px] text-gray-400">
                                <div>
                                    @if($item->incident_time)
                                        <span>Waktu Kejadian: {{ substr($item->incident_time, 0, 5) }} WIB</span>
                                    @else
                                        <span>Diajukan: {{ $item->created_at->translatedFormat('d M Y, H:i') }}</span>
                                    @endif
                                </div>
                                <span class="text-amber-700 font-semibold flex items-center gap-0.5">
                                    <span>Lihat Detail</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="pt-2">
                    {{ $exceptions->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.mobile>
