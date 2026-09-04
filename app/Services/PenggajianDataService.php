<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Penggajian;
use App\Models\PenggajianDetail;
use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use Illuminate\Http\Request;

class PenggajianDataService
{
    /**
     * Index page — ringkasan semua periode + tujuan list.
     */
    public function getIndexData(Request $request): array
    {
        $periodeId = $this->resolvePeriodeId($request->get('periode'));
        $allPeriodes = Periode::orderBy('id', 'desc')->get();
        $periodeIds = $allPeriodes->pluck('id');

        $ritSummary = $this->getRitaseSummary($periodeIds);
        $gajiSummary = $this->getGajiSummary($periodeIds);

        $periodes = $allPeriodes->map(fn($p) => $this->mapPeriodeSummary($p, $ritSummary, $gajiSummary));
        $allTujuans = $periodeId
            ? Tujuan::whereIn('kode_tujuan', Ritase::where('periode_id', $periodeId)->distinct()->pluck('kode_tujuan'))->orderBy('id')->get()
            : Tujuan::where('status', 'aktif')->orderBy('id')->get();

        return compact('periodes', 'allTujuans', 'periodeId') + ['periodesForDropdown' => Periode::all()];
    }

    /**
     * Ritase data untuk penggajian page (API endpoint).
     */
    public function getRitaseData(Request $request): array
    {
        try {
            $periodeId = $this->resolvePeriodeFromRequest($request);
            if (!$periodeId) return ['error' => 'Parameter tidak lengkap'];

            $tanggal = $request->get('tanggal');
            $search = trim($request->get('search', ''));
            $ritBase = $this->buildRitaseQuery($periodeId, $tanggal, $search);

            [$ritCounts, $validCounts, $dtSums, $lemburSums, $gagalRits] = $this->getRitaseAggregates($ritBase, $tanggal);
            [$sopirs, $tujuanCodes] = $this->getSopirAndTujuan($ritBase);
            $penggajianData = Penggajian::with(['sopir', 'details'])->where('periode_id', $periodeId)->get()->keyBy('kode_sopir');

            $defaultRates = $this->getDefaultRates($periodeId, $penggajianData);
            $this->addKompensasiRates($defaultRates, $ritBase);
            $this->addLemburRates($defaultRates, $ritBase);

            $result = $this->buildRitaseResult($sopirs, $tujuanCodes, $ritCounts, $validCounts, $dtSums, $lemburSums, $gagalRits, $penggajianData, $defaultRates, $periodeId);

            return ['sopir' => $result, 'default_rates' => $defaultRates, 'detected_periode_id' => $periodeId];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Edit page data.
     */
    public function getEditData($id): array
    {
        $periode = Periode::findOrFail($id);
        $tujuanCodes = Ritase::where('periode_id', $id)->whereNotNull('kode_tujuan')->distinct()->pluck('kode_tujuan');
        $allTujuans = Tujuan::whereIn('kode_tujuan', $tujuanCodes)->orderBy('id')->get();
        $existingGaji = Penggajian::with(['details', 'sopir'])->where('periode_id', $id)->get()->keyBy('kode_sopir');

        $detailPerTujuan = [];
        foreach ($existingGaji as $gaji) {
            foreach ($gaji->details as $d) {
                if (!isset($detailPerTujuan[$d->kode_tujuan])) {
                    $detailPerTujuan[$d->kode_tujuan] = [
                        'bbm_per_rit' => $d->solar_per_rit, 'upah_per_rit' => $d->upah_per_rit,
                        'tol_per_rit' => $d->tol_per_rit ?? 0, 'kompensasi_gagal' => 0, 'lembur_per_rit' => 0,
                    ];
                }
            }
        }

        $lembur = Ritase::where('periode_id', $id)->where('is_lembur', true)
            ->selectRaw('kode_tujuan, MAX(upah_lembur) as lembur_per_rit')
            ->groupBy('kode_tujuan')->pluck('lembur_per_rit', 'kode_tujuan');
        foreach ($detailPerTujuan as $kt => &$data) {
            if (isset($lembur[$kt])) $data['lembur_per_rit'] = (float) $lembur[$kt];
        }

        $kompensasi = Ritase::where('periode_id', $id)->where('status', 'gagal_produksi')
            ->selectRaw('kode_tujuan, SUM(nominal_kompensasi) as total')
            ->groupBy('kode_tujuan')->pluck('total', 'kode_tujuan');
        foreach ($detailPerTujuan as $kt => &$data) {
            $data['kompensasi_gagal'] = floatval($kompensasi[$kt] ?? 0);
        }

        return compact('periode', 'allTujuans', 'existingGaji', 'detailPerTujuan');
    }

    /**
     * Riwayat page data.
     */
    public function getRiwayatData(Request $request): array
    {
        $sort = $request->get('sort', 'terbaru');
        $bulan = $request->get('bulan');
        $tahun = $request->get('tahun');

        $query = Periode::query();
        if ($bulan) $query->whereMonth('tanggal_mulai', $bulan);
        if ($tahun && !$bulan) $query->whereYear('tanggal_mulai', $tahun);
        if ($tahun && $bulan) $query->whereYear('tanggal_mulai', $tahun);
        $query->orderBy('tanggal_mulai', $sort === 'terlama' ? 'asc' : 'desc');

        $paginated = $query->paginate(10)->withQueryString();
        $periodeIds = $paginated->pluck('id');

        $ritSummary = $this->getRitaseSummary($periodeIds);
        $gajiSummary = $this->getGajiSummary($periodeIds);
        $years = Periode::selectRaw('YEAR(tanggal_mulai) as tahun')->distinct()->orderBy('tahun', 'desc')->pluck('tahun');

        $periodes = $paginated->getCollection()->map(fn($p) => $this->mapRiwayatPeriode($p, $ritSummary, $gajiSummary));
        $paginated->setCollection($periodes);

        return ['periodes' => $paginated, 'sort' => $sort, 'bulan' => $bulan, 'tahun' => $tahun, 'availableYears' => $years];
    }

    // === Private helpers ===

    private function resolvePeriodeId($periodeId): ?int
    {
        if ($periodeId) return (int) $periodeId;
        $latest = Periode::orderBy('id', 'desc')->first();
        return $latest?->id;
    }

    private function resolvePeriodeFromRequest(Request $request): ?int
    {
        $periodeId = $request->get('periode');
        $tanggal = $request->get('tanggal');
        if ($tanggal) {
            $found = Periode::whereDate('tanggal_mulai', '<=', $tanggal)->whereDate('tanggal_selesai', '>=', $tanggal)->first();
            if ($found) $periodeId = $found->id;
        }
        return $periodeId ? (int) $periodeId : null;
    }

    private function getRitaseSummary($periodeIds)
    {
        return Ritase::whereIn('periode_id', $periodeIds)->where('status', '!=', 'gagal_produksi')
            ->selectRaw('periode_id, SUM(upah_sopir) as total_upah, SUM(dt) as total_dt, COUNT(*) as total_rit')
            ->groupBy('periode_id')->get()->keyBy('periode_id');
    }

    private function getGajiSummary($periodeIds)
    {
        return Penggajian::whereIn('periode_id', $periodeIds)
            ->selectRaw('periode_id, SUM(uang_solar) as total_solar, SUM(upah_sopir) as total_upah, SUM(dt) as total_dt, SUM(total) as grand_total, COUNT(*) as gaji_count')
            ->groupBy('periode_id')->get()->keyBy('periode_id');
    }

    private function mapPeriodeSummary($periode, $ritSummary, $gajiSummary): array
    {
        $rit = $ritSummary->get($periode->id);
        $gaji = $gajiSummary->get($periode->id);
        $has = $gaji && $gaji->gaji_count > 0;
        return [
            'id' => $periode->id, 'nama_periode' => $periode->nama_periode,
            'total_ritase' => $rit->total_rit ?? 0,
            'total_solar' => $has ? floatval($gaji->total_solar) : 0,
            'total_sopir' => $has ? floatval($gaji->total_upah) : floatval($rit->total_upah ?? 0),
            'total_dt' => $has ? floatval($gaji->total_dt) : floatval($rit->total_dt ?? 0),
            'grand_total' => $has ? floatval($gaji->grand_total) : (floatval($rit->total_upah ?? 0) + floatval($rit->total_dt ?? 0)),
        ];
    }

    private function mapRiwayatPeriode($periode, $ritSummary, $gajiSummary): array
    {
        $rit = $ritSummary->get($periode->id);
        $gaji = $gajiSummary->get($periode->id);
        $has = $gaji && $gaji->gaji_count > 0;
        $ritUpah = floatval($rit->total_upah ?? 0);
        $ritDt = floatval($rit->total_dt ?? 0);

        $jumlahSopir = $has
            ? Penggajian::where('periode_id', $periode->id)->distinct('kode_sopir')->count('kode_sopir')
            : Ritase::where('periode_id', $periode->id)->distinct('kode_sopir')->count('kode_sopir');

        return [
            'id' => $periode->id, 'nama_periode' => $periode->nama_periode,
            'tanggal_mulai' => $periode->tanggal_mulai, 'tanggal_selesai' => $periode->tanggal_selesai,
            'total_ritase' => $rit->total_rit ?? 0,
            'total_solar' => $has ? floatval($gaji->total_solar) : 0,
            'total_upah' => $has ? floatval($gaji->total_upah) : $ritUpah,
            'total_dt' => $has ? floatval($gaji->total_dt) : $ritDt,
            'total_kompensasi' => $has ? floatval($gaji->total_kompensasi ?? 0) : 0,
            'grand_total' => $has ? floatval($gaji->grand_total) : ($ritUpah + $ritDt),
            'jumlah_sopir' => $jumlahSopir,
        ];
    }

    private function buildRitaseQuery($periodeId, $tanggal, $search)
    {
        $q = Ritase::where('periode_id', $periodeId);
        if ($tanggal) $q->whereDate('tanggal', $tanggal);
        if ($search !== '') {
            $sopirCodes = Sopir::where('nama', 'like', "%{$search}%")->pluck('kode_sopir');
            $tujuanCodes = Tujuan::where('nama', 'like', "%{$search}%")->pluck('kode_tujuan');
            $q->where(fn($q2) => $q2->whereIn('kode_sopir', $sopirCodes)->orWhereIn('kode_tujuan', $tujuanCodes));
        }
        return $q;
    }

    private function getRitaseAggregates($ritBase, $tanggal): array
    {
        $counts = (clone $ritBase)->selectRaw('kode_sopir, kode_tujuan, COUNT(*) as total')
            ->groupBy('kode_sopir', 'kode_tujuan')->get()->keyBy(fn($r) => $r->kode_sopir.'|'.$r->kode_tujuan);
        $valid = (clone $ritBase)->where('status', '!=', 'gagal_produksi')
            ->selectRaw('kode_sopir, kode_tujuan, COUNT(*) as total')
            ->groupBy('kode_sopir', 'kode_tujuan')->get()->keyBy(fn($r) => $r->kode_sopir.'|'.$r->kode_tujuan);
        $dt = (clone $ritBase)->where('status', '!=', 'gagal_produksi')
            ->selectRaw('kode_sopir, SUM(dt) as total_dt')
            ->groupBy('kode_sopir')->get()->keyBy('kode_sopir');
        $lembur = (clone $ritBase)->where('is_lembur', true)
            ->selectRaw('kode_sopir, SUM(upah_lembur) as total_lembur')
            ->groupBy('kode_sopir')->get()->keyBy('kode_sopir');
        $gagal = (clone $ritBase)->where('status', 'gagal_produksi')->orderBy('tanggal')
            ->get(['id', 'kode_sopir', 'tanggal', 'kode_tujuan'])->groupBy('kode_sopir');

        return [$counts, $valid, $dt, $lembur, $gagal];
    }

    private function getSopirAndTujuan($ritBase): array
    {
        $sopirCodes = (clone $ritBase)->distinct()->pluck('kode_sopir');
        $sopirs = Sopir::whereIn('kode_sopir', $sopirCodes)->orderBy('id')->get()->keyBy('kode_sopir');
        $tujuanCodes = (clone $ritBase)->whereNotNull('kode_tujuan')->distinct()->pluck('kode_tujuan');
        return [$sopirs, $tujuanCodes];
    }

    private function getDefaultRates($periodeId, $penggajianData): array
    {
        $rates = [];
        $refId = $penggajianData->isNotEmpty() ? $periodeId : Penggajian::where('periode_id', '<', $periodeId)->max('periode_id');
        if (!$refId) return $rates;

        $rows = PenggajianDetail::whereHas('penggajian', fn($q) => $q->where('periode_id', $refId))
            ->selectRaw('kode_tujuan, AVG(solar_per_rit) as bbm, AVG(upah_per_rit) as upah, AVG(tol_per_rit) as tol')
            ->groupBy('kode_tujuan')->get();
        foreach ($rows as $r) {
            $rates[$r->kode_tujuan] = ['bbm_per_rit' => floatval($r->bbm), 'upah_per_rit' => floatval($r->upah), 'tol_per_rit' => floatval($r->tol)];
        }
        return $rates;
    }

    private function addKompensasiRates(array &$rates, $ritBase): void
    {
        $komp = (clone $ritBase)->where('status', 'gagal_produksi')
            ->selectRaw('kode_tujuan, MAX(nominal_kompensasi) as komp')
            ->groupBy('kode_tujuan')->pluck('komp', 'kode_tujuan');
        foreach ($komp as $kt => $v) {
            $rates[$kt]['kompensasi_gagal'] = floatval($v);
            if (!isset($rates[$kt])) $rates[$kt] = ['bbm_per_rit' => 0, 'upah_per_rit' => 0];
        }
    }

    private function addLemburRates(array &$rates, $ritBase): void
    {
        $lembur = (clone $ritBase)->where('is_lembur', true)
            ->selectRaw('kode_tujuan, MAX(upah_lembur) as lembur')
            ->groupBy('kode_tujuan')->pluck('lembur', 'kode_tujuan');
        foreach ($lembur as $kt => $v) {
            $rates[$kt]['lembur_per_rit'] = floatval($v);
            if (!isset($rates[$kt])) $rates[$kt] = ['bbm_per_rit' => 0, 'upah_per_rit' => 0];
        }
    }

    private function buildRitaseResult($sopirs, $tujuanCodes, $ritCounts, $validCounts, $dtSums, $lemburSums, $gagalRits, $penggajianData, $defaultRates, $periodeId): array
    {
        $result = [];
        foreach ($sopirs->keys() as $kodeSopir) {
            $sopir = $sopirs->get($kodeSopir);
            $gaji = $penggajianData->get($kodeSopir);
            $ritPerTujuan = [];

            foreach ($tujuanCodes as $kt) {
                $key = $kodeSopir.'|'.$kt;
                $total = isset($ritCounts[$key]) ? (int) $ritCounts[$key]->total : 0;
                $valid = isset($validCounts[$key]) ? (int) $validCounts[$key]->total : 0;
                if ($total === 0) continue;

                $detail = $gaji?->details->firstWhere('kode_tujuan', $kt);
                $rate = $defaultRates[$kt] ?? null;
                $ritPerTujuan[$kt] = [
                    'total_rit' => $total, 'total_rit_valid' => $valid,
                    'solar_per_rit' => $detail?->solar_per_rit ? (float) $detail->solar_per_rit : ($rate['bbm_per_rit'] ?? 0),
                    'upah_per_rit' => $detail?->upah_per_rit ? (float) $detail->upah_per_rit : ($rate['upah_per_rit'] ?? 0),
                    'tol_per_rit' => $detail?->tol_per_rit ? (float) $detail->tol_per_rit : ($rate['tol_per_rit'] ?? 0),
                    'total_solar' => $detail?->total_solar ? (float) $detail->total_solar : (($rate['bbm_per_rit'] ?? 0) * $valid),
                    'total_upah' => $detail?->total_upah ? (float) $detail->total_upah : (($rate['upah_per_rit'] ?? 0) * $valid),
                    'total_tol' => $detail?->total_tol ? (float) $detail->total_tol : (($rate['tol_per_rit'] ?? 0) * $valid),
                    'subtotal' => $detail?->subtotal ? (float) $detail->subtotal : (($rate['bbm_per_rit'] ?? 0) + ($rate['upah_per_rit'] ?? 0)) * $valid,
                ];
            }

            if (empty($ritPerTujuan)) continue;

            $totalDT = floatval($dtSums[$kodeSopir]->total_dt ?? 0);
            $upahLembur = $gaji ? (float) $gaji->upah_lembur : (float) ($lemburSums->get($kodeSopir)?->total_lembur ?? 0);
            $gagalCollection = $gagalRits->get($kodeSopir, collect())->map(fn($r) => [
                'id' => $r->id,
                'tanggal' => $r->tanggal instanceof \Carbon\Carbon ? $r->tanggal->format('Y-m-d') : $r->tanggal,
                'kode_tujuan' => $r->kode_tujuan,
            ])->values()->toArray();

            $solar = array_sum(array_column($ritPerTujuan, 'total_solar'));
            $upah = array_sum(array_column($ritPerTujuan, 'total_upah'));
            $tol = $gaji ? (float) $gaji->tol : 0;

            $result[] = [
                'kode_sopir' => $kodeSopir, 'nama_sopir' => $sopir->nama, 'periode_id' => $periodeId,
                'total_dt' => $totalDT, 'total_tol' => $tol,
                'total_kompensasi' => $gaji ? (float) $gaji->kompensasi_gagal : 0,
                'total_solar' => $solar, 'total_upah' => $upah, 'upah_lembur' => $upahLembur,
                'grand_total' => $solar + $upah + $totalDT + $tol + $upahLembur,
                'rit_per_tujuan' => $ritPerTujuan, 'gagal_rits' => $gagalCollection,
                'belum_dihitung' => !$gaji,
            ];
        }
        return $result;
    }
}
