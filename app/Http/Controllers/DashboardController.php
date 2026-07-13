<?php

namespace App\Http\Controllers;

use App\Models\Sopir;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // ===== DATA SOPIR (dari database) =====
        $totalSopir = Sopir::count();
        $sopirAktif = Sopir::where('status', 'aktif')->count();
        $sopirNonaktif = Sopir::where('status', 'nonaktif')->count();

        // ===== DATA RITASE =====
        // Cek apakah tabel ritase sudah ada
        $totalRitase = 0;
        $ritasePending = 0;

        if (Schema::hasTable('ritases')) {
            $totalRitase = DB::table('ritases')->where('status', 'valid')->count();
            $ritasePending = DB::table('ritases')->where('status', 'pending')->count();
        }

       // ===== DATA GAJI =====
        $totalGaji = 0;
        $totalSolar = 0;
        $totalUpah = 0;
        $totalSewaDT = 0;

        if (Schema::hasTable('gajis')) {
            $totalSolar = DB::table('gajis')->sum('total_solar') ?? 0;
            $totalUpah = DB::table('gajis')->sum('total_upah') ?? 0;
            $totalSewaDT = DB::table('gajis')->sum('total_sewa_dt') ?? 0;
            $totalGaji = DB::table('gajis')->sum('grand_total');
        }

        // ===== PERSENTASE PERTUMBUHAN SOPIR =====
        // Bandingkan dengan bulan lalu
        $sopirBulanLalu = Sopir::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $pertumbuhanSopir = 0;
        if ($sopirBulanLalu > 0) {
            $pertumbuhanSopir = round((($sopirAktif - $sopirBulanLalu) / $sopirBulanLalu) * 100, 1);
        } elseif ($sopirAktif > 0) {
            $pertumbuhanSopir = 100; // Baru ada sopir bulan ini
        }

        return view('dashboard.index', compact(
            'user',
            'totalSopir',
            'sopirAktif',
            'sopirNonaktif',
            'totalRitase',
            'ritasePending',
            'totalGaji',
            'pertumbuhanSopir'
        ));
    }
}
