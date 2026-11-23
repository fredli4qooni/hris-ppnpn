<x-filament-widgets::widget>
    <div 
        class="rounded-3xl p-6 shadow-sm border border-blue-900/10 relative overflow-hidden text-white"
        style="background: linear-gradient(135deg, #0B2A4A 0%, #0058A8 50%, #0B2A4A 100%); color: #ffffff;"
    >
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="space-y-2 max-w-2xl">
                <div class="flex items-center gap-2">
                    <span 
                        class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-xs"
                        style="background-color: #F8B810; color: #0B2A4A;"
                    >
                        Kanwil DJPb Provinsi Lampung
                    </span>
                    <span class="text-xs text-blue-200 font-medium">
                        {{ $this->getCurrentDate() }}
                    </span>
                </div>

                <div>
                    <h2 class="text-xl font-extrabold tracking-tight text-white">
                        Selamat Datang, {{ $this->getUserName() }}
                    </h2>
                    <p class="text-xs text-blue-100 font-medium mt-0.5">
                        Peran Aktif: <span class="text-[#F8B810] font-bold">{{ $this->getUserRoleLabel() }}</span>
                    </p>
                </div>

                <p class="text-[11px] leading-relaxed text-blue-100/90">
                    Sistem Tata Kelola Kehadiran, Kedisiplinan SPK, & Penggajian Terpadu Pegawai Pemerintah Non Pegawai Negeri (PPNPN).
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <a 
                    href="{{ route('filament.admin.resources.employees.index') }}" 
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold transition shadow-xs hover:opacity-90 cursor-pointer"
                    style="background-color: #ffffff; color: #0058A8;"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span>Data Pegawai</span>
                </a>

                <a 
                    href="{{ route('filament.admin.pages.supervisor-command-center') }}" 
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-white transition border hover:bg-white/10 cursor-pointer"
                    style="border-color: rgba(255, 255, 255, 0.35); background-color: rgba(255, 255, 255, 0.1);"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    <span>Pusat Komando</span>
                </a>
            </div>
        </div>

        <!-- Background Decorative Motif -->
        <div class="absolute -right-12 -bottom-12 w-48 h-48 rounded-full pointer-events-none" style="background-color: rgba(255, 255, 255, 0.05);"></div>
    </div>
</x-filament-widgets::widget>
