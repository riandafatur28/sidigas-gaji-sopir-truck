<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Periode;
use App\Models\PenggajianDetail;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RitaseService
{
    /**
     * Clean destination name for display.
     */
    public function cleanTujuan(?string $nama): string
    {
        return (new RitaseDetailService())->cleanTujuan($nama);
    }

    /**
     * Resolve upah per rit for manual ritase from PenggajianDetail rate.
     * Uses caching to avoid repeated queries.
     *
     * @param int $periodeId
     * @param string $kodeTujuan
     * @return float
     */
    public function resolveUpahSopir(int $periodeId, string $kodeTujuan): float
    {
        $cacheKey = "upah_sopir_{$periodeId}_{$kodeTujuan}";
        
        return Cache::remember($cacheKey, 60, function () use ($periodeId, $kodeTujuan) {
            $upah = PenggajianDetail::whereHas('penggajian', function ($q) use ($periodeId) {
                $q->where('periode_id', $periodeId);
            })
                ->where('kode_tujuan', $kodeTujuan)
                ->orderByDesc('id')
                ->value('upah_per_rit');

            if ($upah === null) {
                $upah = PenggajianDetail::where('kode_tujuan', $kodeTujuan)
                    ->orderByDesc('id')
                    ->value('upah_per_rit');
            }

            return (float) ($upah ?? 0);
        });
    }

    /**
     * Calculate DT based on business rules:
     * 1. Gagal Produksi → DT = 0
     * 2. Same regency, same time, same day → 1x DT for special regencies
     * 3. Otherwise → full DT value
     *
     * @param Request $request
     * @param int|null $excludeId
     * @return int
     */
    public function hitungDT(Request $request, ?int $excludeId = null): int
    {
        if ($request->status === 'gagal_produksi') {
            return 0;
        }

        $kabSatuDt = config('dt.single_dt_regencies');
        $dtValue = config('dt.value', 330000);

        $query = Ritase::where('kode_sopir', $request->kode_sopir)
            ->where('tanggal', $request->tanggal)
            ->where('kabupaten', $request->kabupaten)
            ->where('waktu', $request->waktu)
            ->where('status', '!=', 'gagal_produksi');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $ritLain = $query->first();

        if ($ritLain && in_array($request->kabupaten, $kabSatuDt)) {
            return 0;
        }

        return $dtValue;
    }

    /**
     * Check DT rental rules for display.
     *
     * @param Request $request
     * @return array
     */
    public function cekAturanSewaDT(Request $request): array
    {
        $kabupaten = $request->kabupaten;
        $waktu = $request->waktu;
        $status = $request->status;
        $kodeSopir = $request->kode_sopir;
        $tanggal = $request->tanggal;
        $nominalKompensasi = $request->nominal_kompensasi ?? 0;

        $dt = 0;
        $keterangan = '';
        $kabSatuDt = config('dt.single_dt_regencies');
        $dtValue = config('dt.value', 330000);

        if ($status === 'gagal_produksi') {
            $dt = 0;
            $keterangan = '❌ Gagal Produksi → Tidak dapat DT';
        } else {
            $ritLain = Ritase::where('kode_sopir', $kodeSopir)
                ->where('tanggal', $tanggal)
                ->where('kabupaten', $kabupaten)
                ->where('waktu', $waktu)
                ->where('status', '!=', 'gagal_produksi')
                ->first();

            if ($ritLain && in_array($kabupaten, $kabSatuDt)) {
                $dt = 0;
                $keterangan = "⚠️ Rit ke-2 kabupaten {$kabupaten} waktu {$waktu} → 0 DT (1x/hari)";
            } else {
                $dt = $dtValue;
                $dtFormatted = number_format($dtValue, 0, ',', '.');
                if ($ritLain) {
                    $keterangan = "✅ Rit ke-2 kabupaten {$kabupaten} waktu {$waktu} → DT Rp {$dtFormatted} (Lainnya/hitung 2x)";
                } else {
                    $keterangan = "✅ Rit pertama kabupaten {$kabupaten} waktu {$waktu} → DT Rp {$dtFormatted}";
                }
            }
        }

        // HITUNG RIT KE BERAPA
        $ritLain = Ritase::where('kode_sopir', $kodeSopir)
            ->where('tanggal', $tanggal)
            ->where('kabupaten', $kabupaten)
            ->where('waktu', $waktu)
            ->where('status', '!=', 'gagal_produksi')
            ->first();

        if ($ritLain) {
            $ritKeberapa = 2;
        } else {
            $totalRitHariIni = Ritase::where('kode_sopir', $kodeSopir)
                ->where('tanggal', $tanggal)
                ->where('status', '!=', 'gagal_produksi')
                ->count();
            $ritKeberapa = $totalRitHariIni + 1;
        }

        return [
            'rit_keberapa' => $ritKeberapa,
            'sewa_dt' => $dt,
            'keterangan' => $keterangan,
            'kompensasi' => $nominalKompensasi,
        ];
    }

    public function detailData(Request $request): array
    {
        return (new RitaseDetailService())->detailData($request);
    }

    public function detailPdf(Request $request): array
    {
        return (new RitaseDetailService())->detailPdf($request);
    }

    // =====================================================================
    // Controller delegation methods
    // =====================================================================

    public function getIndexData(Request $request): array
    {
        $search = $request->get('search', '');
        $filterPeriode = $request->get('periode', '');
        $filterSopir = $request->get('sopir', '');
        $filterTujuan = $request->get('tujuan', '');
        $tanggal = $request->get('tanggal', '');

        if (!$filterPeriode) {
            $active = Periode::where('status', 'aktif')->first();
            if ($active) $filterPeriode = $active->id;
        }

        $periodes = Periode::orderBy('id', 'asc')->get();
        $sopirs = Sopir::orderBy('id', 'asc')->get();
        $tujuans = Tujuan::orderBy('id', 'asc')->get();

        $ritBase = Ritase::with(['periode', 'sopir', 'tujuan']);
        if ($filterPeriode) $ritBase->where('periode_id', $filterPeriode);
        if ($tanggal) $ritBase->whereDate('tanggal', $tanggal);

        $ritases = (clone $ritBase)
            ->when($filterSopir, fn($q) => $q->where('kode_sopir', $filterSopir))
            ->when($filterTujuan, fn($q) => $q->where('kode_tujuan', $filterTujuan))
            ->when($search, fn($q) => $q->where(function ($q2) use ($search) {
                $q2->where('kode_ritase', 'like', "%{$search}%")
                    ->orWhereHas('sopir', fn($sq) => $sq->where('nama', 'like', "%{$search}%"))
                    ->orWhereHas('tujuan', fn($sq) => $sq->where('nama', 'like', "%{$search}%"));
            }))
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        $statBase = Ritase::query();
        if ($filterPeriode) $statBase->where('periode_id', $filterPeriode);
        if ($tanggal) $statBase->whereDate('tanggal', $tanggal);

        return compact(
            'ritases', 'periodes', 'sopirs', 'tujuans',
            'search', 'filterPeriode', 'filterSopir', 'filterTujuan', 'tanggal',
        ) + [
            'totalRitase' => (clone $statBase)->count(),
            'ritaseValid' => (clone $statBase)->where('status', 'valid')->count(),
            'ritasePending' => (clone $statBase)->where('status', 'pending')->count(),
            'ritaseGagal' => (clone $statBase)->where('status', 'gagal_produksi')->count(),
            'sopirTerlibat' => (clone $statBase)->distinct('kode_sopir')->count('kode_sopir'),
        ];
    }

    public function storeRitase($request): float
    {
        $validated = $request->validated();
        $validated['nominal_kompensasi'] = is_numeric($validated['nominal_kompensasi'] ?? 0) ? (float) $validated['nominal_kompensasi'] : 0;
        $isLembur = $request->boolean('is_lembur');
        $upahLembur = $isLembur ? (float) ($request->upah_lembur ?? 0) : 0;

        if (cache()->get('aturan_validasi_enabled', false)) {
            $validasi = \App\Models\ValidasiBukti::where('kode_sopir', $request->kode_sopir)
                ->where('tanggal', $request->tanggal)
                ->where('kode_tujuan', $request->kode_tujuan)
                ->where('status', 'disetujui')
                ->exists();
            if (!$validasi) {
                throw new \Exception('Sopir ini belum memiliki bukti validasi yang disetujui untuk tanggal dan tujuan ini.');
            }
        }

        Sopir::where('kode_sopir', $request->kode_sopir)->where('status', '!=', 'aktif')->update(['status' => 'aktif']);
        Tujuan::where('kode_tujuan', $request->kode_tujuan)->where('status', '!=', 'aktif')->update(['status' => 'aktif']);

        $dtValue = $this->hitungDT($request, null);

        Ritase::create([
            'periode_id' => $request->periode_id,
            'kode_sopir' => $request->kode_sopir,
            'kode_tujuan' => $request->kode_tujuan,
            'tanggal' => $request->tanggal,
            'waktu' => $request->waktu,
            'kabupaten' => $request->kabupaten,
            'status' => $request->status,
            'dt' => $dtValue,
            'upah_sopir' => $this->resolveUpahSopir($request->periode_id, $request->kode_tujuan),
            'nominal_kompensasi' => $validated['nominal_kompensasi'],
            'catatan' => $request->catatan,
            'is_lembur' => $isLembur,
            'upah_lembur' => $upahLembur,
        ]);

        return $dtValue;
    }

    public function updateRitase($request, $id): float
    {
        $validated = $request->validated();
        $validated['nominal_kompensasi'] = is_numeric($validated['nominal_kompensasi'] ?? 0) ? (float) $validated['nominal_kompensasi'] : 0;
        $isLembur = $request->boolean('is_lembur');
        $upahLembur = $isLembur ? (float) ($request->upah_lembur ?? 0) : 0;

        Sopir::where('kode_sopir', $request->kode_sopir)->where('status', '!=', 'aktif')->update(['status' => 'aktif']);
        Tujuan::where('kode_tujuan', $request->kode_tujuan)->where('status', '!=', 'aktif')->update(['status' => 'aktif']);

        $ritase = Ritase::findOrFail($id);
        $dtValue = $this->hitungDT($request, $id);

        $ritase->update([
            'periode_id' => $request->periode_id,
            'kode_sopir' => $request->kode_sopir,
            'kode_tujuan' => $request->kode_tujuan,
            'tanggal' => $request->tanggal,
            'waktu' => $request->waktu,
            'kabupaten' => $request->kabupaten,
            'status' => $request->status,
            'dt' => $dtValue,
            'nominal_kompensasi' => $validated['nominal_kompensasi'],
            'catatan' => $request->catatan,
            'is_lembur' => $isLembur,
            'upah_lembur' => $upahLembur,
        ]);

        return $dtValue;
    }

    public function getParserFormData(): array
    {
        return [
            'periodes' => Periode::orderBy('id', 'desc')->get(),
            'activePeriode' => Periode::where('status', 'aktif')->first(),
        ];
    }

    public function processParser(Request $request): array
    {
        $parser = new \App\Services\RitaseParserService();
        $parsed = $parser->parse($request->text);

        if (empty($parsed['date'])) {
            throw new \Exception('Tanggal tidak terdeteksi. Format: DD MM YY hari');
        }

        $driverMatches = $parser->matchDrivers(
            collect($parsed['packages'])->pluck('drivers')->flatten()->unique()->values()->all()
        );
        $routeMatches = $parser->matchRoutes(
            collect($parsed['packages'])->pluck('route_name')->unique()->values()->all()
        );

        $results = [
            'date' => $parsed['date'], 'packages' => $parsed['packages'],
            'driver_matches' => $driverMatches, 'route_matches' => $routeMatches,
            'created' => 0, 'skipped' => 0, 'errors' => [], 'details' => [],
        ];

        if ($request->boolean('auto_create')) {
            $createResult = $parser->createRitases($parsed, $request->periode_id, $driverMatches, $routeMatches);
            $results['created'] = $createResult['created'];
            $results['skipped'] = $createResult['skipped'];
            $results['errors'] = array_merge($results['errors'], $createResult['errors']);
            $results['details'] = $createResult['details'];
        }

        return $results;
    }
}
