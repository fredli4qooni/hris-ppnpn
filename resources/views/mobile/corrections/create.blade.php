<x-layouts.mobile title="Formulir Koreksi Lupa Absen">
    <div class="space-y-4">
        <!-- Top Title Bar -->
        <div class="flex items-center space-x-3">
            <a href="{{ route('mobile.corrections.index') }}" class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600 shadow-2xs hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <div>
                <h1 class="text-sm font-bold text-gray-900">Pengajuan Koreksi Lupa Absen</h1>
                <p class="text-[11px] text-gray-500">Maksimal H+2 Hari Kerja Setelah Tanggal Kejadian</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-200 rounded-2xl p-3.5 text-xs text-rose-800 space-y-1">
                <div class="font-bold flex items-center gap-1.5 text-rose-900">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Terjadi Kesalahan Validasi:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-[11px] pl-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('mobile.corrections.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3.5">
            @csrf

            <!-- Attendance Date Card -->
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm space-y-3">
                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-[#0058A8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>1. Tanggal Kejadian Lupa Absen</span>
                </h3>

                <div>
                    <label for="attendance_date" class="block text-xs font-bold text-gray-700 mb-1">
                        Pilih Tanggal: <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="attendance_date" 
                        id="attendance_date" 
                        value="{{ old('attendance_date', $maxDate) }}"
                        min="{{ $minDate }}"
                        max="{{ $maxDate }}"
                        required
                        class="w-full text-xs rounded-xl border border-gray-200 px-3 py-2.5 focus:border-[#0058A8] focus:ring-1 focus:ring-[#0058A8] outline-none"
                    />
                    <p class="text-[10px] text-gray-400 mt-1">
                        Rentang pengajuan yang diizinkan: <strong>{{ \Carbon\Carbon::parse($minDate)->translatedFormat('d M Y') }}</strong> s.d. <strong>{{ \Carbon\Carbon::parse($maxDate)->translatedFormat('d M Y') }}</strong>.
                    </p>
                </div>
            </div>

            <!-- Correction Type Card -->
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm space-y-3">
                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-[#0058A8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>2. Jenis Koreksi Kehadiran</span>
                </h3>

                <div class="grid grid-cols-3 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="correction_type" value="CHECK_IN" class="peer sr-only" {{ old('correction_type') === 'CHECK_IN' ? 'checked' : '' }}>
                        <div class="p-2.5 rounded-xl border border-gray-200 text-center peer-checked:border-[#0058A8] peer-checked:bg-blue-50/50 peer-checked:text-[#0058A8] transition">
                            <span class="block text-xs font-bold">Lupa Masuk</span>
                            <span class="block text-[9px] text-gray-400 mt-0.5">Check-in</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="correction_type" value="CHECK_OUT" class="peer sr-only" {{ old('correction_type') === 'CHECK_OUT' ? 'checked' : '' }}>
                        <div class="p-2.5 rounded-xl border border-gray-200 text-center peer-checked:border-[#0058A8] peer-checked:bg-blue-50/50 peer-checked:text-[#0058A8] transition">
                            <span class="block text-xs font-bold">Lupa Pulang</span>
                            <span class="block text-[9px] text-gray-400 mt-0.5">Check-out</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="correction_type" value="BOTH" class="peer sr-only" {{ old('correction_type', 'BOTH') === 'BOTH' ? 'checked' : '' }}>
                        <div class="p-2.5 rounded-xl border border-gray-200 text-center peer-checked:border-[#0058A8] peer-checked:bg-blue-50/50 peer-checked:text-[#0058A8] transition">
                            <span class="block text-xs font-bold">Lupa Masuk & Pulang</span>
                            <span class="block text-[9px] text-gray-400 mt-0.5">Keduanya</span>
                        </div>
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-1">
                    <div>
                        <label for="suggested_check_in_at" class="block text-[11px] font-semibold text-gray-600 mb-1">
                            Estimasi Jam Masuk:
                        </label>
                        <input 
                            type="time" 
                            name="suggested_check_in_at" 
                            id="suggested_check_in_at" 
                            value="{{ old('suggested_check_in_at', '07:30') }}"
                            class="w-full text-xs rounded-xl border border-gray-200 px-3 py-2 focus:border-[#0058A8] outline-none"
                        />
                    </div>
                    <div>
                        <label for="suggested_check_out_at" class="block text-[11px] font-semibold text-gray-600 mb-1">
                            Estimasi Jam Pulang:
                        </label>
                        <input 
                            type="time" 
                            name="suggested_check_out_at" 
                            id="suggested_check_out_at" 
                            value="{{ old('suggested_check_out_at', '16:30') }}"
                            class="w-full text-xs rounded-xl border border-gray-200 px-3 py-2 focus:border-[#0058A8] outline-none"
                        />
                    </div>
                </div>
            </div>

            <!-- Activity Summary & Proof Upload Card -->
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm space-y-3">
                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-[#0058A8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>3. Rincian Aktivitas & Berkas Bukti</span>
                </h3>

                <div>
                    <label for="work_summary" class="block text-xs font-bold text-gray-700 mb-1">
                        Uraian Aktivitas Tugas Dinas: <span class="text-rose-500">*</span>
                    </label>
                    <textarea 
                        name="work_summary" 
                        id="work_summary" 
                        rows="3" 
                        required
                        placeholder="Contoh: Menjalankan tugas pengantaran berkas dinas ke KPPN / penugasan pemeliharaan jaringan lantai 2..."
                        class="w-full text-xs rounded-xl border border-gray-200 p-3 focus:border-[#0058A8] focus:ring-1 focus:ring-[#0058A8] outline-none"
                    >{{ old('work_summary') }}</textarea>
                </div>

                <div>
                    <label for="proof_file" class="block text-xs font-bold text-gray-700 mb-1">
                        Unggah Bukti Pendukung (Surat Tugas / Foto Dokumentasi):
                    </label>
                    <input 
                        type="file" 
                        name="proof_file" 
                        id="proof_file" 
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-[#0058A8] hover:file:bg-blue-100"
                    />
                    <p class="text-[10px] text-gray-400 mt-1">
                        Maksimal ukuran berkas 2 MB (Format .pdf, .jpg, .png).
                    </p>
                </div>
            </div>

            <div class="pt-2">
                <button 
                    type="submit" 
                    class="w-full py-3 bg-[#0058A8] hover:bg-[#004787] text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center justify-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Kirim Pengajuan Koreksi</span>
                </button>
            </div>
        </form>
    </div>
</x-layouts.mobile>
