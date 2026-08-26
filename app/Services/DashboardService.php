<?php

namespace App\Services;

use App\Models\Penggajian;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\ValidasiBukti;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardService
{
    public function getIndexData(Request $request): array
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $filter = $request->get('periode', 'periode_ini');
        $tanggal = $request->get('tanggal', '');

        $totalSopir = Sopir::count();
        $sopirAktif = Sopir::where('status', 'aktif')->count();
        $sopirNonaktif = Sopir::where('status', 'nonaktif')->count();

        [$startDate, $endDate, $periodLabel] = $this->resolveFilter($filter, $tanggal);

        $ritaseQuery = Ritase::query();
        $gajiQuery = Penggajian::query();

        if ($startDate) {
            $ritaseQuery->whereBetween('tanggal', [$startDate, $endDate]);
            $gajiQuery->whereHas('periode', fn($q) => $q->where('tanggal_mulai', '<=', $endDate)->where('tanggal_selesai', '>=', $startDate));
        }

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

        $validasiCounts = ValidasiBukti::selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'disetujui' THEN 1 ELSE 0 END) as disetujui")
            ->selectRaw("SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak")
            ->first();

        $validasiPending = (int) ($validasiCounts->pending ?? 0);
        $validasiDisetujui = (int) ($validasiCounts->disetujui ?? 0);
        $validasiDitolak = (int) ($validasiCounts->ditolak ?? 0);
        $validasiHariIni = ValidasiBukti::whereDate('created_at', today())->count();

        $recentRitase = Ritase::with(['sopir:id,kode_sopir,nama', 'tujuan:id,kode_tujuan,nama']);
        if ($startDate) {
            $recentRitase->whereBetween('tanggal', [$startDate, $endDate]);
        }
        $recentRitase = $recentRitase->latest()->limit(8)->get();

        [$periodeAktif, $sisaHari, $progressPeriode] = $this->getPeriodeProgress();

        $hariIniRitase = Ritase::whereDate('tanggal', today())->count();
        $hariIniValidasi = $validasiHariIni;

        $topSopir = Ritase::selectRaw('kode_sopir, COUNT(*) as total')->with('sopir');
        if ($startDate) {
            $topSopir->whereBetween('tanggal', [$startDate, $endDate]);
        }
        $topSopir = $topSopir->groupBy('kode_sopir')->orderByDesc('total')->limit(5)->get();

        return compact(
            'user', 'totalSopir', 'sopirAktif', 'sopirNonaktif',
            'totalRitase', 'ritasePending', 'ritaseValid', 'ritaseGagal', 'totalGaji',
            'validasiPending', 'validasiDisetujui', 'validasiDitolak', 'validasiHariIni',
            'recentRitase', 'filter', 'periodLabel', 'startDate', 'endDate', 'tanggal',
            'periodeAktif', 'sisaHari', 'progressPeriode',
            'hariIniRitase', 'hariIniValidasi', 'topSopir'
        );
    }

    private function resolveFilter(string $filter, string $tanggal): array
    {
        $startDate = null;
        $endDate = now();
        $periodLabel = 'Periode Ini';

        if ($tanggal) {
            return [Carbon::parse($tanggal), Carbon::parse($tanggal), $tanggal];
        }

        switch ($filter) {
            case 'semua':
                return [null, now(), 'Semua Waktu'];
            case 'periode_ini':
                $periode = \App\Models\Periode::where('status', 'aktif')
                    ->where('tanggal_mulai', '<=', now())
                    ->where('tanggal_selesai', '>=', now())
                    ->first()
                    ?? \App\Models\Periode::where('status', 'aktif')->first();
                if ($periode) {
                    return [Carbon::parse($periode->tanggal_mulai), Carbon::parse($periode->tanggal_selesai), 'Periode Ini'];
                }
                return [$startDate, $endDate, $periodLabel];
            case 'periode_lalu':
                $periodeLalu = \App\Models\Periode::where(fn($q) => $q->where('status', 'selesai')->orWhere('tanggal_selesai', '<', now()))
                    ->latest('tanggal_selesai')->first();
                if ($periodeLalu) {
                    return [Carbon::parse($periodeLalu->tanggal_mulai), Carbon::parse($periodeLalu->tanggal_selesai), 'Periode Lalu'];
                }
                return [$startDate, $endDate, $periodLabel];
            case 'bulan_ini':
                return [now()->startOfMonth(), now(), 'Bulan Ini'];
            case '3_bulan_lalu':
                return [now()->subMonths(3)->startOfMonth(), now(), '3 Bulan Lalu'];
            case '6_bulan_lalu':
                return [now()->subMonths(6)->startOfMonth(), now(), '6 Bulan Lalu'];
            case '1_tahun_lalu':
                return [now()->subYear()->startOfYear(), now(), '1 Tahun Lalu'];
        }

        return [$startDate, $endDate, $periodLabel];
    }

    private function getPeriodeProgress(): array
    {
        $periodeAktif = \App\Models\Periode::where('status', 'aktif')->first();
        if (!$periodeAktif) {
            return [null, 0, 0];
        }
        $mulai = Carbon::parse($periodeAktif->tanggal_mulai);
        $selesai = Carbon::parse($periodeAktif->tanggal_selesai);
        $totalHari = $mulai->diffInDays($selesai) ?: 1;
        $hariTerpakai = $mulai->diffInDays(now()->min($selesai));
        $progressPeriode = min(100, round(($hariTerpakai / $totalHari) * 100));
        $sisaHari = (int) max(0, today()->diffInDays($selesai));

        return [$periodeAktif, $sisaHari, $progressPeriode];
    }
}
