<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Terlalu Banyak Percobaan - SIDIGAS</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f9fafb; color: #111827; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .card { width: 100%; max-width: 26rem; background: #fff; border: 1px solid #e5e7eb; border-radius: .5rem; padding: 2rem 1.75rem; text-align: center; }
    .code { font-size: 3rem; font-weight: 800; color: #111827; line-height: 1; }
    h1 { font-size: 1.125rem; font-weight: 700; margin-top: .75rem; }
    p { font-size: .875rem; color: #4b5563; margin-top: .5rem; line-height: 1.5; }
    .count { font-size: 2rem; font-weight: 800; color: #2d6a4f; margin-top: 1rem; }
    .btn { display: inline-block; margin-top: 1.25rem; background: #111827; color: #fff; font-size: .875rem; font-weight: 600; padding: .625rem 1.5rem; border-radius: .375rem; text-decoration: none; }
    .btn:hover { background: #000; }
    .hint { font-size: .75rem; color: #9ca3af; margin-top: 1rem; }
</style>
</head>
<body>
@php
    $headers = (isset($exception) && method_exists($exception, 'getHeaders')) ? $exception->getHeaders() : [];
    $retry = max(1, (int) ($headers['Retry-After'] ?? 60));
@endphp
<div class="card">
    <div class="code">429</div>
    <h1>Terlalu Banyak Percobaan</h1>
    <p>Demi keamanan, login dan OTP dibatasi beberapa kali percobaan per menit. Silakan tunggu sebentar lalu coba lagi.</p>
    <div class="count"><span id="cd" data-retry="{{ $retry }}">{{ $retry }}</span> dtk</div>
    <a class="btn" href="{{ route('login') }}">Kembali ke Login</a>
    <p class="hint">Jangan spam tombol — setiap percobaan gagal ikut dihitung.</p>
</div>
<script>
    (function () {
        var el = document.getElementById('cd');
        var s = parseInt(el.getAttribute('data-retry'), 10) || 60;
        var t = setInterval(function () {
            s -= 1;
            if (s <= 0) { clearInterval(t); el.textContent = '0'; location.reload(); return; }
            el.textContent = s;
        }, 1000);
    })();
</script>
</body>
</html>
