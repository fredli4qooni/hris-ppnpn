<x-layouts.mobile title="Ajukan Dispensasi Kahar">
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
                    <h2 class="text-sm font-bold text-gray-900">Formulir Dispensasi Kahar</h2>
                    <p class="text-[11px] text-gray-500">Permohonan Pembebasan Potongan TL (FR-303)</p>
                </div>
            </div>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm space-y-4">
            <form 
                action="{{ route('mobile.late-exceptions.store') }}" 
                method="POST" 
                enctype="multipart/form-data" 
                class="space-y-4"
                x-data="{
                    selectedCategory: '{{ old('category', 'ACCIDENT') }}',
                    selectedAttId: '{{ old('attendance_id', $selectedAttendanceId ?? '') }}',
                    incidentDate: '{{ old('incident_date', $maxDate) }}'
                }"
            >
                @csrf

                <!-- Selection of Late Attendance -->
                @if($recentLateAttendances->isNotEmpty())
                    <div class="space-y-1.5 p-3.5 bg-amber-50/60 border border-amber-200/80 rounded-2xl">
                        <label for="attendance_id" class="block text-xs font-bold text-amber-950">
                            Pilih Rekaman Keterlambatan Terdaftar:
                        </label>
                        <select 
                            name="attendance_id" 
                            id="attendance_id" 
                            x-model="selectedAttId"
                            @change="
                                const opt = $event.target.selectedOptions[0];
                                if (opt && opt.dataset.date) {
                                    incidentDate = opt.dataset.date;
                                }
                            "
                            class="w-full text-xs font-semibold text-gray-900 bg-white border border-amber-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                        >
                            <option value="">-- Masukkan Tanggal Manual di Bawah --</option>
                            @foreach($recentLateAttendances as $att)
                                <option 
                                    value="{{ $att->id }}" 
                                    data-date="{{ $att->date->toDateString() }}"
                                    {{ old('attendance_id', $selectedAttendanceId) == $att->id ? 'selected' : '' }}
                                >
                                    {{ $att->date->translatedFormat('d M Y') }} • Terlambat {{ $att->late_minutes }} Menit (Potongan {{ $att->deduction_percentage }}%)
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-amber-800">
                            Pilih salah satu jadwal di atas agar pengajuan otomatis terhubung dengan rekaman absensi Anda.
                        </p>
                    </div>
                @endif

                <!-- Incident Date Input -->
                <div class="space-y-1">
                    <label for="incident_date" class="block text-xs font-bold text-gray-700">
                        Tanggal Kejadian Kahar <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="incident_date" 
                        id="incident_date" 
                        x-model="incidentDate"
                        max="{{ $maxDate }}"
                        value="{{ old('incident_date', $maxDate) }}"
                        required
                        class="w-full text-xs font-medium text-gray-900 bg-gray-50 border border-gray-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-[#0058A8] focus:border-[#0058A8] @error('incident_date') border-rose-500 @enderror"
                    >
                    @error('incident_date')
                        <p class="text-[11px] text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Force Majeure Category -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700">
                        Kategori Kondisi Kahar (Force Majeure) <span class="text-rose-500">*</span>
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition" :class="selectedCategory === 'DISASTER' ? 'bg-amber-50/70 border-amber-300 text-amber-950 font-bold' : 'bg-gray-50 border-gray-200 text-gray-700'">
                            <input type="radio" name="category" value="DISASTER" x-model="selectedCategory" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-xs block leading-tight">Bencana Alam</span>
                                <span class="text-[10px] text-gray-500 font-normal block mt-0.5">Banjir, tanah longsor, angin kencang/pohon tumbang yang memutus akses jalan utama.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition" :class="selectedCategory === 'ACCIDENT' ? 'bg-amber-50/70 border-amber-300 text-amber-950 font-bold' : 'bg-gray-50 border-gray-200 text-gray-700'">
                            <input type="radio" name="category" value="ACCIDENT" x-model="selectedCategory" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-xs block leading-tight">Kecelakaan Lalu Lintas / Kendala Perjalanan Berat</span>
                                <span class="text-[10px] text-gray-500 font-normal block mt-0.5">Insiden kecelakaan lalu lintas atau mogok/kerusakan parah kendaraan saat berangkat dinas.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition" :class="selectedCategory === 'FAMILY_BEREAVEMENT' ? 'bg-amber-50/70 border-amber-300 text-amber-950 font-bold' : 'bg-gray-50 border-gray-200 text-gray-700'">
                            <input type="radio" name="category" value="FAMILY_BEREAVEMENT" x-model="selectedCategory" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-xs block leading-tight">Musibah Keluarga Inti</span>
                                <span class="text-[10px] text-gray-500 font-normal block mt-0.5">Orang tua/suami/istri/anak kandung wafat mendadak atau kondisi sakit kritis gawat darurat.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition" :class="selectedCategory === 'OTHER_FORCE_MAJEURE' ? 'bg-amber-50/70 border-amber-300 text-amber-950 font-bold' : 'bg-gray-50 border-gray-200 text-gray-700'">
                            <input type="radio" name="category" value="OTHER_FORCE_MAJEURE" x-model="selectedCategory" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-xs block leading-tight">Kondisi Kahar Lainnya</span>
                                <span class="text-[10px] text-gray-500 font-normal block mt-0.5">Kondisi luar biasa darurat lainnya yang dapat dipertanggungjawabkan keabsahannya.</span>
                            </div>
                        </label>
                    </div>
                    @error('category')
                        <p class="text-[11px] text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Incident Time Input -->
                <div class="space-y-1">
                    <label for="incident_time" class="block text-xs font-bold text-gray-700">
                        Waktu Kejadian (WIB)
                    </label>
                    <input 
                        type="time" 
                        name="incident_time" 
                        id="incident_time" 
                        value="{{ old('incident_time', '06:45') }}"
                        class="w-full text-xs font-mono font-medium text-gray-900 bg-gray-50 border border-gray-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-[#0058A8] focus:border-[#0058A8]"
                    >
                    <p class="text-[10px] text-gray-400">Jam terjadinya kendala kahar di perjalanan dinas.</p>
                </div>

                <!-- Detailed Description / Chronology -->
                <div class="space-y-1">
                    <label for="description" class="block text-xs font-bold text-gray-700">
                        Uraian Kronologis Kejadian Kahar <span class="text-rose-500">*</span>
                    </label>
                    <textarea 
                        name="description" 
                        id="description" 
                        rows="4" 
                        required
                        placeholder="Jelaskan secara runtut kronologi kejadian kahar yang menyebabkan keterlambatan tiba di kantor..."
                        class="w-full text-xs text-gray-900 bg-gray-50 border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-[#0058A8] focus:border-[#0058A8] @error('description') border-rose-500 @enderror"
                    >{{ old('description') }}</textarea>
                    <p class="text-[10px] text-gray-400">Minimal 15 karakter. Uraian akan ditelaah langsung oleh Supervisor Lapangan dan Kasubbag Umum.</p>
                    @error('description')
                        <p class="text-[11px] text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Proof Document Upload (Mandatory) -->
                <div class="space-y-1.5 p-3.5 bg-gray-50 rounded-2xl border border-gray-200">
                    <label for="proof_file" class="block text-xs font-bold text-gray-700">
                        Lampiran Bukti Resmi / Foto Dokumentasi <span class="text-rose-500">*</span>
                    </label>
                    <p class="text-[10px] text-gray-500 leading-relaxed">
                        Unggah surat kepolisian (bila kecelakaan), surat keterangan rumah sakit (musibah keluarga), atau foto dokumentasi situasi genangan/bencana di lokasi.
                    </p>
                    <input 
                        type="file" 
                        name="proof_file" 
                        id="proof_file" 
                        accept=".pdf,.jpg,.jpeg,.png"
                        required
                        class="w-full text-xs text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#0058A8] file:text-white hover:file:bg-[#004787] file:cursor-pointer"
                    >
                    <span class="text-[10px] text-gray-400 block">Format yang didukung: PDF, JPG, PNG (Maksimal 2 MB).</span>
                    @error('proof_file')
                        <p class="text-[11px] text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button 
                        type="submit" 
                        class="w-full py-3 px-4 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center justify-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        <span>Kirim Permohonan Dispensasi</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.mobile>
