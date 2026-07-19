<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIDIGAS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f0f0f2; }
        .toggle-pass { cursor: pointer; user-select: none; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-sm bg-white border border-gray-200 rounded p-6">
        <div class="text-center mb-5">
            <h1 class="text-xl font-bold text-gray-900">SIDIGAS</h1>
            <p class="text-xs text-gray-400 mt-0.5">Sistem Distribusi Gaji Sopir</p>
        </div>

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
                class="w-full bg-gray-900 text-white rounded text-sm font-semibold py-2.5 hover:bg-gray-800 transition">
                Masuk
            </button>
        </form>

        {{-- Separator --}}
        <div class="flex items-center gap-3 my-4">
            <hr class="flex-1 border-gray-200">
            <span class="text-xs text-gray-400">atau</span>
            <hr class="flex-1 border-gray-200">
        </div>

        {{-- Google Login --}}
        <a href="{{ route('google.login') }}"
            class="w-full flex items-center justify-center gap-1.5 border border-gray-200 rounded text-xs font-medium py-1.5 hover:bg-gray-50 transition">
            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google" class="w-3.5 h-3.5">
            Masuk dengan Google
        </a>

        <p class="text-[10px] text-gray-300 text-center mt-5">SIDIGAS &copy; 2026</p>
    </div>

    <script>
        function togPass(id, btn) {
            const inp = document.getElementById(id);
            const isPass = inp.type === 'password';
            inp.type = isPass ? 'text' : 'password';
            btn.innerHTML = isPass
                ? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>'
                : '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
        }
    </script>
</body>
</html>
