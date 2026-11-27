<x-layouts.mobile title="Permohonan Izin & Cuti">
    <div class="space-y-4">
        <!-- Header & Quota Metric Card -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#0058A8] flex items-center justify-center font-bold shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Permohonan Izin & Cuti</h2>
                        <p class="text-[11px] text-gray-500">Persetujuan Supervisor & Atasan</p>
                    </div>
                </div>
                <a 
                    href="{{ route('mobile.leaves.create') }}" 
                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-[#0058A8] text-white rounded-xl text-xs font-bold hover:bg-[#004787] transition shadow-xs"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Ajukan</span>
                </a>
            </div>

            <!-- Balances -->
            <div class="grid grid-cols-2 gap-2.5 pt-2 border-t border-gray-100">
                <div class="bg-amber-50/60 p-2.5 rounded-xl border border-amber-100">
                    <span class="text-[10px] text-amber-800 font-medium block">Sisa Cuti Tahunan</span>
                    <div class="text-base font-extrabold text-amber-900 mt-0.5">
                        {{ $employee->leave_quota_balance }} <span class="text-xs font-normal text-amber-700">Hari</span>
                    </div>
                </div>
                <div class="bg-blue-50/60 p-2.5 rounded-xl border border-blue-100">
                    <span class="text-[10px] text-blue-800 font-medium block">Aturan Pengajuan</span>
                    <div class="text-[11px] font-semibold text-blue-900 mt-0.5 leading-snug">
                        Maks. 3 Hari Beruntun
                    </div>
                </div>
            </div>
        </div>

        <!-- Requests List -->
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                    Daftar Pengajuan Mandiri ({{ $requests->total() }})
                </h3>
            </div>

            @if($requests->isEmpty())
                <div class="bg-white rounded-2xl border border-gray-100 p-8 text-center space-y-3 shadow-sm">
                    <div class="w-12 h-12 mx-auto rounded-full bg-blue-50 text-[#0058A8] flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900">Belum Ada Pengajuan</h4>
                        <p class="text-xs text-gray-500 mt-1 max-w-xs mx-auto">
                            Anda belum pernah mengajukan cuti, izin sakit, atau izin dinas melalui portal mobile ini.
                        </p>
                    </div>
                    <div class="pt-2">
                        <a 
                            href="{{ route('mobile.leaves.create') }}" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#0058A8] text-white text-xs font-bold rounded-xl shadow-xs hover:bg-[#004787] transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span>Buat Pengajuan Pertama</span>
                        </a>
                    </div>
                </div>
            @else
                @foreach($requests as $req)
                    <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm space-y-3 transition hover:border-blue-200">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider block">
                                    {{ \Carbon\Carbon::parse($req->created_at)->translatedFormat('d M Y') }}
                                </span>
                                <h4 class="font-bold text-sm text-gray-900 mt-0.5">
                                    {{ $req->getTypeLabel() }}
                                </h4>
                            </div>
                            <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full border {{ match($req->status) {
                                \App\Models\LeaveRequest::STATUS_APPROVED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                \App\Models\LeaveRequest::STATUS_REJECTED => 'bg-rose-50 text-rose-700 border-rose-200',
                                \App\Models\LeaveRequest::STATUS_PENDING_ATASAN => 'bg-blue-50 text-[#0058A8] border-blue-200',
                                \App\Models\LeaveRequest::STATUS_CANCELLED => 'bg-gray-50 text-gray-600 border-gray-200',
                                default => 'bg-amber-50 text-amber-700 border-amber-200',
                            } }}">
                                {{ $req->getStatusLabel() }}
                            </span>
                        </div>

                        <!-- Dates & Duration Info -->
                        <div class="bg-gray-50 rounded-xl p-2.5 border border-gray-100 text-xs text-gray-700 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-gray-400 block font-medium">Rentang Tanggal</span>
                                <span class="font-semibold text-gray-900">
                                    {{ \Carbon\Carbon::parse($req->start_date)->translatedFormat('d M Y') }}
                                    @if($req->start_date !== $req->end_date)
                                        &ndash; {{ \Carbon\Carbon::parse($req->end_date)->translatedFormat('d M Y') }}
                                    @endif
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] text-gray-400 block font-medium">Durasi</span>
                                <span class="font-bold text-[#0058A8]">{{ $req->total_days }} Hari Kerja</span>
                            </div>
                        </div>

                        <p class="text-xs text-gray-600 line-clamp-2 italic">
                            &ldquo;{{ $req->reason }}&rdquo;
                        </p>

                        <!-- Action Bar -->
                        <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-xs">
                            <span class="text-[11px] text-gray-400">
                                Tahap 1: <strong class="{{ $req->supervisor_status === \App\Models\LeaveRequest::SUPERVISOR_STATUS_VERIFIED ? 'text-emerald-700' : ($req->supervisor_status === \App\Models\LeaveRequest::SUPERVISOR_STATUS_REJECTED ? 'text-rose-700' : 'text-amber-700') }}">{{ $req->getSupervisorStatusLabel() }}</strong>
                            </span>
                            <a 
                                href="{{ route('mobile.leaves.show', $req) }}" 
                                class="font-bold text-[#0058A8] hover:underline flex items-center gap-1"
                            >
                                <span>Lihat Rincian</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    </div>
                @endforeach

                <div class="mt-4">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.mobile>
