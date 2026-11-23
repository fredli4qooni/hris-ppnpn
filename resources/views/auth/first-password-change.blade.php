<x-layouts.auth title="Wajib Ganti Kata Sandi">
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 sm:p-8" x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
        <div class="text-center mb-6">
            <div class="w-12 h-12 bg-amber-50 text-[#8A6100] border border-amber-200 rounded-full mx-auto flex items-center justify-center mb-2">
                <svg class="w-6 h-6 text-[#8A6100]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>
            <h2 class="text-xl font-bold text-[#0B2A4A]">Ganti Kata Sandi Perdana</h2>
            <p class="text-xs text-gray-500 mt-1">
                Sesuai kebijakan keamanan, Anda wajib mengganti kata sandi sementara saat pertama kali masuk ke sistem.
            </p>
        </div>

        <form method="POST" action="{{ route('password.first-change.update') }}" class="space-y-4">
            @csrf

            <!-- Current Password -->
            <div>
                <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-1">
                    Kata Sandi Lama / Sementara
                </label>
                <div class="relative">
                    <input 
                        :type="showCurrent ? 'text' : 'password'" 
                        id="current_password" 
                        name="current_password" 
                        required 
                        class="w-full px-4 py-2.5 pr-11 rounded-lg border @error('current_password') border-red-500 @else border-gray-300 @enderror focus:ring-2 focus:ring-[#0058A8] focus:border-[#0058A8] text-sm transition outline-none"
                        placeholder="Masukkan kata sandi saat ini"
                    >
                    <button 
                        type="button" 
                        @click="showCurrent = !showCurrent"
                        tabindex="-1"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition cursor-pointer"
                        title="Tampilkan / Sembunyikan"
                    >
                        <svg x-show="!showCurrent" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23" stroke-linecap="round" stroke-linejoin="round"></line>
                        </svg>
                        <svg x-show="showCurrent" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"></circle>
                        </svg>
                    </button>
                </div>
                @error('current_password')
                <p class="text-red-600 text-xs mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- New Password -->
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">
                    Kata Sandi Baru
                </label>
                <div class="relative">
                    <input 
                        :type="showNew ? 'text' : 'password'" 
                        id="password" 
                        name="password" 
                        required 
                        class="w-full px-4 py-2.5 pr-11 rounded-lg border @error('password') border-red-500 @else border-gray-300 @enderror focus:ring-2 focus:ring-[#0058A8] focus:border-[#0058A8] text-sm transition outline-none"
                        placeholder="Minimal 8 karakter (huruf & angka)"
                    >
                    <button 
                        type="button" 
                        @click="showNew = !showNew"
                        tabindex="-1"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition cursor-pointer"
                        title="Tampilkan / Sembunyikan"
                    >
                        <svg x-show="!showNew" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23" stroke-linecap="round" stroke-linejoin="round"></line>
                        </svg>
                        <svg x-show="showNew" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"></circle>
                        </svg>
                    </button>
                </div>
                @error('password')
                <p class="text-red-600 text-xs mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm New Password -->
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1">
                    Konfirmasi Kata Sandi Baru
                </label>
                <div class="relative">
                    <input 
                        :type="showConfirm ? 'text' : 'password'" 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        required 
                        class="w-full px-4 py-2.5 pr-11 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#0058A8] focus:border-[#0058A8] text-sm transition outline-none"
                        placeholder="Ketik ulang kata sandi baru"
                    >
                    <button 
                        type="button" 
                        @click="showConfirm = !showConfirm"
                        tabindex="-1"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition cursor-pointer"
                        title="Tampilkan / Sembunyikan"
                    >
                        <svg x-show="!showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23" stroke-linecap="round" stroke-linejoin="round"></line>
                        </svg>
                        <svg x-show="showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Criteria Checklist -->
            <div class="bg-gray-50 p-3 rounded-lg text-xs text-gray-600 space-y-1 border border-gray-100">
                <p class="font-semibold text-gray-700">Ketentuan Kata Sandi:</p>
                <ul class="list-disc list-inside space-y-0.5 text-gray-500">
                    <li>Panjang minimal 8 karakter</li>
                    <li>Mengandung kombinasi huruf dan angka</li>
                    <li>Berbeda dari kata sandi sementara</li>
                </ul>
            </div>

            <!-- Submit Button (Solid DJPb Blue) -->
            <button 
                type="submit" 
                class="w-full py-3 px-4 rounded-lg bg-[#0058A8] hover:bg-[#0B2A4A] text-white font-semibold text-sm shadow transition duration-150 flex items-center justify-center space-x-2 mt-4 cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>Simpan Kata Sandi & Lanjutkan</span>
            </button>
        </form>
    </div>
</x-layouts.auth>
