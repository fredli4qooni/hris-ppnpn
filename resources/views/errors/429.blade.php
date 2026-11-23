<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batas Percobaan Terlampaui - HRIS Kanwil DJPb Lampung</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-[#F4F6F9] text-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white border border-gray-200 rounded-3xl p-6 sm:p-8 shadow-sm text-center space-y-4">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>

        <div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                HTTP 429 &bull; Rate Limit Exceeded
            </span>
            <h1 class="text-lg font-extrabold text-gray-900 mt-2">Batas Permintaan Terlampaui</h1>
            <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                {{ $message ?? 'Sistem mendeteksi frekuensi pengiriman request melebihi ambang batas keamanan CSIRT Kemenkeu. Silakan tunggu 1 menit sebelum mencoba kembali.' }}
            </p>
        </div>

        <div class="pt-3">
            <a href="/login" class="inline-flex items-center justify-center w-full py-2.5 px-4 bg-[#0058A8] hover:bg-[#0B2A4A] text-white text-xs font-bold rounded-xl shadow-xs transition">
                Kembali ke Halaman Masuk
            </a>
        </div>
    </div>
</body>
</html>
