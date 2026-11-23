<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kendala Sistem Sementara - HRIS Kanwil DJPb Lampung</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-[#F4F6F9] text-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white border border-gray-200 rounded-3xl p-6 sm:p-8 shadow-sm text-center space-y-4">
        
        <!-- Danger / Alert Icon -->
        <div class="w-16 h-16 mx-auto rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>

        <div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 uppercase tracking-wide">
                HTTP 500 &bull; Internal Server Error
            </span>
            <h1 class="text-lg font-extrabold text-gray-900 mt-2">Mohon Maaf, Terjadi Kendala Sistem</h1>
            <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                Sistem mendeteksi kendala operasional yang telah direkam secara otomatis ke log pemantauan IT Kanwil DJPb Lampung.
            </p>
        </div>

        <!-- Incident Reference Ticket Box (US-803) -->
        @php
            $ticketCode = $incidentCode ?? 'ERR-DJPB-' . strtoupper(substr(md5(microtime()), 0, 8));
        @endphp
        <div class="bg-gray-50 border border-gray-200 rounded-2xl p-3.5 space-y-1.5 text-left">
            <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider block text-center">
                Kode Tiket Rujukan Insiden (Incident Ticket)
            </span>
            <div class="flex items-center justify-between gap-2 bg-white px-3 py-2 rounded-xl border border-gray-200">
                <span class="font-mono font-bold text-xs text-rose-700 tracking-wider" id="ticketCodeSpan">{{ $ticketCode }}</span>
                <button 
                    type="button" 
                    onclick="navigator.clipboard.writeText('{{ $ticketCode }}'); alert('Kode tiket berhasil disalin!');" 
                    class="px-2 py-1 text-[10px] font-bold bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition"
                >
                    Salin
                </button>
            </div>
            <p class="text-[9.5px] text-gray-400 text-center leading-tight">
                Sampaikan kode tiket di atas kepada Administrator Kepegawaian Kanwil DJPb untuk penelusuran lebih lanjut.
            </p>
        </div>

        <div class="pt-2 flex flex-col gap-2">
            <a href="/login" class="inline-flex items-center justify-center w-full py-2.5 px-4 bg-[#0058A8] hover:bg-[#0B2A4A] text-white text-xs font-bold rounded-xl shadow-xs transition">
                Kembali ke Beranda
            </a>
            <button 
                type="button" 
                onclick="window.location.reload()" 
                class="inline-flex items-center justify-center w-full py-2 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-xl transition"
            >
                Muat Ulang Halaman
            </button>
        </div>
    </div>
</body>
</html>
