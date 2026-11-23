<div 
    x-data="{ 
        isOnline: navigator.onLine, 
        showReconnected: false 
    }"
    @online.window="isOnline = true; showReconnected = true; setTimeout(() => showReconnected = false, 4000)"
    @offline.window="isOnline = false"
    class="fixed top-0 inset-x-0 z-50 pointer-events-none select-none"
>
    <!-- Offline Alert Banner (Floating Top) -->
    <div 
        x-show="!isOnline" 
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="-translate-y-full opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="-translate-y-full opacity-0"
        class="bg-gradient-to-r from-amber-600 via-rose-600 to-amber-700 text-white px-4 py-2.5 shadow-lg border-b border-rose-300/30 pointer-events-auto"
    >
        <div class="max-w-md mx-auto flex items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="flex h-3 w-3 shrink-0 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-200 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                </span>
                <div class="leading-snug min-w-0">
                    <span class="font-extrabold uppercase tracking-wide text-[10px] block text-rose-100">Koneksi Internet Terputus</span>
                    <span class="text-[11px] truncate block">Tombol presensi dinonaktifkan sementara hingga sinyal pulih.</span>
                </div>
            </div>
            <button 
                type="button" 
                onclick="window.location.reload()" 
                class="shrink-0 px-2.5 py-1 bg-white/20 hover:bg-white/30 text-white font-bold text-[10px] rounded-lg transition"
            >
                Cek Ulang
            </button>
        </div>
    </div>

    <!-- Reconnected Success Banner (Temporary Flash) -->
    <div 
        x-show="showReconnected && isOnline" 
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="-translate-y-full opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="-translate-y-full opacity-0"
        class="bg-gradient-to-r from-emerald-600 to-teal-700 text-white px-4 py-2.5 shadow-lg border-b border-emerald-300/30 pointer-events-auto"
    >
        <div class="max-w-md mx-auto flex items-center gap-2.5 text-xs">
            <div class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <div class="leading-snug">
                <span class="font-extrabold uppercase tracking-wide text-[10px] block text-emerald-100">Koneksi Internet Pulih</span>
                <span class="text-[11px] block">Anda siap melanjutkan proses verifikasi presensi.</span>
            </div>
        </div>
    </div>
</div>
