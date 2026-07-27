<?php

namespace App\Http\Controllers;

use App\Models\Penggajian;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\ValidasiBukti;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Auto-sync: periode yg mencakup hari ini jadi aktif, lainnya selesai
        \App\Models\Periode::syncActiveStatus();
        \App\Models\Sopir::syncActiveStatus();
        \App\Models\Tujuan::syncActiveStatus();

        $user = Auth::user();
        $filter = $request->get('periode', 'periode_ini');
        $tanggal = $request->get('tanggal', '');

        $totalSopir = Sopir::count();
        $sopirAktif = Sopir::where('status', 'aktif')->count();
        $sopirNonaktif = Sopir::where('status', 'nonaktif')->count();

        $ritaseQuery = Ritase::query();
        $gajiQuery = Penggajian::query();

        $startDate = null;
        $endDate = now();
        $periodLabel = 'Periode Ini';
        if ($filter == 'semua') $periodLabel = 'Semua Waktu';

        // If specific date is set, override everything
        if ($tanggal) {
            $startDate = \Carbon\Carbon::parse($tanggal);
            $endDate = \Carbon\Carbon::parse($tanggal);
            $periodLabel = $tanggal;
        }

        switch ($filter) {
            case 'periode_ini':
                $periodeAktif = \App\Models\Periode::where('status', 'aktif')
                    ->where('tanggal_mulai', '<=', now())
                    ->where('tanggal_selesai', '>=', now())
                    ->first();
                if ($periodeAktif) {
                    $startDate = \Carbon\Carbon::parse($periodeAktif->tanggal_mulai);
                    $endDate = \Carbon\Carbon::parse($periodeAktif->tanggal_selesai);
                } else {
                    $periodeAktif = \App\Models\Periode::where('status', 'aktif')->first();
                    if ($periodeAktif) {
                        $startDate = \Carbon\Carbon::parse($periodeAktif->tanggal_mulai);
                        $endDate = \Carbon\Carbon::parse($periodeAktif->tanggal_selesai);
                    }
                }
                $periodLabel = 'Periode Ini';
                break;
            case 'periode_lalu':
                $periodeLalu = \App\Models\Periode::where(function ($q) {
                    $q->where('status', 'selesai')
                      ->orWhere('tanggal_selesai', '<', now());
                })->latest('tanggal_selesai')->first();
                if ($periodeLalu) {
                    $startDate = \Carbon\Carbon::parse($periodeLalu->tanggal_mulai);
                    $endDate = \Carbon\Carbon::parse($periodeLalu->tanggal_selesai);
                }
                $periodLabel = 'Periode Lalu';
                break;
            case 'bulan_ini':
                $startDate = now()->startOfMonth();
                $periodLabel = 'Bulan Ini';
                break;
            case '3_bulan_lalu':
                $startDate = now()->subMonths(3)->startOfMonth();
                $periodLabel = '3 Bulan Lalu';
                break;
            case '6_bulan_lalu':
                $startDate = now()->subMonths(6)->startOfMonth();
                $periodLabel = '6 Bulan Lalu';
                break;
            case '1_tahun_lalu':
                $startDate = now()->subYear()->startOfYear();
                $periodLabel = '1 Tahun Lalu';
                break;
        }

        if ($startDate) {
            $ritaseQuery->whereBetween('tanggal', [$startDate, $endDate]);
            $gajiQuery->whereHas('periode', function ($q) use ($startDate, $endDate) {
                $q->where('tanggal_mulai', '<=', $endDate)
                  ->where('tanggal_selesai', '>=', $startDate);
            });
        }

        // Optimasi: hitung semua status ritase dalam 1 query
        $ritaseCounts = (clone $ritaseQuery)
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN status = 'valid' THEN 1 ELSE 0 END) as valid")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'gagal_produksi' THEN 1 ELSE 0 END) as gagal")
            ->first();

        $totalRitase = (int) ($ritaseCounts->total ?? 0);
        $ritaseValid = (int) ($ritaseCounts->valid ?? 0);
        $ritasePending = (int) ($ritaseCounts->pending ?? 0);
        $ritaseGagal = (int) ($ritaseCounts->gagal ?? 0);

        $totalGaji = (clone $gajiQuery)->sum('total') ?? 0;

        // Optimasi: hitung semua status validasi dalam 1 query
        $validasiCounts = ValidasiBukti::selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'disetujui' THEN 1 ELSE 0 END) as disetujui")
            ->selectRaw("SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak")
            ->first();

        $validasiPending = (int) ($validasiCounts->pending ?? 0);
        $validasiDisetujui = (int) ($validasiCounts->disetujui ?? 0);
        $validasiDitolak = (int) ($validasiCounts->ditolak ?? 0);
        $validasiHariIni = ValidasiBukti::whereDate('created_at', today())->count();

        // Aktivitas terbaru
        $recentRitase = \App\Models\Ritase::with(['sopir:id,kode_sopir,nama', 'tujuan:id,kode_tujuan,nama']);
        if ($startDate) {
            $recentRitase->whereBetween('tanggal', [$startDate, $endDate]);
        }
        $recentRitase = $recentRitase->latest()->limit(8)->get();

        // Data pendukung psikologi
        $periodeAktif = \App\Models\Periode::where('status', 'aktif')->first();
        $sisaHari = 0;
        $progressPeriode = 0;
        if ($periodeAktif) {
            $mulai = \Carbon\Carbon::parse($periodeAktif->tanggal_mulai);
            $selesai = \Carbon\Carbon::parse($periodeAktif->tanggal_selesai);
            $totalHari = $mulai->diffInDays($selesai) ?: 1;
            $hariTerpakai = $mulai->diffInDays(now()->min($selesai));
            $progressPeriode = min(100, round(($hariTerpakai / $totalHari) * 100));
            $sisaHari = (int) max(0, today()->diffInDays($selesai));
        }

        $hariIniRitase = \App\Models\Ritase::whereDate('tanggal', today())->count();
        $hariIniValidasi = \App\Models\ValidasiBukti::whereDate('created_at', today())->count();

        // Peringkat sopir (top 5 by total ritase dalam rentang filter)
        $topSopir = \App\Models\Ritase::selectRaw('kode_sopir, COUNT(*) as total')
            ->with('sopir');
        if ($startDate) {
            $topSopir->whereBetween('tanggal', [$startDate, $endDate]);
        }
        $topSopir = $topSopir->groupBy('kode_sopir')->orderByDesc('total')->limit(5)->get();

        // Jumlah validasi yang ditolak (untuk learning effect)
        $validasiHariIni = $hariIniValidasi;

        return view('dashboard.index', compact(
            'user',
            'totalSopir',
            'sopirAktif',
            'sopirNonaktif',
            'totalRitase',
            'ritasePending',
            'ritaseValid',
            'ritaseGagal',
            'totalGaji',
            'validasiPending',
            'validasiDisetujui',
            'validasiDitolak',
            'validasiHariIni',
            'recentRitase',
            'filter',
            'periodLabel',
            'startDate',
            'endDate',
            'tanggal',
            'periodeAktif',
            'sisaHari',
            'progressPeriode',
            'hariIniRitase',
            'hariIniValidasi',
            'topSopir'
        ));
    }
}
