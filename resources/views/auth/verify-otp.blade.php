<x-layouts.auth title="Verifikasi OTP">

    <div class="w-full max-w-sm bg-white border border-stone-200/80 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.04),0_8px_24px_-12px_rgba(0,0,0,0.08)] p-6">
        <div class="text-center mb-5">
            <h1 class="text-xl font-bold text-gray-900">SIDIGAS</h1>
            <p class="text-xs text-gray-400 mt-0.5">Verifikasi OTP</p>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2 rounded mb-3">{{ session('success') }}</div>
        @endif

        <p class="text-xs text-gray-500 text-center mb-4">Kode dikirim ke <strong class="text-gray-800">{{ $email }}</strong></p>

        <form method="POST" action="{{ route('verify.otp') }}" class="space-y-3.5">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">

            <input type="text" name="otp" maxlength="6" required autofocus
                class="w-full text-center text-2xl font-bold tracking-[8px] px-3.5 py-2.5 border border-gray-200 rounded bg-gray-50 focus:outline-none focus:border-gray-900 focus:bg-white"
                placeholder="000000"
                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            @error('otp')
                <p class="text-red-500 text-xs text-center">{{ $message }}</p>
            @enderror

            <button type="submit"
                class="w-full bg-gray-900 text-white rounded text-sm font-semibold py-2.5 hover:bg-gray-800 transition inline-flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Verifikasi
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="text-xs text-gray-500 hover:underline">&larr; Kembali</a>
        </div>
    </div>

</x-layouts.auth>
