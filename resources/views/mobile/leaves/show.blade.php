<x-layouts.mobile title="Rincian Permohonan Izin">
    <div class="space-y-4">
        <!-- Back Navigation & Title -->
        <div class="flex items-center space-x-3">
            <a 
                href="{{ route('mobile.leaves.index') }}" 
                class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50 transition shadow-xs"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <div>
                <h2 class="text-sm font-bold text-gray-900">Rincian Permohonan #{{ $leaveRequest->id }}</h2>
                <p class="text-[11px] text-gray-500">Status Persetujuan Berjenjang</p>
            </div>
        </div>

        <!-- Main Details Card -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm space-y-4">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider block">Jenis Pengajuan</span>
                    <h3 class="text-base font-bold text-gray-900 mt-0.5">
                        {{ $leaveRequest->getTypeLabel() }}
                    </h3>
                </div>
                <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full border {{ match($leaveRequest->status) {
                    \App\Models\LeaveRequest::STATUS_APPROVED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    \App\Models\LeaveRequest::STATUS_REJECTED => 'bg-rose-50 text-rose-700 border-rose-200',
                    \App\Models\LeaveRequest::STATUS_PENDING_ATASAN => 'bg-blue-50 text-[#0058A8] border-blue-200',
                    \App\Models\LeaveRequest::STATUS_CANCELLED => 'bg-gray-50 text-gray-600 border-gray-200',
                    default => 'bg-amber-50 text-amber-700 border-amber-200',
                } }}">
                    {{ $leaveRequest->getStatusLabel() }}
                </span>
            </div>

            <!-- Rentang Tanggal & Durasi -->
            <div class="grid grid-cols-2 gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100 text-xs">
                <div>
                    <span class="text-[10px] text-gray-400 block font-medium">Rentang Hari</span>
                    <span class="font-bold text-gray-900">
                        {{ \Carbon\Carbon::parse($leaveRequest->start_date)->translatedFormat('d M Y') }}
                        @if($leaveRequest->start_date !== $leaveRequest->end_date)
                            &ndash; {{ \Carbon\Carbon::parse($leaveRequest->end_date)->translatedFormat('d M Y') }}
                        @endif
                    </span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] text-gray-400 block font-medium">Total Durasi</span>
                    <span class="font-extrabold text-[#0058A8]">{{ $leaveRequest->total_days }} Hari Kerja</span>
                </div>
            </div>

            <!-- Alasan -->
            <div>
                <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider block mb-1">Alasan Keperluan</span>
                <p class="text-xs text-gray-700 bg-gray-50/50 p-3 rounded-xl border border-gray-100 leading-relaxed">
                    {{ $leaveRequest->reason }}
                </p>
            </div>

            <!-- Berkas Bukti Lampiran -->
            @if($leaveRequest->attachment_path)
                <div>
                    <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider block mb-1">Berkas Lampiran</span>
                    <a 
                        href="{{ asset('storage/' . $leaveRequest->attachment_path) }}" 
                        target="_blank" 
                        class="inline-flex items-center gap-2 px-3 py-2 bg-blue-50 text-[#0058A8] text-xs font-semibold rounded-xl border border-blue-200 hover:bg-blue-100 transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                        <span>Lihat Berkas Surat / Dokumen</span>
                    </a>
                </div>
            @endif

            <!-- SLA Overdue Warning (FR-508) -->
            @if($leaveRequest->isOverdueSla())
                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800 flex items-start gap-2">
                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div>
                        <span class="font-bold block">Pemberitahuan SLA Pelayanan</span>
                        Pengajuan ini telah berada dalam antrean lebih dari 2 hari kerja ({{ $leaveRequest->getPendingWorkingDays() }} hari kerja). Anda dapat mengonfirmasi tindak lanjut kepada supervisor atau atasan langsung.
                    </div>
                </div>
            @endif
        </div>

        <!-- Two-Tier Approval Timeline (FR-501, FR-504, FR-505) -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm space-y-4">
            <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                Alur Persetujuan Berjenjang (Two-Tier)
            </h4>

            <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-gray-200">
                <!-- Tahap 0: Pengajuan Mandiri Pegawai -->
                <div class="relative">
                    <div class="absolute -left-6 top-0 w-4 h-4 rounded-full bg-emerald-500 border-2 border-white ring-2 ring-emerald-200"></div>
                    <div>
                        <h5 class="text-xs font-bold text-gray-900">Pengajuan Mandiri Pegawai</h5>
                        <p class="text-[11px] text-gray-500">
                            Diajukan pada {{ \Carbon\Carbon::parse($leaveRequest->created_at)->translatedFormat('d M Y, H:i') }} WIB
                        </p>
                    </div>
                </div>

                <!-- Tahap 1: Verifikasi Supervisor Lapangan -->
                <div class="relative">
                    <div class="absolute -left-6 top-0 w-4 h-4 rounded-full {{ match($leaveRequest->supervisor_status) {
                        \App\Models\LeaveRequest::SUPERVISOR_STATUS_VERIFIED => 'bg-emerald-500 ring-emerald-200',
                        \App\Models\LeaveRequest::SUPERVISOR_STATUS_REJECTED => 'bg-rose-500 ring-rose-200',
                        default => 'bg-amber-400 ring-amber-100',
                    } }} border-2 border-white ring-2"></div>
                    <div>
                        <div class="flex items-center justify-between">
                            <h5 class="text-xs font-bold text-gray-900">Tahap 1: Verifikasi Faktual Supervisor</h5>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ match($leaveRequest->supervisor_status) {
                                \App\Models\LeaveRequest::SUPERVISOR_STATUS_VERIFIED => 'bg-emerald-50 text-emerald-700',
                                \App\Models\LeaveRequest::SUPERVISOR_STATUS_REJECTED => 'bg-rose-50 text-rose-700',
                                default => 'bg-amber-50 text-amber-700',
                            } }}">
                                {{ $leaveRequest->getSupervisorStatusLabel() }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-0.5">
                            Supervisor: <strong>{{ $leaveRequest->supervisor?->name ?? 'Belum ditentukan' }}</strong>
                            @if($leaveRequest->supervisor_action_at)
                                &bull; {{ \Carbon\Carbon::parse($leaveRequest->supervisor_action_at)->translatedFormat('d M Y, H:i') }} WIB
                            @endif
                        </p>
                        @if($leaveRequest->supervisor_notes)
                            <div class="mt-1.5 p-2 bg-gray-50 rounded-lg text-[11px] text-gray-700 italic border border-gray-100">
                                &ldquo;{{ $leaveRequest->supervisor_notes }}&rdquo;
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Tahap 2: Putusan Akhir Pejabat Atasan (Kasubbag Umum/PPK) -->
                <div class="relative">
                    <div class="absolute -left-6 top-0 w-4 h-4 rounded-full {{ match($leaveRequest->status) {
                        \App\Models\LeaveRequest::STATUS_APPROVED => 'bg-emerald-500 ring-emerald-200',
                        \App\Models\LeaveRequest::STATUS_REJECTED => 'bg-rose-500 ring-rose-200',
                        default => 'bg-gray-300 ring-gray-100',
                    } }} border-2 border-white ring-2"></div>
                    <div>
                        <div class="flex items-center justify-between">
                            <h5 class="text-xs font-bold text-gray-900">Tahap 2: Putusan Akhir Pejabat Atasan</h5>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ match($leaveRequest->status) {
                                \App\Models\LeaveRequest::STATUS_APPROVED => 'bg-emerald-50 text-emerald-700',
                                \App\Models\LeaveRequest::STATUS_REJECTED => 'bg-rose-50 text-rose-700',
                                \App\Models\LeaveRequest::STATUS_PENDING_ATASAN => 'bg-blue-50 text-[#0058A8]',
                                default => 'bg-gray-50 text-gray-600',
                            } }}">
                                {{ match($leaveRequest->status) {
                                    \App\Models\LeaveRequest::STATUS_APPROVED => 'Disetujui Sah',
                                    \App\Models\LeaveRequest::STATUS_REJECTED => 'Ditolak Atasan',
                                    \App\Models\LeaveRequest::STATUS_PENDING_ATASAN => 'Menunggu Putusan',
                                    default => 'Menunggu Tahap 1',
                                } }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-0.5">
                            Pejabat Penyetuju: <strong>{{ $leaveRequest->atasan?->name ?? 'Kasubbag Umum / PPK' }}</strong>
                            @if($leaveRequest->approved_at)
                                &bull; {{ \Carbon\Carbon::parse($leaveRequest->approved_at)->translatedFormat('d M Y, H:i') }} WIB
                            @endif
                        </p>
                        @if($leaveRequest->atasan_notes)
                            <div class="mt-1.5 p-2 bg-gray-50 rounded-lg text-[11px] text-gray-700 italic border border-gray-100">
                                &ldquo;{{ $leaveRequest->atasan_notes }}&rdquo;
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Action (if pending) -->
        @if($leaveRequest->canBeCancelledBy(Auth::user()))
            <div class="pt-2">
                <form 
                    action="{{ route('mobile.leaves.cancel', $leaveRequest) }}" 
                    method="POST" 
                    onsubmit="return confirm('Apakah Anda yakin ingin membatalkan permohonan ini? Tindakan ini tidak dapat diurungkan.');"
                >
                    @csrf
                    <button 
                        type="submit" 
                        class="w-full py-3 px-4 rounded-xl bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold text-xs shadow-xs transition flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        <span>Batalkan Permohonan Ini</span>
                    </button>
                </form>
            </div>
        @endif
    </div>
</x-layouts.mobile>
