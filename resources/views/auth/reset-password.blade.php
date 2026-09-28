<x-layouts.auth title="Reset Password">

    <div class="w-full max-w-sm bg-white border border-stone-200/80 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.04),0_8px_24px_-12px_rgba(0,0,0,0.08)] p-6">
        <div class="text-center mb-5">
            <h1 class="text-xl font-bold text-gray-900">SIDIGAS</h1>
            <p class="text-xs text-gray-400 mt-0.5">Buat Password Baru</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-3.5">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="relative">
                <input type="password" name="password" id="password" required autofocus
                    class="w-full px-3.5 py-2.5 pr-10 border border-gray-200 rounded text-sm bg-gray-50 focus:outline-none focus:border-gray-900 focus:bg-white"
                    placeholder="Password baru">
                <button type="button" onclick="togPass('password',this)"
                    class="toggle-pass absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>

            <div class="relative">
                <input type="password" name="password_confirmation" id="password_confirmation" required
                    class="w-full px-3.5 py-2.5 pr-10 border border-gray-200 rounded text-sm bg-gray-50 focus:outline-none focus:border-gray-900 focus:bg-white"
                    placeholder="Konfirmasi password">
                <button type="button" onclick="togPass('password_confirmation',this)"
                    class="toggle-pass absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>

            @error('password')
                <p class="text-red-500 text-xs">{{ $message }}</p>
            @enderror

            <button type="submit"
                class="w-full bg-gray-900 text-white rounded text-sm font-semibold py-2.5 hover:bg-gray-800 transition inline-flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Simpan
            </button>
        </form>
    </div>

</x-layouts.auth>
