<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - SIDIGAS</title>
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
                class="w-full bg-gray-900 text-white rounded text-sm font-semibold py-2.5 hover:bg-gray-800 transition">
                Kirim OTP
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="text-xs text-gray-500 hover:underline">&larr; Kembali</a>
        </div>
    </div>

</body>
</html>
