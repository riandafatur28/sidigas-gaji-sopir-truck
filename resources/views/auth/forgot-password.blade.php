{{-- ============================================================ --}}
{{-- FORGOT PASSWORD PAGE --}}
{{-- ============================================================ --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Sistem Armada</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a1a2e',
                        secondary: '#2d2d44',
                        accent: '#4a4a6a',
                        muted: '#6a6a8a',
                        surface: '#f8f8fa',
                    },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f0f0f2; }
        .card-login {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }
        .btn-login {
            background: #1a1a2e;
            color: #ffffff;
            padding: 14px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 15px;
            transition: background 0.2s ease;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        .btn-login:hover { background: #2d2d44; }
        .input-login {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            transition: border 0.2s ease;
            background: #f9fafb;
        }
        .input-login:focus {
            outline: none;
            border-color: #1a1a2e;
            background: #ffffff;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-5xl flex flex-col md:flex-row items-center gap-10">

        {{-- LEFT: BRANDING --}}
        <div class="hidden md:block w-1/2 text-center">
            <div class="flex justify-center mb-6">
                <svg class="w-20 h-20 text-[#1a1a2e] opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-[#1a1a2e]">Sistem Armada</h1>
            <p class="text-[#6a6a8a] text-lg mt-2">Manajemen Dump Truck</p>
        </div>

        {{-- RIGHT: FORM --}}
        <div class="w-full md:w-1/2 max-w-sm">
            <div class="card-login p-8">

                <div class="md:hidden text-center mb-6">
                    <h1 class="text-2xl font-bold text-[#1a1a2e]">Sistem Armada</h1>
                    <p class="text-[#6a6a8a] text-sm">Manajemen Dump Truck</p>
                </div>

                <div class="text-center mb-6">
                    <div class="w-12 h-12 bg-[#1a1a2e]/10 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-[#1a1a2e] opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-[#1a1a2e]">Lupa Password?</h2>
                    <p class="text-sm text-[#6a6a8a] mt-1">Masukkan email untuk menerima kode OTP</p>
                </div>

                <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-[#6a6a8a] uppercase tracking-wider mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            class="input-login" placeholder="nama@email.com">
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="btn-login">Kirim OTP</button>
                </form>

                <div class="text-center mt-4">
                    <a href="{{ route('login') }}" class="text-sm text-[#1a1a2e] hover:underline">← Kembali ke Login</a>
                </div>

                <p class="text-xs text-[#8a8aaa] text-center mt-6">© 2026 Sistem Armada</p>
            </div>
        </div>
    </div>

</body>
</html>
