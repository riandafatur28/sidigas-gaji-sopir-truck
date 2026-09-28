<x-layouts.auth title="Lupa Password">

    <div class="w-full max-w-sm bg-white border border-stone-200/80 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.04),0_8px_24px_-12px_rgba(0,0,0,0.08)] p-6">
        <div class="text-center mb-5">
            <h1 class="text-xl font-bold text-gray-900">SIDIGAS</h1>
            <p class="text-xs text-gray-400 mt-0.5">Lupa Password</p>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2 rounded mb-3">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-3.5">
            @csrf
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                class="w-full px-3.5 py-2.5 border border-gray-200 rounded text-sm bg-gray-50 focus:outline-none focus:border-gray-900 focus:bg-white"
                placeholder="Email">
            @error('email')
                <p class="text-red-500 text-xs">{{ $message }}</p>
            @enderror

            <button type="submit"
                class="w-full bg-gray-900 text-white rounded text-sm font-semibold py-2.5 hover:bg-gray-800 transition inline-flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Kirim OTP
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="text-xs text-gray-500 hover:underline">&larr; Kembali</a>
        </div>
    </div>

</x-layouts.auth>
