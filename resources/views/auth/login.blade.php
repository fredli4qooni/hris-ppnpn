<x-layouts.auth title="Masuk ke Sistem">
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 sm:p-8">
        <!-- Logo & Header -->
        <div class="text-center mb-6">
            <div class="flex justify-center mb-3">
                <img src="{{ asset('images/logo-djpb.png') }}" alt="Logo DJPb" class="h-16 w-auto object-contain">
            </div>
            <h2 class="text-xl font-bold text-[#0B2A4A]">Masuk ke Akun Anda</h2>
            <p class="text-xs text-gray-500 mt-1">Portal Presensi Pegawai PPNPN Kanwil DJPb Lampung</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <!-- Username / Email Field -->
            <div>
                <label for="login" class="block text-sm font-semibold text-gray-700 mb-1">
                    Username / NIP / Email
                </label>
                <input 
                    type="text" 
                    id="login" 
                    name="login" 
                    value="{{ old('login') }}" 
                    required 
                    autofocus 
                    class="w-full px-4 py-2.5 rounded-lg border @error('login') border-red-500 @else border-gray-300 @enderror focus:ring-2 focus:ring-[#0058A8] focus:border-[#0058A8] text-sm transition outline-none"
                    placeholder="Masukkan username atau email"
                >
                @error('login')
                <p class="text-red-600 text-xs mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password Field with Clean Eye Toggle -->
            <div x-data="{ showPassword: false }">
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">
                    Kata Sandi
                </label>
                <div class="relative">
                    <input 
                        :type="showPassword ? 'text' : 'password'" 
                        id="password" 
                        name="password" 
                        required 
                        class="w-full px-4 py-2.5 pr-11 rounded-lg border @error('password') border-red-500 @else border-gray-300 @enderror focus:ring-2 focus:ring-[#0058A8] focus:border-[#0058A8] text-sm transition outline-none"
                        placeholder="Masukkan kata sandi"
                    >
                    <button 
                        type="button" 
                        @click="showPassword = !showPassword"
                        tabindex="-1"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition cursor-pointer"
                        title="Tampilkan / Sembunyikan Kata Sandi"
                    >
                        <!-- Eye Slash (Hidden state) -->
                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23" stroke-linecap="round" stroke-linejoin="round"></line>
                        </svg>
                        <!-- Open Eye (Visible state) -->
                        <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"></circle>
                        </svg>
                    </button>
                </div>
                @error('password')
                <p class="text-red-600 text-xs mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center space-x-2 text-gray-600 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded text-[#0058A8] focus:ring-[#0058A8]">
                    <span class="text-xs">Ingat Saya</span>
                </label>
            </div>

            <!-- Submit Button (Solid DJPb Blue) -->
            <button 
                type="submit" 
                class="w-full py-3 px-4 rounded-lg bg-[#0058A8] hover:bg-[#0B2A4A] text-white font-semibold text-sm shadow transition duration-150 flex items-center justify-center space-x-2 cursor-pointer"
            >
                <span>Masuk Sekarang</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
        </form>
    </div>
</x-layouts.auth>
