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

        $this->authService->sendOtp($request->email);
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

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
