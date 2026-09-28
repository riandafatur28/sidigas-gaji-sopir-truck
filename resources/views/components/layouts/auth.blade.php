<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'SIDIGAS' }} - SIDIGAS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f3efe7; }
        .toggle-pass { cursor: pointer; user-select: none; }
    </style>
</head>
<body class="min-h-screen">

<div class="min-h-screen flex flex-col lg:flex-row items-stretch gap-5 lg:gap-8 p-4 sm:p-6 lg:p-8">

    {{-- PANEL GAMBAR (kiri) — persegi 1:1, rounded + padding, tone disesuaikan --}}
    <div class="lg:w-1/2 flex items-center justify-center shrink-0">
        <div class="relative w-full aspect-square max-w-[min(100%,calc(100vh-5rem))] rounded-2xl lg:rounded-3xl overflow-hidden" style="background:linear-gradient(160deg,#f6f3ee,#e7dcc2)">
            <img src="/images/auth-hero.jpg" alt="Armada SIDIGAS"
                class="absolute inset-0 w-full h-full object-cover object-center"
                onerror="this.remove();document.getElementById('authHeroFallback').hidden=false;">
            <div id="authHeroFallback" hidden class="absolute inset-0" style="background:linear-gradient(160deg,#f6f3ee 0%,#efe7d3 55%,#e7dcc2 100%)"></div>
        </div>
    </div>

    {{-- PANEL FORM (kanan) --}}
    <div class="flex-1 flex items-center justify-center">
        {{ $slot }}
    </div>
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
