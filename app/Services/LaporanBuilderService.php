<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PenggajianDetail;
use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use Illuminate\Http\Request;

class LaporanBuilderService
{
    /**
     * Build data laporan untuk 1 periode.
     */
    public function build(Request $request): array
    {
        $periodeId = $request->get('periode');
        if (!$periodeId) {
            $latest = Periode::orderBy('id', 'desc')->first();
            $periodeId = $latest?->id;
        }

        $periode = $periodeId ? Periode::findOrFail($periodeId) : null;
        $data = $periodeId ? $this->buildLaporanData($periodeId) : null;

        return compact('periode', 'periodeId', 'data');
    }

    /**
     * Data untuk PDF export.
     */
    public function buildForPdf($periodeId): array
    {
        $periode = Periode::findOrFail($periodeId);
        $result = $this->build(new Request(['periode' => $periodeId]));
        return ['periode' => $periode, 'data' => $result['data']];
    }

    private function buildLaporanData($periodeId): array
    {
        $hariKerja = Ritase::where('periode_id', $periodeId)
            ->where('status', '!=', 'gagal_produksi')
            ->distinct('tanggal')->count('tanggal');

        $totalSopir = Sopir::whereHas('ritase', fn($q) => $q->where('periode_id', $periodeId))->count();

        $totalRitase = Ritase::where('periode_id', $periodeId)
            ->where('status', '!=', 'gagal_produksi')->count();

        $uniqueTrip = Ritase::where('periode_id', $periodeId)
            ->where('status', '!=', 'gagal_produksi')
            ->selectRaw('COUNT(DISTINCT CONCAT(kode_sopir, DATE(tanggal), kode_tujuan)) as total')
            ->value('total');

        $totalGagal = Ritase::where('periode_id', $periodeId)
            ->where('status', 'gagal_produksi')->count();

        [$gajiPerTujuan, $nonGagalPerTujuan, $gagalPerTujuan] = $this->getTujuanAggregates($periodeId);
        $allTujuanCodes = $gajiPerTujuan->keys()->merge($nonGagalPerTujuan->keys())->merge($gagalPerTujuan->keys())->unique();
        $tujuanList = Tujuan::whereIn('kode_tujuan', $allTujuanCodes)->get()->keyBy('kode_tujuan');

        [$detailRows, $totals] = $this->buildDetailRows($allTujuanCodes, $tujuanList, $gajiPerTujuan, $nonGagalPerTujuan, $gagalPerTujuan);

        return [
            'hari_kerja' => $hariKerja,
            'total_sopir' => $totalSopir,
            'total_ritase' => $totalRitase,
            'total_ritase_gagal' => $totalGagal,
            'unique_kabupaten' => $uniqueTrip,
            'detail_rows' => $detailRows,
        ] + $totals;
    }

    private function getTujuanAggregates($periodeId): array
    {
        $gaji = PenggajianDetail::whereHas('penggajian', fn($q) => $q->where('periode_id', $periodeId))
            ->selectRaw('kode_tujuan, SUM(jumlah_rit) as total_rit, SUM(total_solar) as total_solar, SUM(total_upah) as total_upah, SUM(total_tol) as total_tol, SUM(subtotal) as subtotal')
            ->groupBy('kode_tujuan')->get()->keyBy('kode_tujuan');

        $nonGagal = Ritase::where('periode_id', $periodeId)
            ->where('status', '!=', 'gagal_produksi')
            ->selectRaw('kode_tujuan, COUNT(*) as total_rit, SUM(dt) as total_dt')
            ->groupBy('kode_tujuan')->get()->keyBy('kode_tujuan');

        $gagal = Ritase::where('periode_id', $periodeId)
            ->where('status', 'gagal_produksi')
            ->selectRaw('kode_tujuan, COUNT(*) as jumlah_gagal, SUM(nominal_kompensasi) as total_kompensasi')
            ->groupBy('kode_tujuan')->get()->keyBy('kode_tujuan');

        return [$gaji, $nonGagal, $gagal];
    }

    private function buildDetailRows($allTujuanCodes, $tujuanList, $gajiPerTujuan, $nonGagalPerTujuan, $gagalPerTujuan): array
    {
        $rows = [];
        $totals = ['total_solar_all' => 0, 'total_upah_all' => 0, 'total_dt_all' => 0, 'total_gagal_all' => 0, 'grand_total_all' => 0];
        $no = 1;

        foreach ($allTujuanCodes as $kodeTujuan) {
            $nama = $tujuanList->get($kodeTujuan)?->nama ?? $kodeTujuan;
            $detail = $gajiPerTujuan->get($kodeTujuan);
            $nonGagal = $nonGagalPerTujuan->get($kodeTujuan);
            $gagal = $gagalPerTujuan->get($kodeTujuan);

            $dtTotal = floatval($nonGagal->total_dt ?? 0);
            $detailRit = intval($detail?->total_rit ?? 0);
            $liveRit = intval($nonGagal->total_rit ?? 0);
            $rit = max($detailRit, $liveRit);

            [$solarTotal, $upahTotal, $tolTotal] = $this->scaleRates($detail, $detailRit, $liveRit);
            $gagalQty = $gagal ? intval($gagal->jumlah_gagal) : 0;
            $gagalTotal = $gagal ? floatval($gagal->total_kompensasi) : 0;

            $solarPerRit = $detailRit > 0 ? $solarTotal / $detailRit : 0;
            $upahPerRit = $detailRit > 0 ? $upahTotal / $detailRit : 0;
            $tolPerRit = $detailRit > 0 ? $tolTotal / $detailRit : 0;
            $dtPerRit = $rit > 0 ? $dtTotal / $rit : 0;
            $subtotal = $solarTotal + $upahTotal + $dtTotal + $tolTotal + $gagalTotal;
            $groupNo = $no++;

            $rows[] = $this->makeRow($groupNo, $nama, 'Solar', $solarPerRit, $rit, $solarTotal);
            $rows[] = $this->makeRow($groupNo, $nama, 'Upah Sopir', $upahPerRit, $rit, $upahTotal);
            $rows[] = $this->makeRow($groupNo, $nama, 'DT', $dtPerRit, $rit, $dtTotal);
            if ($tolTotal > 0) $rows[] = $this->makeRow($groupNo, $nama, 'Tol', $tolPerRit, $rit, $tolTotal);
            if ($gagalQty > 0) $rows[] = $this->makeRow($groupNo, $nama, 'Gagal', $gagalTotal / $gagalQty, $gagalQty, $gagalTotal);
            $rows[] = ['no' => '', 'tujuan' => $nama, 'jenis' => 'SUBTOTAL', 'harga' => 0, 'qty' => $rit + $gagalQty, 'total' => $subtotal, 'is_subtotal' => true];

            $totals['total_solar_all'] += $solarTotal;
            $totals['total_upah_all'] += $upahTotal;
            $totals['total_dt_all'] += $dtTotal;
            $totals['total_gagal_all'] += $gagalTotal;
            $totals['grand_total_all'] += $subtotal;
        }

        return [$rows, $totals];
    }

    private function scaleRates($detail, $detailRit, $liveRit): array
    {
        $solar = floatval($detail?->total_solar ?? 0);
        $upah = floatval($detail?->total_upah ?? 0);
        $tol = floatval($detail?->total_tol ?? 0);

        if ($liveRit > $detailRit && $detailRit > 0) {
            $solar = ($solar / $detailRit) * $liveRit;
            $upah = ($upah / $detailRit) * $liveRit;
            $tol = ($tol / $detailRit) * $liveRit;
        }
        return [$solar, $upah, $tol];
    }

    private function makeRow($no, $tujuan, $jenis, $harga, $qty, $total): array
    {
        return compact('no', 'tujuan', 'jenis', 'harga', 'qty', 'total') + ['is_subtotal' => false];
    }
}
