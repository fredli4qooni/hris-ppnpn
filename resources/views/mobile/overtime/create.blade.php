<x-layouts.mobile title="Ajukan Lembur Tanpa Surat">
    <div class="space-y-4">
        <div class="flex items-center gap-2">
            <a href="{{ route('mobile.overtime.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#0058A8] hover:underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                <span>Kembali ke Daftar Lembur</span>
            </a>
        </div>

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 rounded-2xl p-3.5 text-xs text-rose-800 space-y-1">
                <p class="font-bold">Pengajuan belum dapat disimpan:</p>
                <ul class="list-disc pl-4 space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('mobile.overtime.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm space-y-4">
            @csrf

            <div>
                <h2 class="text-sm font-bold text-gray-900">Pengajuan Lembur Tanpa Surat</h2>
                <p class="text-[11px] text-gray-500 mt-0.5">
                    Batas pengajuan paling lambat Hari-H sebelum jam mulai lembur. Tidak berlaku tanggal mundur.
                </p>
            </div>

            <!-- Impor PDF Box -->
            <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-3.5 space-y-2.5">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-xs font-bold text-blue-950">Impor Dokumen PDF (Opsional)</p>
                        <p class="text-[11px] text-blue-800 leading-relaxed">
                            Unggah PDF berisi tanggal, jam lembur, dan keterangan untuk mengisi kolom secara otomatis.
                        </p>
                    </div>
                </div>

                <input
                    id="attachment_pdf"
                    name="attachment_pdf"
                    type="file"
                    accept="application/pdf,.pdf"
                    class="block w-full text-xs text-gray-700 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#0058A8] file:text-white hover:file:bg-blue-800"
                >

                <div class="flex items-center justify-between gap-2">
                    <button
                        type="button"
                        id="btn_parse_pdf"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-blue-300 text-[#0058A8] text-xs font-bold hover:bg-blue-50 transition"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <span>Baca Isi PDF</span>
                    </button>
                    <span id="pdf_parse_status" class="text-[11px] text-blue-700 font-medium"></span>
                </div>
            </div>

            <!-- Tanggal Lembur -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="start_date" class="block text-xs font-bold text-gray-700 mb-1">Tanggal Mulai <span class="text-rose-500">*</span></label>
                    <input
                        id="start_date"
                        name="start_date"
                        type="date"
                        min="{{ $todayDate }}"
                        value="{{ old('start_date', $todayDate) }}"
                        required
                        class="w-full rounded-xl border-gray-200 text-xs focus:border-[#0058A8] focus:ring-[#0058A8]"
                    >
                </div>
                <div>
                    <label for="end_date" class="block text-xs font-bold text-gray-700 mb-1">Tanggal Selesai</label>
                    <input
                        id="end_date"
                        name="end_date"
                        type="date"
                        min="{{ $todayDate }}"
                        value="{{ old('end_date', old('start_date', $todayDate)) }}"
                        class="w-full rounded-xl border-gray-200 text-xs focus:border-[#0058A8] focus:ring-[#0058A8]"
                    >
                    <p class="text-[10px] text-gray-400 mt-0.5">Samakan jika lembur 1 hari.</p>
                </div>
            </div>

            <!-- Jam Lembur -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="start_time" class="block text-xs font-bold text-gray-700 mb-1">Jam Mulai (WIB) <span class="text-rose-500">*</span></label>
                    <input
                        id="start_time"
                        name="start_time"
                        type="time"
                        value="{{ old('start_time', '17:00') }}"
                        required
                        class="w-full rounded-xl border-gray-200 text-xs focus:border-[#0058A8] focus:ring-[#0058A8]"
                    >
                </div>
                <div>
                    <label for="end_time" class="block text-xs font-bold text-gray-700 mb-1">Jam Selesai (WIB) <span class="text-rose-500">*</span></label>
                    <input
                        id="end_time"
                        name="end_time"
                        type="time"
                        value="{{ old('end_time', '19:30') }}"
                        required
                        class="w-full rounded-xl border-gray-200 text-xs focus:border-[#0058A8] focus:ring-[#0058A8]"
                    >
                </div>
            </div>

            <div class="bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-600">Estimasi Durasi Per Hari</span>
                <span id="duration_preview" class="text-xs font-extrabold text-[#0058A8]">2 Jam 30 Menit</span>
            </div>

            <!-- Uraian -->
            <div>
                <label for="description" class="block text-xs font-bold text-gray-700 mb-1">Uraian Kegiatan Lembur <span class="text-rose-500">*</span></label>
                <textarea
                    id="description"
                    name="description"
                    rows="3"
                    required
                    minlength="5"
                    maxlength="500"
                    placeholder="Contoh: Penyelesaian rekonsiliasi laporan keuangan dan penataan berkas."
                    class="w-full rounded-xl border-gray-200 text-xs focus:border-[#0058A8] focus:ring-[#0058A8]"
                >{{ old('description') }}</textarea>
            </div>

            <div class="pt-1 flex items-center gap-2">
                <button
                    type="submit"
                    class="w-full py-2.5 px-4 bg-[#0058A8] text-white rounded-xl text-xs font-bold hover:bg-blue-800 transition shadow-xs"
                >
                    Kirim Pengajuan Lembur
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const startDateInput = document.getElementById('start_date');
                const endDateInput = document.getElementById('end_date');
                const startTimeInput = document.getElementById('start_time');
                const endTimeInput = document.getElementById('end_time');
                const descriptionInput = document.getElementById('description');
                const durationPreview = document.getElementById('duration_preview');
                const pdfInput = document.getElementById('attachment_pdf');
                const parseBtn = document.getElementById('btn_parse_pdf');
                const parseStatus = document.getElementById('pdf_parse_status');

                function updateDurationPreview() {
                    const start = startTimeInput.value;
                    const end = endTimeInput.value;
                    if (!start || !end) {
                        durationPreview.textContent = '-';
                        return;
                    }
                    const [sh, sm] = start.split(':').map(Number);
                    const [eh, em] = end.split(':').map(Number);
                    const startMinutes = (sh * 60) + sm;
                    const endMinutes = (eh * 60) + em;
                    const diff = endMinutes - startMinutes;

                    if (diff <= 0) {
                        durationPreview.textContent = 'Jam selesai harus lebih besar dari jam mulai';
                        return;
                    }

                    const hours = Math.floor(diff / 60);
                    const mins = diff % 60;
                    durationPreview.textContent = mins === 0 ? `${hours} Jam` : `${hours} Jam ${mins} Menit`;
                }

                startDateInput.addEventListener('change', () => {
                    if (endDateInput.value < startDateInput.value) {
                        endDateInput.value = startDateInput.value;
                    }
                    endDateInput.min = startDateInput.value;
                });

                startTimeInput.addEventListener('change', updateDurationPreview);
                endTimeInput.addEventListener('change', updateDurationPreview);
                updateDurationPreview();

                async function parseSelectedPdf() {
                    const file = pdfInput.files && pdfInput.files[0];
                    if (!file) {
                        parseStatus.textContent = 'Pilih file PDF dahulu.';
                        return;
                    }

                    parseStatus.textContent = 'Membaca PDF...';
                    parseBtn.disabled = true;

                    const formData = new FormData();
                    formData.append('pdf_file', file);

                    try {
                        const response = await fetch('{{ route('mobile.overtime.parse-pdf') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        const result = await response.json();
                        if (!response.ok || !result.success) {
                            parseStatus.textContent = result.message || 'Gagal membaca PDF.';
                            return;
                        }

                        const data = result.data || {};
                        if (data.start_date) {
                            startDateInput.value = data.start_date;
                            endDateInput.value = data.end_date || data.start_date;
                        }
                        if (data.start_time) {
                            startTimeInput.value = data.start_time;
                        }
                        if (data.end_time) {
                            endTimeInput.value = data.end_time;
                        }
                        if (data.description) {
                            descriptionInput.value = data.description;
                        }
                        updateDurationPreview();

                        parseStatus.textContent = data.has_extractable_text
                            ? 'Data PDF berhasil dibaca. Silakan periksa kembali.'
                            : 'PDF berupa hasil scan/gambar, silakan isi kolom secara manual.';
                    } catch (err) {
                        parseStatus.textContent = 'Terjadi kendala saat memproses PDF.';
                    } finally {
                        parseBtn.disabled = false;
                    }
                }

                parseBtn.addEventListener('click', parseSelectedPdf);
                pdfInput.addEventListener('change', parseSelectedPdf);
            });
        </script>
    @endpush
</x-layouts.mobile>
