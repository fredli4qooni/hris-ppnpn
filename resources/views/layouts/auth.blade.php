<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'HRIS PPNPN' }} - Kanwil DJPb Provinsi Lampung</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F4F6F9] text-gray-900 font-sans min-h-screen flex flex-col justify-between">
    <!-- Header -->
    <header class="bg-[#0B2A4A] text-white py-3.5 px-6 shadow-md border-b-4 border-[#F8B810]">
        <div class="max-w-md mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="{{ asset('images/logo-djpb.png') }}" alt="Logo DJPb" class="h-10 w-auto object-contain">
                <div>
                    <h1 class="text-sm font-bold tracking-wide uppercase leading-tight">Kanwil DJPb Lampung</h1>
                    <p class="text-[11px] text-blue-200">Sistem Presensi Pegawai PPNPN</p>
                </div>
            </div>
            @auth
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-xs bg-red-600 hover:bg-red-700 px-3 py-1.5 rounded text-white font-medium transition flex items-center gap-1 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span>Keluar</span>
                </button>
            </form>
            @endauth
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-grow flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            @if(session('success'))
            <div class="mb-4 p-4 rounded-lg bg-[#E0F5EA] border border-[#00A050] text-[#007A3D] text-sm flex items-start space-x-2">
                <svg class="w-5 h-5 shrink-0 text-[#007A3D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <div>{{ session('success') }}</div>
            </div>
            @endif

            @if(session('warning'))
            <div class="mb-4 p-4 rounded-lg bg-[#FEF3D0] border border-[#F8B810] text-[#8A6100] text-sm flex items-start space-x-2">
                <svg class="w-5 h-5 shrink-0 text-[#8A6100]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div>{{ session('warning') }}</div>
            </div>
            @endif

            @if(session('error'))
            <div class="mb-4 p-4 rounded-lg bg-[#FDECEA] border border-[#C62828] text-[#C62828] text-sm flex items-start space-x-2">
                <svg class="w-5 h-5 shrink-0 text-[#C62828]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                <div>{{ session('error') }}</div>
            </div>
            @endif

            {{ $slot }}
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-4 text-center text-xs text-gray-500 border-t border-gray-200">
        &copy; {{ date('Y') }} Kanwil DJPb Provinsi Lampung &bull; Kementerian Keuangan RI
    </footer>
</body>
</html>
