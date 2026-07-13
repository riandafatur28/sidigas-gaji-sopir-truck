<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SopirController;
use App\Http\Controllers\TujuanController;
use App\Http\Controllers\RitaseController;
use App\Http\Controllers\PeriodeController;
use App\Http\Controllers\PenggajianController;
use Illuminate\Support\Facades\Route;

// Redirect root ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// ================= GUEST ROUTES =================
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Lupa Password
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendOtp'])->name('password.email');

    // Verify OTP
    Route::get('/verify-otp', [AuthController::class, 'showVerifyOtp'])->name('verify.otp.form');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify.otp');

    // Reset Password
    Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('reset.password.form');
    Route::post('/reset-password', [AuthController::class, 'updatePassword'])->name('password.update');
});

// ================= AUTHENTICATED ROUTES =================
Route::middleware('auth')->group(function () {
    // Dashboard & Logout
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Kelola Sopir
    Route::get('/sopir', [SopirController::class, 'index'])->name('sopir.index');
    Route::post('/sopir', [SopirController::class, 'store'])->name('sopir.store');
    Route::put('/sopir/{id}', [SopirController::class, 'update'])->name('sopir.update');
    Route::delete('/sopir/{id}', [SopirController::class, 'destroy'])->name('sopir.destroy');

    // Kelola Tujuan
    Route::get('/tujuan', [TujuanController::class, 'index'])->name('tujuan.index');
    Route::post('/tujuan', [TujuanController::class, 'store'])->name('tujuan.store');
    Route::put('/tujuan/{id}', [TujuanController::class, 'update'])->name('tujuan.update');
    Route::delete('/tujuan/{id}', [TujuanController::class, 'destroy'])->name('tujuan.destroy');

    // Kelola Ritase
    Route::get('/ritase', [RitaseController::class, 'index'])->name('ritase.index');
    Route::post('/ritase', [RitaseController::class, 'store'])->name('ritase.store');
    Route::put('/ritase/{id}', [RitaseController::class, 'update'])->name('ritase.update');
    Route::delete('/ritase/{id}', [RitaseController::class, 'destroy'])->name('ritase.destroy');

    // Kelola Periode
    Route::get('/periode', [PeriodeController::class, 'index'])->name('periode.index');
    Route::post('/periode', [PeriodeController::class, 'store'])->name('periode.store');
    Route::put('/periode/{id}', [PeriodeController::class, 'update'])->name('periode.update');
    Route::delete('/periode/{id}', [PeriodeController::class, 'destroy'])->name('periode.destroy');

    // Kelola Gaji
Route::get('/gaji', [PenggajianController::class, 'index'])->name('gaji.index');
Route::get('/gaji/riwayat', [PenggajianController::class, 'riwayat'])->name('gaji.riwayat');
Route::get('/gaji/{id}/edit', [PenggajianController::class, 'edit'])->name('gaji.edit');
Route::put('/gaji/{id}', [PenggajianController::class, 'update'])->name('gaji.update');
Route::post('/gaji', [PenggajianController::class, 'store'])->name('gaji.store');
Route::delete('/gaji/{id}', [PenggajianController::class, 'destroy'])->name('gaji.destroy');
Route::get('/gaji/slip/{periode_id}/{kode_sopir}', [PenggajianController::class, 'slipGaji'])->name('gaji.slip');
Route::get('/api/get-ritase-data', [PenggajianController::class, 'getRitaseData'])->name('api.get-ritase-data');

    // API untuk get data ritase
    Route::get('/api/get-ritase-data', [PenggajianController::class, 'getRitaseData'])->name('api.get-ritase-data');

    // API untuk cek aturan sewa DT
    Route::post('/ritase/cek-aturan', [RitaseController::class, 'cekAturanSewaDT'])->name('ritase.cek.aturan');
});
