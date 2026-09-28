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

        // Per-hari aggregates untuk breakdown harian
        $perHari = $this->getPerHariAggregates($periodeId);

        [$detailRows, $totals] = $this->buildDetailRowsPerHari($tujuanList, $gajiPerTujuan, $perHari);

        return [
            'hari_kerja' => $hariKerja,
            'total_sopir' => $totalSopir,
            'total_ritase' => $totalRitase,
            'total_ritase_gagal' => $totalGagal,
            'unique_kabupaten' => $uniqueTrip,
            'detail_rows' => $detailRows,
            'per_hari' => $perHari['grouped'],
            'use_per_hari' => !empty($perHari['grouped']),
        ] + $totals;
    }

    private function getPerHariAggregates($periodeId): array
    {
        $perHariValid = Ritase::where('periode_id', $periodeId)
            ->where('status', '!=', 'gagal_produksi')
            ->selectRaw('DATE(tanggal) as tgl, kode_tujuan, COUNT(*) as cnt, SUM(dt) as dt_sum')
            ->groupBy('tgl', 'kode_tujuan')
            ->get();

        $perHariGagal = Ritase::where('periode_id', $periodeId)
            ->where('status', 'gagal_produksi')
            ->selectRaw('DATE(tanggal) as tgl, kode_tujuan, COUNT(*) as cnt, SUM(nominal_kompensasi) as komp_sum')
            ->groupBy('tgl', 'kode_tujuan')
            ->get();

        $grouped = [];
        foreach ($perHariValid as $r) {
            $key = $r->tgl . '|' . $r->kode_tujuan;
            $grouped[$key] = [
                'tgl' => $r->tgl,
                'kode_tujuan' => $r->kode_tujuan,
                'cnt_valid' => (int) $r->cnt,
                'dt_sum' => (float) $r->dt_sum,
                'cnt_gagal' => 0,
                'komp_sum' => 0,
            ];
        }
        foreach ($perHariGagal as $r) {
            $key = $r->tgl . '|' . $r->kode_tujuan;
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['tgl' => $r->tgl, 'kode_tujuan' => $r->kode_tujuan, 'cnt_valid' => 0, 'dt_sum' => 0, 'cnt_gagal' => 0, 'komp_sum' => 0];
            }
            $grouped[$key]['cnt_gagal'] = (int) $r->cnt;
            $grouped[$key]['komp_sum'] = (float) $r->komp_sum;
        }

        // Index by tgl for view grouping
        $byDate = [];
        foreach ($grouped as $row) {
            $byDate[$row['tgl']][] = $row;
        }
        ksort($byDate);

        return ['grouped' => $byDate, 'flat' => $grouped];
    }

    private function buildDetailRowsPerHari($tujuanList, $gajiPerTujuan, $perHari): array
    {
        $rows = [];
        $totals = ['total_solar_all' => 0, 'total_upah_all' => 0, 'total_dt_all' => 0, 'total_gagal_all' => 0, 'grand_total_all' => 0];
        $no = 1;

        $grouped = $perHari['grouped'] ?? [];
        if (empty($grouped)) {
            // Fallback ke agregat lama jika tidak ada data per-hari
            return $this->buildDetailRowsLegacy($tujuanList, $gajiPerTujuan);
        }

        foreach ($grouped as $tgl => $items) {
            $carbon = \Carbon\Carbon::parse($tgl)->locale('id');
            $hariNama = $carbon->translatedFormat('l');
            $tglLabel = $carbon->translatedFormat('d F Y');
            $fullLabel = $hariNama . ', ' . $tglLabel;

            foreach ($items as $it) {
                $kodeTujuan = $it['kode_tujuan'];
                $nama = $tujuanList->get($kodeTujuan)?->nama ?? $kodeTujuan;
                $detail = $gajiPerTujuan->get($kodeTujuan);
                $cntValid = $it['cnt_valid'];
                $dtSum = $it['dt_sum'];
                $cntGagal = $it['cnt_gagal'];
                $kompSum = $it['komp_sum'];

                // Hitung solar/upah/tol per-hari proporsional dari total gaji
                $detailRit = intval($detail?->total_rit ?? 0);
                $liveRitTotal = \App\Models\Ritase::where('kode_tujuan', $kodeTujuan)->where('status', '!=', 'gagal_produksi')->count();
                // Gunakan rate per-rit dari detail
                $solarTotalAll = floatval($detail?->total_solar ?? 0);
                $upahTotalAll = floatval($detail?->total_upah ?? 0);
                $tolTotalAll = floatval($detail?->total_tol ?? 0);
                $solarPerRit = $detailRit > 0 ? $solarTotalAll / $detailRit : 0;
                $upahPerRit = $detailRit > 0 ? $upahTotalAll / $detailRit : 0;
                $tolPerRit = $detailRit > 0 ? $tolTotalAll / $detailRit : 0;

                $solarHari = $solarPerRit * $cntValid;
                $upahHari = $upahPerRit * $cntValid;
                $tolHari = $tolPerRit * $cntValid;
                $dtHari = $dtSum;
                $subtotal = $solarHari + $upahHari + $dtHari + $tolHari + $kompSum;

                $groupNo = $no++;

                // Simpan dengan info hari/tanggal untuk view
                $base = ['hari' => $hariNama, 'tanggal' => $tgl, 'tgl_label' => $fullLabel];

                if ($cntValid > 0) {
                    $rows[] = array_merge($base, $this->makeRow($groupNo, $nama, 'Solar', $solarPerRit, $cntValid, $solarHari));
                    $rows[] = array_merge($base, $this->makeRow($groupNo, $nama, 'Upah Sopir', $upahPerRit, $cntValid, $upahHari));
                    $rows[] = array_merge($base, $this->makeRow($groupNo, $nama, 'DT', $cntValid > 0 ? $dtHari / $cntValid : 0, $cntValid, $dtHari));
                    if ($tolHari > 0) $rows[] = array_merge($base, $this->makeRow($groupNo, $nama, 'Tol', $tolPerRit, $cntValid, $tolHari));
                }
                if ($cntGagal > 0) {
                    $rows[] = array_merge($base, $this->makeRow($groupNo, $nama, 'Gagal', $cntGagal > 0 ? $kompSum / $cntGagal : 0, $cntGagal, $kompSum));
                }
                // SUBTOTAL per hari-tujuan
                $rows[] = array_merge($base, ['no' => '', 'tujuan' => $nama, 'jenis' => 'SUBTOTAL', 'harga' => 0, 'qty' => $cntValid + $cntGagal, 'total' => $subtotal, 'is_subtotal' => true]);

                $totals['total_solar_all'] += $solarHari;
                $totals['total_upah_all'] += $upahHari;
                $totals['total_dt_all'] += $dtHari;
                $totals['total_gagal_all'] += $kompSum;
                $totals['grand_total_all'] += $subtotal;
            }
        }

        return [$rows, $totals];
    }

    private function buildDetailRowsLegacy($tujuanList, $gajiPerTujuan): array
    {
        // Fallback agregat lama (tanpa per-hari) - dipertahankan untuk kompatibilitas
        $rows = [];
        $totals = ['total_solar_all' => 0, 'total_upah_all' => 0, 'total_dt_all' => 0, 'total_gagal_all' => 0, 'grand_total_all' => 0];
        return [$rows, $totals];
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
