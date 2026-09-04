<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Penggajian;
use App\Models\PenggajianDetail;
use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;

class SlipBuilderService
{
    /**
     * Build slip data untuk 1 sopir di 1 periode.
     */
    public function buildSlipData($periodeId, $kodeSopir): ?array
    {
        $periode = Periode::findOrFail($periodeId);
        $sopir = Sopir::where('kode_sopir', $kodeSopir)->first();
        if (!$sopir) return null;

        $gaji = Penggajian::with('details.tujuan')
            ->where('periode_id', $periodeId)
            ->where('kode_sopir', $kodeSopir)
            ->first();

        $detailTujuan = $gaji
            ? $gaji->details
            : PenggajianDetail::whereHas('penggajian', fn($q) => $q->where('periode_id', $periodeId))->get();

        $periodRates = $this->getPeriodRates($periodeId);
        $ritasePerHari = $this->getRitasePerHari($periodeId, $kodeSopir);
        $hariList = $this->getHariList($periode);
        $namaHari = self::NAMA_HARI;

        $dataPerHari = [];
        foreach ($hariList as $tanggal) {
            $ritHari = $ritasePerHari->get($tanggal, collect());
            foreach ($ritHari as $ritIndex => $rit) {
                $hari = $namaHari[\Carbon\Carbon::parse($tanggal)->format('l')] ?? \Carbon\Carbon::parse($tanggal)->format('l');
                $detail = $detailTujuan->first(fn($d) => $d->kode_tujuan === $rit->kode_tujuan);
                $isGagal = $rit->status === 'gagal_produksi';

                [$solar, $upah, $tol] = $isGagal ? [0, 0, 0] : $this->slipRitRates($periodeId, $rit, $detail, $periodRates);
                $kompensasi = $isGagal ? ($rit->nominal_kompensasi ?? 0) : 0;

                $solarF = (float) $solar;
                $upahF = (float) $upah;
                $tolF = (float) $tol;
                $kompF = (float) $kompensasi;
                $lemburF = (float) ($rit->upah_lembur ?? 0);

                $dataPerHari[] = [
                    'tanggal' => $tanggal,
                    'hari' => $hari,
                    'rit_ke' => $ritIndex + 1,
                    'total_rit_hari' => $ritHari->count(),
                    'solar' => round($solarF),
                    'upah' => round($upahF),
                    'jumlah' => $isGagal ? round($kompF) : round($solarF + $upahF + $lemburF),
                    'tujuan' => $this->getTujuanNama($detail, $rit),
                    'kode_tujuan' => $rit->kode_tujuan,
                    'is_gagal' => $isGagal,
                    'is_lembur' => $rit->is_lembur ?? false,
                    'upah_lembur' => $lemburF,
                    'dt' => $isGagal ? 0 : (floatval($rit->dt) ?? 0),
                    'tol' => $isGagal ? 0 : round($tolF),
                ];
            }
        }

        $dataPerHari = $this->mergeDuplicateRitEntries($dataPerHari);

        return [
            'sopir' => $sopir,
            'gaji' => $gaji,
            'dataPerHari' => $dataPerHari,
            'totalSolarAll' => array_sum(array_column($dataPerHari, 'solar')),
            'totalUpahAll' => array_sum(array_column($dataPerHari, 'upah')),
            'totalJumlahAll' => array_sum(array_column($dataPerHari, 'jumlah')),
            'totalDTAll' => array_sum(array_column($dataPerHari, 'dt')),
            'totalTolAll' => array_sum(array_column($dataPerHari, 'tol')),
            'totalKompensasiAll' => $gaji ? ($gaji->kompensasi_gagal ?? 0) : 0,
            'grandTotal' => array_sum(array_column($dataPerHari, 'jumlah')) + array_sum(array_column($dataPerHari, 'dt')) + array_sum(array_column($dataPerHari, 'tol')),
        ];
    }

    /**
     * Build slip data untuk semua sopir di 1 periode (PDF export).
     */
    public function buildPeriodeSlipData($periodeId): array
    {
        $periode = Periode::findOrFail($periodeId);
        $sopirIds = $this->getAllSopirIds($periodeId);

        $allSlips = [];
        foreach ($sopirIds as $kodeSopir) {
            $slip = $this->buildSlipData($periodeId, $kodeSopir);
            if ($slip && count($slip['dataPerHari']) > 0) $allSlips[] = $slip;
        }
        usort($allSlips, fn($a, $b) => $a['sopir']->id <=> $b['sopir']->id);

        $dateHeaders = $this->buildDateHeaders($periode);
        $organizedSlips = $this->organizeSlips($allSlips);
        $slipEntries = $this->buildSlipEntries($organizedSlips);
        $sopirPerPages = collect($slipEntries)->chunk(4)->map->values()->toArray();

        return compact('sopirPerPages', 'dateHeaders', 'periode');
    }

    /**
     * Hitung solar/upah/tol per rit.
     */
    public function slipRitRates($periodeId, $rit, $detail, $periodRates): array
    {
        if ($detail) return $this->extractRatesFromDetail($detail, $rit);

        $rate = $periodRates->get($rit->kode_tujuan);
        if (!$rate) return [0, $rit->upah_sopir ?? 0, 0];

        $jml = $rate->jumlah_rit ?? 1;
        return [
            $jml > 0 ? ($rate->solar_per_rit ?? $rate->total_solar / $jml) : 0,
            $jml > 0 ? ($rate->upah_per_rit ?? $rate->total_upah / $jml) : ($rit->upah_sopir ?? 0),
            isset($rate->tol_per_rit) ? ($jml > 0 ? ($rate->tol_per_rit ?? $rate->total_tol / $jml) : 0) : 0,
        ];
    }

    /**
     * Gabungkan rit entries yang sama hari + sama tujuan.
     */
    public function mergeDuplicateRitEntries(array $dataPerHari): array
    {
        if (count($dataPerHari) <= 1) return $dataPerHari;

        [$nonGagal, $gagal] = [[], []];
        foreach ($dataPerHari as $e) {
            $e['is_gagal'] ? $gagal[] = $e : $nonGagal[] = $e;
        }

        $groups = [];
        foreach ($nonGagal as $e) {
            $groups[$e['tanggal'] . '|' . ($e['kode_tujuan'] ?? $e['tujuan'])][] = $e;
        }

        $merged = [];
        foreach ($groups as $entries) {
            if (count($entries) === 1) {
                $entries[0]['rit_count'] = 1;
                $merged[] = $entries[0];
                continue;
            }
            $base = $entries[0];
            $count = count($entries);
            $base['solar'] *= $count;
            $base['upah'] *= $count;
            $base['upah_lembur'] *= $count;
            $base['jumlah'] *= $count;
            $base['tol'] *= $count;
            $base['dt'] = array_sum(array_column($entries, 'dt'));
            $base['tujuan'] = $count . 'x ' . $base['tujuan'];
            $base['rit_count'] = $count;
            $merged[] = $base;
        }

        foreach ($gagal as $g) {
            $g['rit_count'] = 1;
            $merged[] = $g;
        }

        usort($merged, fn($a, $b) => $a['tanggal'] <=> $b['tanggal'] ?: $a['rit_ke'] <=> $b['rit_ke']);

        $dateGroups = [];
        foreach ($merged as $e) $dateGroups[$e['tanggal']][] = $e;

        $result = [];
        foreach ($dateGroups as $tanggal => $entries) {
            $total = count($entries);
            foreach ($entries as $idx => $e) {
                $e['rit_ke'] = $idx + 1;
                $e['total_rit_hari'] = $total;
                $result[] = $e;
            }
        }
        return $result;
    }

    // === Private helpers ===

    private const NAMA_HARI = [
        'Saturday' => 'Sabtu', 'Sunday' => 'Minggu', 'Monday' => 'Senin',
        'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis',
        'Friday' => "Jum'at",
    ];

    private function getPeriodRates($periodeId)
    {
        return PenggajianDetail::whereHas('penggajian', fn($q) => $q->where('periode_id', $periodeId))
            ->orderBy('id', 'desc')->get()
            ->groupBy('kode_tujuan')
            ->map(fn($items) => $items->first());
    }

    private function getRitasePerHari($periodeId, $kodeSopir)
    {
        return Ritase::where('periode_id', $periodeId)
            ->where('kode_sopir', $kodeSopir)
            ->orderBy('tanggal', 'asc')
            ->get()
            ->groupBy(fn($r) => $r->tanggal instanceof \Carbon\Carbon ? $r->tanggal->format('Y-m-d') : $r->tanggal);
    }

    private function getHariList(Periode $periode): array
    {
        $start = \Carbon\Carbon::parse($periode->tanggal_mulai);
        $end = \Carbon\Carbon::parse($periode->tanggal_selesai);
        $list = [];
        for ($d = $start->copy(); $d <= $end; $d->addDay()) $list[] = $d->format('Y-m-d');
        return $list;
    }

    private function getTujuanNama($detail, $rit): string
    {
        if ($detail && $detail->tujuan) return $detail->tujuan->nama;
        if ($rit->tujuan) return $rit->tujuan->nama;
        return $rit->kode_tujuan;
    }

    private function extractRatesFromDetail($detail, $rit): array
    {
        $jml = $detail->jumlah_rit ?? 1;
        return [
            $jml > 0 ? ($detail->solar_per_rit ?? $detail->total_solar / $jml) : 0,
            $jml > 0 ? ($detail->upah_per_rit ?? $detail->total_upah / $jml) : ($rit->upah_sopir ?? 0),
            isset($detail->tol_per_rit) ? ($jml > 0 ? ($detail->tol_per_rit ?? $detail->total_tol / $jml) : 0) : 0,
        ];
    }

    private function getAllSopirIds($periodeId)
    {
        $gajiSopir = Penggajian::where('periode_id', $periodeId)->pluck('kode_sopir');
        $ritSopir = Ritase::where('periode_id', $periodeId)->whereNotIn('kode_sopir', $gajiSopir)->pluck('kode_sopir');
        return $gajiSopir->concat($ritSopir)->unique()->values();
    }

    private function buildDateHeaders(Periode $periode): array
    {
        $start = \Carbon\Carbon::parse($periode->tanggal_mulai);
        $end = \Carbon\Carbon::parse($periode->tanggal_selesai);
        $headers = [];
        for ($d = $start->copy(); $d <= $end; $d->addDay()) {
            $headers[] = [
                'label' => self::NAMA_HARI[$d->format('l')] ?? $d->format('l'),
                'date' => $d->format('d/m'),
                'tanggal' => $d->format('Y-m-d'),
            ];
        }
        return $headers;
    }

    private function organizeSlips(array $allSlips): array
    {
        $organized = [];
        foreach ($allSlips as $slip) {
            $ritMap = [];
            foreach ($slip['dataPerHari'] as $e) $ritMap[$e['tanggal']][$e['rit_ke']] = $e;

            $totalRitValid = 0;
            foreach ($slip['dataPerHari'] as $e) {
                if (!$e['is_gagal']) $totalRitValid += $e['rit_count'] ?? 1;
            }

            $organized[] = [
                'sopir' => $slip['sopir'], 'ritMap' => $ritMap,
                'totalSolarAll' => $slip['totalSolarAll'], 'totalUpahAll' => $slip['totalUpahAll'],
                'totalJumlahAll' => $slip['totalJumlahAll'], 'totalDTAll' => $slip['totalDTAll'],
                'totalTolAll' => $slip['totalTolAll'] ?? 0,
                'potonganOperasional' => $totalRitValid * 20000,
                'grandTotal' => $slip['totalJumlahAll'] + $slip['totalDTAll'] + ($slip['totalTolAll'] ?? 0),
            ];
        }
        return $organized;
    }

    private function buildSlipEntries(array $organizedSlips): array
    {
        $entries = [];
        foreach ($organizedSlips as $slip) {
            $allRitKeys = [];
            foreach ($slip['ritMap'] as $rits) foreach ($rits as $k => $_) $allRitKeys[$k] = true;
            $allRitKeys = array_keys($allRitKeys);
            sort($allRitKeys);

            foreach ($allRitKeys as $rit) {
                $totals = ['solar' => 0, 'upah' => 0, 'jumlah' => 0, 'dt' => 0, 'tol' => 0];
                foreach ($slip['ritMap'] as $rits) {
                    if (isset($rits[$rit])) {
                        foreach ($totals as $k => $_) $totals[$k] += $rits[$rit][$k];
                    }
                }
                $entries[] = [
                    'sopir' => $slip['sopir'], 'ritMap' => $slip['ritMap'], 'ritKe' => $rit,
                    'totalSolarAll' => $totals['solar'], 'totalUpahAll' => $totals['upah'],
                    'totalJumlahAll' => $totals['jumlah'], 'totalDTAll' => $totals['dt'],
                    'totalTolAll' => $totals['tol'], 'potonganOperasional' => $slip['potonganOperasional'],
                    'grandTotal' => $totals['jumlah'] + $totals['dt'] + $totals['tol'],
                ];
            }
        }
        usort($entries, fn($a, $b) => $a['ritKe'] <=> $b['ritKe'] ?: $a['sopir']->id <=> $b['sopir']->id);
        return $entries;
    }
}
