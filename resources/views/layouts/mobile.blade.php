<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $title ?? 'Portal Karyawan' }} - HRIS Kanwil DJPb Provinsi Lampung</title>
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0058A8">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Presensi PPNPN">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-djpb.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <!-- FaceAPI Client Library via CDN with Local Support -->
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F4F6F9] text-gray-900 font-sans min-h-screen flex flex-col antialiased">
    <!-- PWA Offline Floating Banner (FR-208 / US-702) -->
    <x-offline-banner />

    <!-- Top Header -->
    <header class="sticky top-0 z-40 bg-gradient-to-r from-[#09223E] via-[#0B2A4A] to-[#0E3A68] text-white shadow-md overflow-hidden">
        <div class="max-w-md mx-auto px-4 pt-3 pb-4 flex items-center justify-between relative z-10">
            <div class="flex items-center space-x-2.5 min-w-0">
                <img src="{{ asset('images/logo-djpb.png') }}" alt="Logo DJPb" class="h-9 w-auto object-contain shrink-0">
                <div class="h-7 w-px bg-white/25 shrink-0"></div>
                <div class="min-w-0">
                    <h1 class="text-[11px] font-extrabold tracking-wider uppercase leading-tight truncate">Kanwil DJPb Lampung</h1>
                    <p class="text-[10px] text-blue-200 leading-tight truncate">Sistem Presensi PPNPN</p>
                </div>
            </div>

            @auth
            <div class="flex items-center gap-2 shrink-0">
                <!-- In-App Notification Bell (FR-701 / US-601) -->
                <a href="{{ route('mobile.notifications.index') }}" class="relative p-1.5 rounded-xl text-blue-100 hover:text-white hover:bg-white/10 transition" title="Pusat Notifikasi">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    @php
                        $unreadNotificationsCount = Auth::user()->unreadNotifications()->count();
                    @endphp
                    @if($unreadNotificationsCount > 0)
                        <span class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-black text-white shadow-sm ring-2 ring-[#0B2A4A] animate-pulse">
                            {{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}
                        </span>
                    @endif
                </a>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-[11px] bg-white/10 hover:bg-red-600/90 border border-white/15 px-2.5 py-1.5 rounded-xl text-white font-semibold transition flex items-center gap-1 cursor-pointer" title="Keluar">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
            @endauth
        </div>
        <!-- Curved Gold Bottom Accent -->
        <div class="h-1.5 w-full bg-gradient-to-r from-[#0B2A4A] via-[#F8B810] to-[#F8B810]"></div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow max-w-md w-full mx-auto px-4 py-5 pb-24">
        @if(session('success'))
        <div class="mb-4 p-3.5 rounded-xl bg-[#E0F5EA] border border-[#00A050] text-[#007A3D] text-xs flex items-center space-x-2 shadow-sm">
            <svg class="w-4 h-4 shrink-0 text-[#007A3D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <div>{{ session('success') }}</div>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-4 p-3.5 rounded-xl bg-[#FDECEA] border border-[#C62828] text-[#C62828] text-xs flex items-center space-x-2 shadow-sm">
            <svg class="w-4 h-4 shrink-0 text-[#C62828]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            <div>{{ session('error') }}</div>
        </div>
        @endif

        {{ $slot }}
    </main>

    <!-- Bottom Navigation Bar (Mobile App Style) -->
    <nav class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 shadow-lg">
        <div class="max-w-md mx-auto grid grid-cols-5 py-2 px-1 text-center">
            <!-- Beranda -->
            <a href="{{ route('mobile.home') }}" class="flex flex-col items-center py-1 transition {{ request()->routeIs('mobile.home') ? 'text-[#0058A8] font-bold' : 'text-gray-500 hover:text-[#0058A8]' }}">
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span class="text-[10px]">Beranda</span>
            </a>

            <!-- Presensi (Sprint 3 Active) -->
            <a href="{{ route('mobile.attendance') }}" class="flex flex-col items-center py-1 transition {{ request()->routeIs('mobile.attendance') ? 'text-[#0058A8] font-bold' : 'text-gray-500 hover:text-[#0058A8]' }}">
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
                <span class="text-[10px]">Presensi</span>
            </a>

            <!-- Izin / Cuti (FR-501 / US-403) -->
            <a href="{{ route('mobile.leaves.index') }}" class="flex flex-col items-center py-1 transition {{ request()->routeIs('mobile.leaves*') ? 'text-[#0058A8] font-bold' : 'text-gray-500 hover:text-[#0058A8]' }}">
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span class="text-[10px]">Cuti/Izin</span>
            </a>

            <!-- Riwayat (FR-211 / US-306) -->
            <a href="{{ route('mobile.attendance.history') }}" class="flex flex-col items-center py-1 transition {{ request()->routeIs('mobile.attendance.history*') ? 'text-[#0058A8] font-bold' : 'text-gray-500 hover:text-[#0058A8]' }}">
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="text-[10px]">Riwayat</span>
            </a>

            <!-- Profil (US-206) -->
            <a href="{{ route('mobile.profile') }}" class="flex flex-col items-center py-1 transition {{ request()->routeIs('mobile.profile') ? 'text-[#0058A8] font-bold' : 'text-gray-500 hover:text-[#0058A8]' }}">
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span class="text-[10px]">Profil</span>
            </a>
        </div>
    </nav>
    <!-- PWA Service Worker Registration (US-702) -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/service-worker.js')
                    .then(function(reg) {
                        console.log('HRIS DJPb PWA Service Worker terdaftar:', reg.scope);
                    })
                    .catch(function(err) {
                        console.warn('Registrasi PWA Service Worker gagal:', err);
                    });
            });
        }
    </script>
</body>
</html>
