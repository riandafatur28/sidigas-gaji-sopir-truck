<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP - SIDIGAS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f0f0f2; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-sm bg-white border border-gray-200 rounded p-6">
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
                class="w-full bg-gray-900 text-white rounded text-sm font-semibold py-2.5 hover:bg-gray-800 transition">
                Verifikasi
            </button>
        </form>
    </div>

</body>
</html>
