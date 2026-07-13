<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    // ============ LOGIN ============
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors(['email' => 'Email atau password salah.'])
            ->onlyInput('email');
    }

    // ============ LUPA PASSWORD ============
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        Otp::where('email', $request->email)->delete();

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Otp::create([
            'email'      => $request->email,
            'otp'        => $otp,
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);

        Mail::to($request->email)->send(new OtpMail($otp, $request->email));

        return redirect()
            ->route('verify.otp.form', ['email' => $request->email])
            ->with('success', 'Kode OTP telah dikirim ke email Anda.');
    }

    // ============ VERIFY OTP ============
    public function showVerifyOtp(Request $request)
    {
        return view('auth.verify-otp', ['email' => $request->query('email')]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|digits:6',
        ]);

        $otpData = Otp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>=', Carbon::now())
            ->first();

        if (!$otpData) {
            return back()->withErrors(['otp' => 'Kode OTP salah atau sudah kadaluarsa.']);
        }

        $otpData->delete();

        return redirect()->route('reset.password.form', ['email' => $request->email]);
    }

    // ============ RESET PASSWORD ============
    public function showResetPassword(Request $request)
    {
        return view('auth.reset-password', ['email' => $request->query('email')]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email|exists:users,email',
            'password'              => 'required|min:6|confirmed',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();
        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()
            ->route('login')
            ->with('success', 'Password berhasil diperbarui! Silakan login.');
    }

    // ============ LOGOUT ============
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
