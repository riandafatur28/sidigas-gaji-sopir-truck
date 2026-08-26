<?php

namespace App\Services;

use App\Models\Penggajian;
use App\Models\PenggajianDetail;
use App\Models\Periode;
use App\Models\Ritase;
use Illuminate\Http\Request;

class PenggajianService
{
    public function __construct(
        private readonly SlipBuilderService $slipBuilder,
        private readonly LaporanBuilderService $laporanBuilder,
        private readonly PenggajianDataService $dataService,
    ) {}

    // === Controller delegation ===

    public function getIndexData(Request $request): array
    {
        return $this->dataService->getIndexData($request);
    }

    public function getRitaseData(Request $request): array
    {
        return $this->dataService->getRitaseData($request);
    }

    public function getEditData($id): array
    {
        return $this->dataService->getEditData($id);
    }

    public function getRiwayatData(Request $request): array
    {
        return $this->dataService->getRiwayatData($request);
    }

    public function getSlipViewData($periodeId, $kodeSopir): array
    {
        $slip = $this->slipBuilder->buildSlipData($periodeId, $kodeSopir);
        if (!$slip || empty($slip['dataPerHari'])) {
            return [
                'periode' => Periode::findOrFail($periodeId),
                'sopir' => \App\Models\Sopir::where('kode_sopir', $kodeSopir)->firstOrFail(),
                'gaji' => null, 'dataPerHari' => [], 'detailTujuan' => collect(),
                'error' => 'Tidak ada data ritase untuk sopir ini pada periode tersebut',
            ];
        }
        return $slip;
    }

    public function getSlipPdfData($periodeId): array
    {
        return $this->slipBuilder->buildPeriodeSlipData($periodeId);
    }

    public function laporan(Request $request): array
    {
        return $this->laporanBuilder->build($request);
    }

    public function getLaporanData($periodeId): array
    {
        return $this->laporanBuilder->buildForPdf($periodeId);
    }

    // === CRUD operations ===

    public function storePenggajian($request): void
    {
        $map = $this->prepareDetailTujuanMap($request);
        $this->preparePenggajian($request->periode_id, $map);
        $this->processPenggajian($request->periode_id, $map);
    }

    public function updatePenggajian($request, $id): void
    {
        $this->storePenggajian($request);
    }

    // === Internal helpers ===

    public function prepareDetailTujuanMap($request): array
    {
        $map = [];
        foreach ($request->detail as $d) {
            $map[$d['kode_tujuan']] = [
                'bbm_per_rit' => floatval($d['bbm_per_rit']) ?: 0,
                'upah_per_rit' => floatval($d['upah_per_rit']) ?: 0,
                'tol_per_rit' => floatval($d['tol_per_rit'] ?? 0) ?: 0,
                'kompensasi_gagal' => floatval($d['kompensasi_gagal'] ?? 0) ?: 0,
                'lembur_per_rit' => floatval($d['lembur_per_rit'] ?? 0) ?: 0,
            ];
        }
        return $map;
    }

    private function preparePenggajian($periodeId, array $map): void
    {
        if (cache()->get('aturan_validasi_enabled', false)) {
            $this->validateBukti($periodeId);
        }

        Penggajian::where('periode_id', $periodeId)->delete();
        Ritase::where('periode_id', $periodeId)->update(['upah_sopir' => 0, 'nominal_kompensasi' => 0]);

        foreach ($map as $kt => $b) {
            if ($b['kompensasi_gagal'] > 0) {
                Ritase::where('periode_id', $periodeId)->where('kode_tujuan', $kt)
                    ->where('status', 'gagal_produksi')
                    ->update(['nominal_kompensasi' => $b['kompensasi_gagal']]);
            }
            Ritase::where('periode_id', $periodeId)->where('kode_tujuan', $kt)
                ->where('status', '!=', 'gagal_produksi')
                ->update(['is_lembur' => $b['lembur_per_rit'] > 0, 'upah_lembur' => $b['lembur_per_rit']]);
        }
    }

    private function validateBukti($periodeId): void
    {
        $ritaseList = Ritase::where('periode_id', $periodeId)->where('status', '!=', 'gagal_produksi')->get();
        foreach ($ritaseList as $rit) {
            $valid = \App\Models\ValidasiBukti::where('kode_sopir', $rit->kode_sopir)
                ->where('kode_tujuan', $rit->kode_tujuan)
                ->where('tanggal', $rit->tanggal)
                ->where('status', 'disetujui')->exists();
            if (!$valid) {
                throw new \Exception('Ritase ' . $rit->kode_ritase . ' belum memiliki bukti validasi disetujui.');
            }
        }
    }

    private function processPenggajian($periodeId, array $map): void
    {
        $ritCounts = Ritase::where('periode_id', $periodeId)->where('status', '!=', 'gagal_produksi')
            ->selectRaw('kode_sopir, kode_tujuan, COUNT(*) as total')
            ->groupBy('kode_sopir', 'kode_tujuan')->get();
        $ritDtSum = Ritase::where('periode_id', $periodeId)->where('status', '!=', 'gagal_produksi')
            ->selectRaw('kode_sopir, SUM(dt) as total')->groupBy('kode_sopir')->get()->keyBy('kode_sopir');
        $ritKompensasi = Ritase::where('periode_id', $periodeId)->where('status', 'gagal_produksi')
            ->selectRaw('kode_sopir, SUM(nominal_kompensasi) as total')->groupBy('kode_sopir')->get()->keyBy('kode_sopir');
        $ritLembur = Ritase::where('periode_id', $periodeId)->where('is_lembur', true)
            ->selectRaw('kode_sopir, SUM(upah_lembur) as total')->groupBy('kode_sopir')->get()->keyBy('kode_sopir');

        $countBySopir = $ritCounts->groupBy('kode_sopir');

        foreach ($map as $kt => $b) {
            Ritase::where('periode_id', $periodeId)->where('kode_tujuan', $kt)
                ->where('status', '!=', 'gagal_produksi')
                ->update(['upah_sopir' => $b['upah_per_rit']]);
        }

        $sopirs = \App\Models\Sopir::whereHas('ritase', fn($q) => $q->where('periode_id', $periodeId))->get();

        foreach ($sopirs as $sopir) {
            $counts = $countBySopir->get($sopir->kode_sopir, collect())->keyBy('kode_tujuan');
            [$totalSolar, $totalUpah, $totalTol, $totalSubtotal, $details] = [0, 0, 0, 0, []];

            foreach ($map as $kt => $b) {
                $rit = $counts->get($kt);
                $jml = $rit ? (int) $rit->total : 0;
                if ($jml <= 0) continue;

                $totalSolar += $b['bbm_per_rit'] * $jml;
                $totalUpah += $b['upah_per_rit'] * $jml;
                $totalTol += $b['tol_per_rit'] * $jml;
                $totalSubtotal += ($b['bbm_per_rit'] + $b['upah_per_rit']) * $jml;
                $details[] = ['kode_tujuan' => $kt, 'jumlah_rit' => $jml, 'bbm_per_rit' => $b['bbm_per_rit'], 'upah_per_rit' => $b['upah_per_rit'], 'tol_per_rit' => $b['tol_per_rit']];
            }

            $dt = (int) ($ritDtSum->get($sopir->kode_sopir)?->total ?? 0);
            $komp = (int) ($ritKompensasi->get($sopir->kode_sopir)?->total ?? 0);
            $lembur = (int) ($ritLembur->get($sopir->kode_sopir)?->total ?? 0);

            $gaji = Penggajian::create([
                'kode_sopir' => $sopir->kode_sopir, 'periode_id' => $periodeId, 'tanggal' => now(),
                'uang_solar' => $totalSolar, 'upah_sopir' => $totalUpah, 'dt' => $dt,
                'tol' => $totalTol, 'upah_lembur' => $lembur, 'kompensasi_gagal' => $komp,
                'total' => $totalSubtotal + $dt + $totalTol + $komp + $lembur,
            ]);

            foreach ($details as $dl) {
                PenggajianDetail::create([
                    'penggajian_id' => $gaji->id, 'kode_tujuan' => $dl['kode_tujuan'],
                    'jumlah_rit' => $dl['jumlah_rit'], 'solar_per_rit' => $dl['bbm_per_rit'],
                    'upah_per_rit' => $dl['upah_per_rit'],
                    'total_solar' => $dl['bbm_per_rit'] * $dl['jumlah_rit'],
                    'total_upah' => $dl['upah_per_rit'] * $dl['jumlah_rit'],
                    'sewa_dt' => 0, 'tol_per_rit' => $dl['tol_per_rit'],
                    'total_tol' => $dl['tol_per_rit'] * $dl['jumlah_rit'],
                    'subtotal' => ($dl['bbm_per_rit'] + $dl['upah_per_rit']) * $dl['jumlah_rit'],
                ]);
            }
        }
    }
}
