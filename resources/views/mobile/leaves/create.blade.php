<x-layouts.mobile title="Formulir Pengajuan Izin/Cuti">
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
                <h2 class="text-sm font-bold text-gray-900">Formulir Pengajuan Izin / Cuti</h2>
                <p class="text-[11px] text-gray-500">Alur Persetujuan Dua Tingkat (Supervisor & Atasan)</p>
            </div>
        </div>

        <!-- Information / Guidance Card -->
        <div class="bg-blue-50/70 rounded-2xl p-4 border border-blue-100 text-xs space-y-2">
            <div class="flex items-center space-x-2 text-[#0058A8] font-bold">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Ketentuan Pengajuan Izin (Lampiran II SPK)</span>
            </div>
            <ul class="list-disc list-inside space-y-1 text-gray-600 pl-1 leading-relaxed text-[11px]">
                <li>Maksimal durasi per pengajuan adalah <strong>3 hari kerja berturut-turut</strong>.</li>
                <li>Saldo kuota cuti tahunan Anda: <strong class="text-[#0058A8]">{{ $employee->leave_quota_balance }} Hari</strong>.</li>
                <li>Pengajuan <strong>Izin Sakit</strong> wajib menyertakan surat keterangan dokter (JPG/PNG/PDF max 2MB).</li>
            </ul>
        </div>

        <!-- Form Submission -->
        <form 
            action="{{ route('mobile.leaves.store') }}" 
            method="POST" 
            enctype="multipart/form-data" 
            class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm space-y-4"
        >
            @csrf

            <!-- Jenis Izin / Cuti -->
            <div>
                <label for="leave_type" class="block text-xs font-bold text-gray-700 mb-1.5">
                    Jenis Izin / Cuti <span class="text-rose-500">*</span>
                </label>
                <select 
                    id="leave_type" 
                    name="leave_type" 
                    required 
                    class="w-full text-xs rounded-xl border-gray-200 focus:border-[#0058A8] focus:ring-1 focus:ring-[#0058A8] bg-gray-50/50 p-3"
                >
                    <option value="">-- Pilih Jenis Pengajuan --</option>
                    <option value="{{ \App\Models\LeaveRequest::TYPE_ANNUAL_LEAVE }}" {{ old('leave_type') === \App\Models\LeaveRequest::TYPE_ANNUAL_LEAVE ? 'selected' : '' }}>
                        Cuti Tahunan (Mengurangi Saldo Kuota)
                    </option>
                    <option value="{{ \App\Models\LeaveRequest::TYPE_SICK_LEAVE }}" {{ old('leave_type') === \App\Models\LeaveRequest::TYPE_SICK_LEAVE ? 'selected' : '' }}>
                        Izin Sakit (Wajib Surat Dokter)
                    </option>
                    <option value="{{ \App\Models\LeaveRequest::TYPE_SPECIAL_LEAVE }}" {{ old('leave_type') === \App\Models\LeaveRequest::TYPE_SPECIAL_LEAVE ? 'selected' : '' }}>
                        Izin Khusus / Di Luar Cuti (Keluarga Meninggal/Menikah)
                    </option>
                    <option value="{{ \App\Models\LeaveRequest::TYPE_DUTY_LEAVE }}" {{ old('leave_type') === \App\Models\LeaveRequest::TYPE_DUTY_LEAVE ? 'selected' : '' }}>
                        Izin Tugas Kedinasan (Disposisi Dinas Luar)
                    </option>
                    <option value="{{ \App\Models\LeaveRequest::TYPE_EARLY_DEPARTURE }}" {{ old('leave_type') === \App\Models\LeaveRequest::TYPE_EARLY_DEPARTURE ? 'selected' : '' }}>
                        Izin Pulang Lebih Awal (Keperluan Mendesak)
                    </option>
                </select>
                @error('leave_type')
                    <p class="text-[11px] text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tanggal Mulai & Selesai -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="start_date" class="block text-xs font-bold text-gray-700 mb-1.5">
                        Mulai Tanggal <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        id="start_date" 
                        name="start_date" 
                        value="{{ old('start_date', date('Y-m-d')) }}" 
                        required 
                        class="w-full text-xs rounded-xl border-gray-200 focus:border-[#0058A8] focus:ring-1 focus:ring-[#0058A8] bg-gray-50/50 p-3"
                    >
                    @error('start_date')
                        <p class="text-[11px] text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="end_date" class="block text-xs font-bold text-gray-700 mb-1.5">
                        Sampai Tanggal <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        id="end_date" 
                        name="end_date" 
                        value="{{ old('end_date', date('Y-m-d')) }}" 
                        required 
                        class="w-full text-xs rounded-xl border-gray-200 focus:border-[#0058A8] focus:ring-1 focus:ring-[#0058A8] bg-gray-50/50 p-3"
                    >
                    @error('end_date')
                        <p class="text-[11px] text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Alasan Pengajuan -->
            <div>
                <label for="reason" class="block text-xs font-bold text-gray-700 mb-1.5">
                    Alasan / Keterangan Keperluan <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    id="reason" 
                    name="reason" 
                    rows="3" 
                    required 
                    placeholder="Contoh: Mengalami demam tinggi dan disarankan dokter beristirahat / Keperluan mendesak keluarga..."
                    class="w-full text-xs rounded-xl border-gray-200 focus:border-[#0058A8] focus:ring-1 focus:ring-[#0058A8] bg-gray-50/50 p-3"
                >{{ old('reason') }}</textarea>
                @error('reason')
                    <p class="text-[11px] text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Upload Bukti Lampiran -->
            <div>
                <label for="attachment" class="block text-xs font-bold text-gray-700 mb-1.5">
                    Berkas Bukti / Surat Keterangan Dokter
                </label>
                <input 
                    type="file" 
                    id="attachment" 
                    name="attachment" 
                    accept=".jpg,.jpeg,.png,.pdf" 
                    class="w-full text-xs rounded-xl border-gray-200 focus:border-[#0058A8] bg-gray-50/50 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-[#0058A8] hover:file:bg-blue-100"
                >
                <p class="text-[10px] text-gray-400 mt-1">Format: JPG, PNG, atau PDF (maksimal 2 MB). Wajib untuk izin sakit.</p>
                @error('attachment')
                    <p class="text-[11px] text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            @error('total_days')
                <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700">
                    {{ $message }}
                </div>
            @enderror

            <!-- Submit Button -->
            <div class="pt-2">
                <button 
                    type="submit" 
                    class="w-full py-3 px-4 rounded-xl bg-[#0058A8] hover:bg-[#004787] text-white font-bold text-xs shadow-xs transition flex items-center justify-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Kirim Pengajuan Izin</span>
                </button>
            </div>
        </form>
    </div>
</x-layouts.mobile>
