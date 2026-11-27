<x-layouts.mobile title="Detail Pengajuan Lembur">
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-2">
            <a href="{{ route('mobile.overtime.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#0058A8] hover:underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                <span>Kembali ke Daftar</span>
            </a>

            @if($overtimeRequest->request_type === \App\Models\OvertimeRequest::TYPE_WITH_LETTER)
                <a
                    href="{{ route('admin.overtime.request.print', $overtimeRequest) }}"
                    target="_blank"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-blue-50 text-[#0058A8] border border-blue-200 text-xs font-bold hover:bg-blue-100 transition"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span>Cetak SPTPL</span>
                </a>
            @endif
        </div>

        @php
            $badgeClass = match($overtimeRequest->status) {
                \App\Models\OvertimeRequest::STATUS_APPROVED => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                \App\Models\OvertimeRequest::STATUS_SUBMITTED => 'bg-blue-50 text-blue-800 border-blue-200',
                \App\Models\OvertimeRequest::STATUS_RETURNED => 'bg-amber-50 text-amber-800 border-amber-200',
                \App\Models\OvertimeRequest::STATUS_REJECTED, \App\Models\OvertimeRequest::STATUS_CANCELLED => 'bg-rose-50 text-rose-800 border-rose-200',
                default => 'bg-gray-50 text-gray-700 border-gray-200',
            };
        @endphp

        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm space-y-3">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded {{ $overtimeRequest->request_type === \App\Models\OvertimeRequest::TYPE_WITH_LETTER ? 'bg-blue-50 text-[#0058A8]' : 'bg-amber-50 text-amber-800' }}">
                        {{ $overtimeRequest->getTypeLabel() }}
                    </span>
                    <h2 class="text-sm font-extrabold text-gray-900 mt-1.5">{{ $overtimeRequest->getDisplayCode() }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $overtimeRequest->getDateRangeLabel() }}</p>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                    {{ $overtimeRequest->getStatusLabel() }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-gray-100">
                <div>
                    <p class="text-[10px] text-gray-400 font-semibold uppercase">Pengusul</p>
                    <p class="font-bold text-gray-800 mt-0.5">{{ $overtimeRequest->proposer?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 font-semibold uppercase">Penyetuju</p>
                    <p class="font-bold text-gray-800 mt-0.5">{{ $overtimeRequest->approver?->name ?? $overtimeRequest->signer_name ?? '-' }}</p>
                </div>
            </div>

            @if($overtimeRequest->approver_notes)
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-900">
                    <p class="font-bold text-[11px]">Catatan Atasan:</p>
                    <p class="mt-0.5 leading-relaxed">{{ $overtimeRequest->approver_notes }}</p>
                </div>
            @endif

            @if($overtimeRequest->cancellation_reason)
                <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 text-xs text-rose-900">
                    <p class="font-bold text-[11px]">Alasan Pembatalan:</p>
                    <p class="mt-0.5 leading-relaxed">{{ $overtimeRequest->cancellation_reason }}</p>
                </div>
            @endif
        </div>

        <!-- Daftar Pegawai & Waktu -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm space-y-3">
            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Rincian Personil &amp; Waktu Lembur</h3>

            <div class="space-y-2.5">
                @foreach($overtimeRequest->items as $item)
                    <div class="p-3 rounded-xl border {{ $employee && $item->employee_id === $employee->id ? 'bg-blue-50/50 border-blue-200' : 'bg-gray-50 border-gray-100' }} space-y-1.5">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-bold text-gray-900">
                                {{ $item->employee?->full_name ?? '-' }}
                            </p>
                            <span class="text-[11px] font-bold text-[#0058A8]">
                                {{ $item->getFormattedDuration() }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-600 font-medium">
                            Waktu: {{ substr((string) $item->start_time, 0, 5) }} - {{ substr((string) $item->end_time, 0, 5) }} WIB
                        </p>
                        <p class="text-xs text-gray-700 leading-relaxed">
                            {{ $item->description }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.mobile>
