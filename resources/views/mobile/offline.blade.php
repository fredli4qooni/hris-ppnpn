<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Koneksi Terputus - HRIS PPNPN Kanwil DJPb Lampung</title>
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0058A8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#F4F6F9] text-gray-900 min-h-screen flex flex-col justify-between p-4 antialiased">

    <!-- Header Brand -->
    <header class="py-3 border-b border-gray-200 flex items-center justify-center space-x-2.5">
        <img src="{{ asset('images/logo-djpb.png') }}" alt="Logo DJPb" class="h-8 w-auto">
        <div>
            <h1 class="text-xs font-bold text-[#0B2A4A] uppercase tracking-wider">Kanwil DJPb Provinsi Lampung</h1>
            <p class="text-[10px] text-gray-500">Sistem Presensi PPNPN</p>
        </div>
    </header>

    <!-- Main Offline Card -->
    <main class="my-auto max-w-sm mx-auto w-full text-center py-6 space-y-4">
        <!-- Visual Wifi Offline Graphic -->
        <div class="relative w-20 h-20 mx-auto rounded-3xl bg-amber-100/80 text-amber-800 flex items-center justify-center border border-amber-200 shadow-sm">
            <svg class="w-10 h-10 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 4.243a1 1 0 010-1.414m0 0a1 1 0 011.414 0M3 3l18 18"></path>
            </svg>
            <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-rose-500 ring-4 ring-white animate-pulse"></span>
        </div>

        <div class="space-y-1">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10.5px] font-bold bg-amber-100 text-amber-900 border border-amber-200">
                <span class="w-2 h-2 rounded-full bg-amber-600 animate-ping"></span>
                <span>Mode Offline Aktif</span>
            </span>
            <h2 class="text-lg font-extrabold text-gray-900 pt-1">Koneksi Internet Terputus</h2>
            <p class="text-xs text-gray-600 leading-relaxed max-w-[280px] mx-auto">
                Perangkat Anda sedang tidak dapat menghubungi server. Halaman ini disajikan oleh modul ketahanan offline PWA Kanwil DJPb.
            </p>
        </div>

        <!-- Information Box for PPNPN -->
        <div class="bg-white border border-gray-200 rounded-2xl p-4 text-left text-xs space-y-2.5 shadow-xs">
            <h3 class="font-bold text-gray-800 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
                <svg class="w-4 h-4 text-[#0058A8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Petunjuk Operasional Lapangan:</span>
            </h3>
            <ul class="space-y-1.5 text-gray-600 text-[11px] leading-relaxed list-disc list-inside">
                <li>Pastikan paket data seluler aktif atau sambungkan ke Wi-Fi kantor Kanwil DJPb.</li>
                <li>Rekaman presensi yang telah berhasil tersimpan di server tetap aman dan tidak akan hilang.</li>
                <li>Jika sinyal di lokasi pos satpam/gedung terputus sementara, tunggu sinyal kembali stabil sebelum menekan tombol verifikasi.</li>
            </ul>
        </div>

        <!-- Actions -->
        <div class="pt-2 space-y-2">
            <button 
                onclick="window.location.reload()" 
                class="w-full py-3 px-4 bg-[#0058A8] hover:bg-[#0B2A4A] text-white text-xs font-bold rounded-xl shadow-md transition flex items-center justify-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span>Coba Hubungkan Kembali</span>
            </button>
            <a 
                href="/mobile/home" 
                class="block w-full py-2.5 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition text-center">
                Kembali ke Beranda Mobile
            </a>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-3 text-center text-[10px] text-gray-400">
        &copy; 2026 Bagian Umum Kanwil DJPb Provinsi Lampung &bull; PWA Resilience FR-208
    </footer>

</body>
</html>
