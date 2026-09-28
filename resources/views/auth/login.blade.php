<x-layouts.auth title="Login">

    <div class="w-full max-w-md bg-white border border-stone-200/80 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.04),0_8px_24px_-12px_rgba(0,0,0,0.08)] p-6 sm:p-8">
        <div class="text-center mb-5">
            <h1 class="text-xl font-bold text-gray-900">SIDIGAS</h1>
            <p class="text-xs text-gray-400 mt-0.5">Sistem Distribusi Gaji Sopir</p>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2 rounded mb-3">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-3.5">
            @csrf
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                class="w-full px-3.5 py-2.5 border border-gray-200 rounded text-sm bg-gray-50 focus:outline-none focus:border-gray-900 focus:bg-white"
                placeholder="Email">
            @error('email')
                <p class="text-red-500 text-xs">{{ $message }}</p>
            @enderror

            <div class="relative">
                <input type="password" name="password" id="password" required
                    class="w-full px-3.5 py-2.5 pr-10 border border-gray-200 rounded text-sm bg-gray-50 focus:outline-none focus:border-gray-900 focus:bg-white"
                    placeholder="Password">
                <button type="button" onclick="togPass('password',this)"
                    class="toggle-pass absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>

            <div class="flex justify-end text-xs">
                <a href="{{ route('password.request') }}" class="text-gray-700 hover:underline">Lupa password?</a>
            </div>

            <button type="submit"
                class="w-full bg-gray-900 text-white rounded text-sm font-semibold py-2.5 hover:bg-gray-800 transition inline-flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                Masuk
            </button>
        </form>

        <div class="flex items-center gap-3 my-4">
            <hr class="flex-1 border-gray-200">
            <span class="text-xs text-gray-400">atau</span>
            <hr class="flex-1 border-gray-200">
        </div>

        <a href="{{ route('google.login') }}"
            class="w-full flex items-center justify-center gap-1.5 border border-gray-200 rounded text-xs font-medium py-1.5 hover:bg-gray-50 transition">
            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google" class="w-3.5 h-3.5">
            Masuk dengan Google
        </a>

        <p class="text-[10px] text-gray-300 text-center mt-5">SIDIGAS &copy; 2026</p>
    </div>

</x-layouts.auth>
