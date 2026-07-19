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
        $user = Auth::user();
        $filter = $request->get('periode', 'semua');

        $totalSopir = Sopir::count();
        $sopirAktif = Sopir::where('status', 'aktif')->count();
        $sopirNonaktif = Sopir::where('status', 'nonaktif')->count();

        $ritaseQuery = Ritase::query();
        $gajiQuery = Penggajian::query();

        $startDate = null;
        $endDate = now();
        $periodLabel = 'Semua Waktu';

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

        // Aktivitas terbaru (pindah dari view ke controller)
        $recentRitase = \App\Models\Ritase::with(['sopir:id,kode_sopir,nama', 'tujuan:id,kode_tujuan,nama']);
        if ($startDate) {
            $recentRitase->whereBetween('tanggal', [$startDate, $endDate]);
        }
        $recentRitase = $recentRitase->latest()->limit(6)->get();

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
            'endDate'
        ));
    }
}
