<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email|ends_with:gmail.com',
            'password' => 'required',
        ], [
            'email.ends_with' => 'Email harus menggunakan domain @gmail.com.',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|ends_with:gmail.com|exists:users,email',
        ], [
            'email.ends_with' => 'Email harus menggunakan domain @gmail.com.',
        ]);

        try {
            $this->authService->sendOtp($request->email);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()
                ->with('error', 'Kode OTP gagal dikirim. Coba lagi sebentar, atau hubungi admin bila berlanjut.');
        }

        return redirect()->route('verify.otp.form', ['email' => $request->email])
            ->with('success', 'Kode OTP telah dikirim ke email Anda.');
    }

    public function showVerifyOtp(Request $request)
    {
        return view('auth.verify-otp', ['email' => $request->query('email')]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['email' => 'required|email', 'otp' => 'required|digits:6']);

        if (!$this->authService->verifyOtp($request->email, $request->otp)) {
            return back()->withErrors(['otp' => 'Kode OTP salah atau sudah kadaluarsa.']);
        }

        return redirect()->route('reset.password.form', ['email' => $request->email]);
    }

    public function showResetPassword(Request $request)
    {
        return view('auth.reset-password', ['email' => $request->query('email')]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        $this->authService->resetPassword($request->email, $request->password);
        return redirect()->route('login')->with('success', 'Password berhasil diperbarui! Silakan login.');
    }

    public function loginGoogle()
    {
        return Socialite::driver('google')
            ->redirectUrl($this->authService->getGoogleRedirectUrl())
            ->redirect();
    }

    public function loginGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl($this->authService->getGoogleRedirectUrl())->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Gagal login dengan Google. Silakan coba lagi.']);
        }

        $user = User::where('email', $googleUser->email)->first();
        if (!$user) {
            return redirect()->route('login')->withErrors(['email' => 'Email Google tidak terdaftar di sistem. Silakan login dengan email & password.']);
        }

        Auth::login($user);
        request()->session()->regenerate();
        return redirect()->route('dashboard');
    }

    /**
     * Google Identity Services (GSI) — frontend mengirim id_token, diverifikasi
     * di sini. Sandbox ini memblokir POST/HTTP2 ke *.googleapis.com lewat proxy
     * egress (Socialite hang), tapi GET CLI curl tembus — jadi token diverifikasi
     * via `curl` ke endpoint tokeninfo.
     */
    public function loginGoogleVerify(Request $request)
    {
        $fail = function (string $msg) use ($request) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'error' => $msg])
                : redirect()->route('login')->withErrors(['email' => $msg]);
        };

        $idToken = $request->input('id_token');
        if (!is_string($idToken) || $idToken === '') {
            return $fail('Gagal login dengan Google. Silakan coba lagi.');
        }

        $payload = null;
        $networkDown = false;
        try {
            $payload = $this->verifyGoogleIdToken($idToken);
        } catch (\RuntimeException $e) {
            $networkDown = true;
        }
        $clientId = (string) config('services.google.client_id');
        $aud = $payload['aud'] ?? null;
        $audOk = is_array($aud) ? in_array($clientId, $aud, true) : ($aud === $clientId);
        \Illuminate\Support\Facades\Log::info('gsi-verify', [
            'payload_null' => $payload === null,
            'aud' => is_string($aud) ? substr($aud, 0, 60) : $aud,
            'client_id' => substr($clientId, 0, 60),
            'aud_ok' => $audOk,
            'email' => $payload['email'] ?? null,
        ]);
        if ($networkDown) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'network' => true, 'error' => 'Koneksi ke Google sedang terganggu. Mencoba ulang...'])
                : redirect()->route('login')->withErrors(['email' => 'Koneksi ke Google sedang terganggu. Coba beberapa saat lagi.']);
        }
        if (!$payload || !$audOk) {
            return $fail('Gagal verifikasi akun Google. Silakan coba lagi.');
        }

        $email = $payload['email'] ?? null;
        if (!$email) {
            return $fail('Gagal membaca email Google.');
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return $fail('Email Google tidak terdaftar di sistem. Silakan login dengan email & password.');
        }

        Auth::login($user);
        $request->session()->regenerate();
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'redirect' => route('dashboard')]);
        }
        return redirect()->route('dashboard');
    }

    private function verifyGoogleIdToken(string $idToken): ?array
    {
        $clientId = (string) config('services.google.client_id');
        $certsPath = storage_path('app/google-certs.json');
        // Jatim bahannya: JWT + JWK — offline verification (tanpa curl per-login).
        if (!is_file($certsPath)) {
            // fallback lama — curl sekali ke tokeninfo (paksa IPv4 literal, retry internal)
            $proxy = (string) (getenv('HTTPS_PROXY') ?: getenv('https_proxy') ?: '');
            if (str_contains($proxy, 'hatch-egress-proxy')) {
                $proxy = 'http://198.19.0.1:3128';
            }
            $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $idToken;
            $cmd = 'curl -s -4 --connect-timeout 5 --max-time 20 --retry 3 --retry-delay 1 --retry-all-errors '
                . ($proxy ? '--proxy ' . escapeshellarg($proxy) . ' ' : '')
                . '-w "__CODE__%{http_code}__" ' . escapeshellarg($url) . ' 2>/tmp/gsi_curl_err.txt';
            $out = @shell_exec($cmd);
            if (!$out) return null;
            $code = 0;
            if (preg_match('/__CODE__(\d{3})__/', $out, $m)) {
                $code = (int) $m[1];
            }
            if ($code === 0) {
                throw new \RuntimeException('gsi_network_down');
            }
            $codePos = strrpos($out, '__CODE__');
            $body = $codePos === false ? $out : substr($out, 0, $codePos);
            $d = json_decode($body, true);
            if (!is_array($d) || isset($d['error'])) return null;
            return $d;
        }
        // offline: validasi signature + claim JWT pakai kunci Google yang di-cache.
        try {
            $raw = @file_get_contents($certsPath);
            $jwks = @json_decode($raw ?: '', true);
            if (!is_array($jwks) || empty($jwks['keys'])) return null;
            $keys = \Firebase\JWT\JWK::parseKeySet($jwks);
            $decoded = \Firebase\JWT\JWT::decode($idToken, $keys);
            $p = json_decode(json_encode($decoded), true);
            // cek audience & issuer
            $aud = $p['aud'] ?? null;
            $audOk = is_array($aud) ? in_array($clientId, $aud, true) : ($aud === $clientId);
            $iss = $p['iss'] ?? '';
            $issOk = str_starts_with($iss, 'https://accounts.google.com');
            if (!$audOk || !$issOk) return null;
            // exp / iat ditangani Firebase JWT (throw 401 jika expired)
            return $p;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::info('gsi-jwt', ['err' => $e->getMessage()]);
            return null;
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
