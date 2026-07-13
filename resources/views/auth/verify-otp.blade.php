{{-- ============================================================ --}}
{{-- VERIFY OTP PAGE --}}
{{-- ============================================================ --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP - Sistem Armada</title>
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
        .input-otp {
            width: 100%;
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 8px;
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            transition: border 0.2s ease;
            background: #f9fafb;
            color: #1a1a2e;
        }
        .input-otp:focus {
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

                @if(session('success'))
                    <div class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="text-center mb-6">
                    <div class="w-12 h-12 bg-[#1a1a2e]/10 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-[#1a1a2e] opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-[#1a1a2e]">Verifikasi OTP</h2>
                    <p class="text-sm text-[#6a6a8a] mt-1">
                        Masukkan 6 digit kode yang dikirim ke<br>
                        <strong class="text-[#1a1a2e]">{{ $email }}</strong>
                    </p>
                </div>

                <form method="POST" action="{{ route('verify.otp') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">

                    <div>
                        <label class="block text-xs font-medium text-[#6a6a8a] uppercase tracking-wider text-center mb-2">Kode OTP</label>
                        <input type="text" name="otp" maxlength="6" required autofocus
                            class="input-otp"
                            placeholder="000000"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        @error('otp')
                            <p class="text-red-500 text-xs mt-1 text-center">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="btn-login">Verifikasi</button>
                </form>

                <p class="text-xs text-[#8a8aaa] text-center mt-6">© 2026 Sistem Armada</p>
            </div>
        </div>
    </div>

</body>
</html>
