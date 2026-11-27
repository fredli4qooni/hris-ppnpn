<x-layouts.mobile title="Buku Disiplin & Pelanggaran">
    <div class="space-y-4" x-data="{ activeTab: 'all', previewPhoto: null }">

        <!-- Top Navigation & Page Title -->
        <div class="flex items-center justify-between">
            <a href="{{ route('mobile.home') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-[#0058A8] transition">
                <span>&larr;</span>
                <span>Beranda</span>
            </a>
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-[#0058A8] border border-blue-200">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                <span>Akumulasi 2 (SPK)</span>
            </span>
        </div>

        <!-- Main Header & Active Points Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center relative overflow-hidden">
            <!-- Top Gradient Accent -->
            <div class="absolute top-0 left-0 right-0 h-2 {{ $ewsStatus['is_critical'] ? 'bg-rose-600 animate-pulse' : ($ewsStatus['is_warning'] ? 'bg-amber-500' : 'bg-[#0058A8]') }}"></div>

            <div class="pt-2">
                <div class="w-12 h-12 mx-auto rounded-2xl {{ $ewsStatus['is_critical'] ? 'bg-rose-50 text-rose-600 border border-rose-200' : ($ewsStatus['is_warning'] ? 'bg-amber-50 text-amber-600 border border-amber-200' : 'bg-blue-50 text-[#0058A8] border border-blue-200') }} flex items-center justify-center font-bold text-xl shadow-xs">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>

                <h2 class="mt-2.5 font-bold text-gray-900 text-base">
                    Buku Catatan Disiplin & Pelanggaran
                </h2>
                <p class="text-[11px] text-gray-500 mt-0.5">
                    Transparansi Catatan Butir Pelanggaran & Akumulasi Sanksi SPK
                </p>

                <!-- Points Display Box -->
                <div class="mt-4 p-4 rounded-2xl bg-gray-50 border border-gray-200/80">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">
                        Saldo Poin Pelanggaran Aktif
                    </span>
                    <div class="mt-1 flex items-baseline justify-center gap-1.5">
                        <span class="text-3xl font-black font-mono {{ $ewsStatus['is_critical'] ? 'text-rose-700' : ($ewsStatus['is_warning'] ? 'text-amber-700' : 'text-gray-900') }}">
                            {{ $activePoints }}
                        </span>
                        <span class="text-xs font-bold text-gray-500">Poin</span>
                    </div>

                    <!-- EWS Badge -->
                    <div class="mt-2.5">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold border {{ $ewsStatus['is_critical'] ? 'bg-rose-100 text-rose-800 border-rose-300' : ($ewsStatus['is_warning'] ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-emerald-100 text-emerald-800 border-emerald-300') }}">
                            {{ $ewsStatus['label'] }}
                        </span>
                    </div>

                    <!-- EWS Explanation -->
                    <p class="text-[11px] text-gray-600 mt-2.5 leading-relaxed">
                        {{ $ewsStatus['description'] }}
                    </p>
                </div>

                <!-- Point Threshold Progress Bar -->
                <div class="mt-4 text-left">
                    <div class="flex justify-between items-center text-[10px] text-gray-500 font-semibold mb-1">
                        <span>Skala Ambang Batas Poin Sanksi</span>
                        <span>Maks. 100 Poin</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden flex">
                        <div class="bg-emerald-500 h-2.5 transition-all" style="width: {{ min(20, $activePoints) }}%" title="Teguran Lisan (0-20)"></div>
                        <div class="bg-blue-500 h-2.5 transition-all" style="width: {{ max(0, min(20, $activePoints - 20)) }}%" title="SP 1 (21-40)"></div>
                        <div class="bg-amber-500 h-2.5 transition-all" style="width: {{ max(0, min(20, $activePoints - 40)) }}%" title="SP 2A (41-60)"></div>
                        <div class="bg-orange-500 h-2.5 transition-all" style="width: {{ max(0, min(20, $activePoints - 60)) }}%" title="SP 2B (61-80)"></div>
                        <div class="bg-rose-500 h-2.5 transition-all" style="width: {{ max(0, min(19, $activePoints - 80)) }}%" title="SP 2C (81-99)"></div>
                        <div class="bg-red-700 h-2.5 transition-all" style="width: {{ $activePoints >= 100 ? 1 : 0 }}%" title="SP 3 (≥100)"></div>
                    </div>
                    <div class="flex justify-between text-[9px] text-gray-400 font-mono mt-1">
                        <span>0 (Aman)</span>
                        <span>60 (Waspada)</span>
                        <span>85 (Kritis)</span>
                        <span>100 (SP 3)</span>
                    </div>
                </div>

                <!-- Download BAP Button -->
                <div class="mt-4 pt-3 border-t border-gray-100">
                    <a href="{{ route('admin.employees.discipline-summary', $employee) }}" target="_blank" class="w-full inline-flex items-center justify-center gap-2 py-2 px-3.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-[#0058A8] border border-blue-200 text-xs font-bold transition">
                        <svg class="w-4 h-4 text-[#0058A8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span>Unduh Berita Acara Rekam Jejak (BAP)</span>
                        <span>↗</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Active Sanction Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3">
            <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                <h3 class="text-xs font-bold text-[#0B2A4A] uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"></path></svg>
                    <span>Status Sanksi Disiplin</span>
                </h3>
                <span class="text-[10px] font-bold text-gray-400 font-mono">Lampiran III SPK</span>
            </div>

            <!-- Isolation Notice -->
            <div class="p-2.5 rounded-xl bg-amber-50/70 border border-amber-200 text-[10px] text-amber-900 leading-relaxed flex items-start gap-2">
                <span class="text-amber-600 font-bold shrink-0">ℹ️</span>
                <div>
                    <strong>Pemisahan Tegas Akumulasi:</strong> Evaluasi Surat Peringatan (SP) ini merupakan <em>Akumulasi 2</em>, terpisah dari keterlambatan harian (<em>Akumulasi 1</em>).
                </div>
            </div>

            @if($activeSanction)
                <div class="p-3.5 rounded-xl border border-rose-200 bg-rose-50/50 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-rose-900">
                            {{ $activeSanction->getTypeLabel() }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-600 text-white">
                            Sanksi Aktif
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="p-2 bg-white rounded-lg border border-rose-100">
                            <span class="text-[10px] text-gray-500 block">Bobot Sanksi</span>
                            <span class="font-bold text-rose-700 font-mono">
                                {{ (float) $activeSanction->deduction_percentage }}%
                            </span>
                        </div>

                        <div class="p-2 bg-white rounded-lg border border-rose-100">
                            <span class="text-[10px] text-gray-500 block">Durasi Pembinaan</span>
                            <span class="font-bold text-gray-900 font-mono">
                                {{ $activeSanction->duration_months }} Bulan
                            </span>
                        </div>
                    </div>

                    <div class="text-[11px] text-gray-600 pt-1 border-t border-rose-100 flex justify-between items-center">
                        <span>Periode Berjalan:</span>
                        <span class="font-bold text-gray-900">
                            @if($activeSanction->start_date && $activeSanction->end_date)
                                {{ $activeSanction->start_date->translatedFormat('F Y') }} s.d. {{ $activeSanction->end_date->translatedFormat('F Y') }}
                            @else
                                Menunggu Periode Berikutnya
                            @endif
                        </span>
                    </div>

                    @if($activeSanction->is_termination_recommended)
                        <div class="p-2 rounded bg-red-100 border border-red-300 text-[10px] font-bold text-red-900">
                            ⚠️ SP 3 memuat rekomendasi resmi pemutusan hubungan kontrak kerja sepihak oleh Pejabat Pembuat Komitmen (PPK).
                        </div>
                    @endif
                </div>
            @else
                <!-- No Active Sanction State -->
                <div class="p-3.5 rounded-xl border border-emerald-200 bg-emerald-50/50 flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 font-bold">
                        ✓
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-emerald-900">Tidak Ada Sanksi Berjalan</h4>
                        <p class="text-[11px] text-emerald-700 mt-0.5">
                            Saat ini Anda tidak memiliki Surat Peringatan aktif.
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Recorded Violations List (Discipline Ledger Records) -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-[#0B2A4A] uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-[#0058A8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <span>Daftar Riwayat Pelanggaran ({{ $violations->count() }})</span>
                </h3>

                <!-- Filter Tabs -->
                <div class="flex bg-gray-200/80 p-0.5 rounded-lg text-[10px] font-bold">
                    <button 
                        type="button" 
                        @click="activeTab = 'all'" 
                        :class="activeTab === 'all' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                        class="px-2.5 py-1 rounded-md transition"
                    >
                        Semua
                    </button>
                    <button 
                        type="button" 
                        @click="activeTab = 'active'" 
                        :class="activeTab === 'active' ? 'bg-white text-rose-700 shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                        class="px-2.5 py-1 rounded-md transition"
                    >
                        Aktif ({{ $activeViolationsCount }})
                    </button>
                    <button 
                        type="button" 
                        @click="activeTab = 'cancelled'" 
                        :class="activeTab === 'cancelled' ? 'bg-white text-gray-600 shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                        class="px-2.5 py-1 rounded-md transition"
                    >
                        Batal ({{ $cancelledViolationsCount }})
                    </button>
                </div>
            </div>

            <!-- Violations Loop -->
            <div class="space-y-2.5">
                @forelse($violations as $v)
                    <div 
                        x-show="activeTab === 'all' || (activeTab === 'active' && '{{ $v->status }}' === 'ACTIVE') || (activeTab === 'cancelled' && '{{ $v->status }}' === 'CANCELLED')"
                        class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm space-y-2.5 transition"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded font-mono font-bold text-[10px] bg-blue-50 text-[#0058A8] border border-blue-200">
                                    {{ $v->catalog?->clause_number ?? 'SPK' }}
                                </span>
                                <span class="text-xs font-bold text-gray-900">
                                    {{ $v->catalog?->name ?? 'Pelanggaran Disiplin' }}
                                </span>
                            </div>

                            @if($v->status === 'ACTIVE')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 shrink-0 font-mono">
                                    +{{ $v->points }} Poin
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500 border border-gray-200 shrink-0 line-through font-mono">
                                    {{ $v->points }} Poin
                                </span>
                            @endif
                        </div>

                        <!-- Incident Details -->
                        <div class="grid grid-cols-2 gap-2 text-[11px] text-gray-600 border-y border-gray-50 py-2">
                            <div>
                                <span class="text-gray-400 block text-[10px]">Tanggal Kejadian</span>
                                <span class="font-semibold text-gray-800">{{ $v->incident_date?->translatedFormat('d F Y') ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px]">Supervisor Pencatat</span>
                                <span class="font-semibold text-gray-800">{{ $v->supervisor?->name ?? '-' }}</span>
                            </div>
                        </div>

                        <!-- Notes / Chronology -->
                        @if($v->notes)
                            <div class="text-[11px] text-gray-700 bg-gray-50 p-2.5 rounded-xl border border-gray-100 leading-relaxed">
                                <strong class="text-gray-500 text-[10px] block mb-0.5">Uraian / Kronologi:</strong>
                                {{ $v->notes }}
                            </div>
                        @endif

                        <!-- Photo Evidence Attachment -->
                        @if($v->evidence_photo_path)
                            <div class="pt-1">
                                <button 
                                    type="button" 
                                    @click="previewPhoto = '{{ asset('storage/' . $v->evidence_photo_path) }}'"
                                    class="inline-flex items-center gap-1.5 text-[11px] font-bold text-[#0058A8] hover:underline"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <span>Lihat Foto Bukti Dokumentasi</span>
                                </button>
                            </div>
                        @endif

                        <!-- Cancellation Notice if cancelled -->
                        @if($v->status === 'CANCELLED')
                            <div class="p-2 rounded-lg bg-gray-100 border border-gray-200 text-[10px] text-gray-600 flex items-start gap-1.5">
                                <span class="font-bold text-gray-500">🚫 DIBATALKAN:</span>
                                <span>{{ $v->cancellation_reason ?? 'Dibatalkan oleh pihak berwenang.' }}</span>
                            </div>
                        @endif
                    </div>
                @empty
                    <!-- Empty State -->
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm text-center space-y-2">
                        <div class="w-12 h-12 mx-auto rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xl">
                            ✓
                        </div>
                        <h4 class="text-sm font-bold text-gray-900">Buku Catatan Bersih</h4>
                        <p class="text-xs text-gray-500 max-w-xs mx-auto leading-relaxed">
                            Belum ada catatan pelanggaran disiplin yang tercatat. Tetap pertahankan integritas dan kedisiplinan kerja Anda!
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Photo Preview Modal -->
        <div 
            x-show="previewPhoto" 
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
            @click.self="previewPhoto = null"
        >
            <div class="bg-white rounded-2xl overflow-hidden max-w-sm w-full p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                    <h4 class="text-xs font-bold text-gray-900">Dokumentasi Bukti Pelanggaran</h4>
                    <button type="button" @click="previewPhoto = null" class="text-gray-400 hover:text-gray-600 font-bold text-sm">
                        ✕
                    </button>
                </div>
                <div class="rounded-xl overflow-hidden bg-gray-100 max-h-80 flex items-center justify-center">
                    <img :src="previewPhoto" alt="Bukti Foto" class="max-h-80 w-auto object-contain">
                </div>
                <button type="button" @click="previewPhoto = null" class="w-full py-2 bg-gray-100 hover:bg-gray-200 rounded-xl text-xs font-bold text-gray-700 transition">
                    Tutup Pratinjau
                </button>
            </div>
        </div>

    </div>
</x-layouts.mobile>
