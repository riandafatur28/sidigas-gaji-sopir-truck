<?php

namespace App\Http\Controllers;

use App\Models\Penggajian;
use App\Models\PenggajianDetail;
use App\Models\Periode;
use App\Models\Tujuan;
use App\Models\Sopir;
use App\Models\Ritase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenggajianController extends Controller
{
    public function index(Request $request)
    {
        // Auto-sync: periode yg mencakup hari ini jadi aktif, lainnya selesai
        Periode::syncActiveStatus();

        $periodeId = $request->get('periode');

        // Default ke periode aktif kalo gak pilih
        if (!$periodeId) {
            $active = Periode::where('status', 'aktif')->first();
            if ($active) $periodeId = $active->id;
        }

        $allPeriodes = Periode::orderBy('id', 'desc')->get();
        $periodeIds = $allPeriodes->pluck('id');

        $ritaseSummary = Ritase::whereIn('periode_id', $periodeIds)
            ->where('status', '!=', 'gagal_produksi')
            ->selectRaw('periode_id, SUM(upah_sopir) as total_upah_rit, SUM(dt) as total_dt_rit, COUNT(*) as total_rit_rit')
            ->groupBy('periode_id')
            ->get()
            ->keyBy('periode_id');

        $gajiSummary = Penggajian::whereIn('periode_id', $periodeIds)
            ->selectRaw('periode_id, SUM(uang_solar) as total_solar, SUM(upah_sopir) as total_upah, SUM(dt) as total_dt, SUM(total) as grand_total, COUNT(*) as gaji_count')
            ->groupBy('periode_id')
            ->get()
            ->keyBy('periode_id');

        $periodes = $allPeriodes->map(function ($periode) use ($ritaseSummary, $gajiSummary) {
            $rit = $ritaseSummary->get($periode->id);
            $gaji = $gajiSummary->get($periode->id);

            $hasGaji = $gaji && $gaji->gaji_count > 0;

            return [
                'id' => $periode->id,
                'nama_periode' => $periode->nama_periode,
                'total_ritase' => $hasGaji ? ($rit->total_rit_rit ?? 0) : ($rit->total_rit_rit ?? 0),
                'total_solar' => $hasGaji ? (floatval($gaji->total_solar ?? 0)) : 0,
                'total_sopir' => $hasGaji
                    ? (floatval($gaji->total_upah ?? 0))
                    : (floatval($rit->total_upah_rit ?? 0)),
                'total_dt' => $hasGaji
                    ? (floatval($gaji->total_dt ?? 0))
                    : (floatval($rit->total_dt_rit ?? 0)),
                'grand_total' => $hasGaji
                    ? (floatval($gaji->grand_total ?? 0))
                    : ((floatval($rit->total_upah_rit ?? 0)) + (floatval($rit->total_dt_rit ?? 0))),
            ];
        });

        // Only show Tujuan that have ritase in selected period
        if ($periodeId) {
            $tujuanCodes = Ritase::where('periode_id', $periodeId)
                ->whereNotNull('kode_tujuan')
                ->distinct()
                ->pluck('kode_tujuan');
            $allTujuans = Tujuan::whereIn('kode_tujuan', $tujuanCodes)
                ->orderBy('id', 'asc')
                ->get();
        } else {
            $allTujuans = Tujuan::where('status', 'aktif')->orderBy('id', 'asc')->get();
        }

        $periodesForDropdown = Periode::all();

        return view('penggajian.index', compact('periodes', 'allTujuans', 'periodesForDropdown', 'periodeId'));
    }

    public function getRitaseData(Request $request)
    {
        try {
            $periodeId = $request->get('periode');
            $tanggal = $request->get('tanggal'); // optional daily filter

            // When tanggal is set, auto-detect the correct period that contains it
            if ($tanggal) {
                $found = \App\Models\Periode::whereDate('tanggal_mulai', '<=', $tanggal)
                    ->whereDate('tanggal_selesai', '>=', $tanggal)->first();
                if ($found) $periodeId = $found->id;
            }

            if (!$periodeId) {
                return response()->json(['error' => 'Parameter tidak lengkap'], 400);
            }

            // --- BATCH: all counts & sums in ~4 queries, no N+1 ---
            $ritBase = Ritase::where('periode_id', $periodeId);
            if ($tanggal) {
                $ritBase->whereDate('tanggal', $tanggal);
            }
            $ritCounts = (clone $ritBase)
                ->selectRaw('kode_sopir, kode_tujuan, COUNT(*) as total')
                ->groupBy('kode_sopir', 'kode_tujuan')
                ->get()->keyBy(fn($r) => $r->kode_sopir.'|'.$r->kode_tujuan);

            $validCounts = (clone $ritBase)
                ->where('status', '!=', 'gagal_produksi')
                ->selectRaw('kode_sopir, kode_tujuan, COUNT(*) as total')
                ->groupBy('kode_sopir', 'kode_tujuan')
                ->get()->keyBy(fn($r) => $r->kode_sopir.'|'.$r->kode_tujuan);

            $dtSums = (clone $ritBase)
                ->where('status', '!=', 'gagal_produksi')
                ->selectRaw('kode_sopir, SUM(dt) as total_dt')
                ->groupBy('kode_sopir')
                ->get()->keyBy('kode_sopir');
            $ritLemburSum = (clone $ritBase)
                ->where('is_lembur', true)
                ->selectRaw('kode_sopir, SUM(upah_lembur) as total_lembur')
                ->groupBy('kode_sopir')
                ->get()->keyBy('kode_sopir');

            $allGagalRits = (clone $ritBase)
                ->where('status', 'gagal_produksi')
                ->orderBy('tanggal')
                ->get(['id', 'kode_sopir', 'tanggal', 'kode_tujuan'])
                ->groupBy('kode_sopir');

            // --- BATCH: siapkan data sopir & tujuan ---
            $allSopirCodes = (clone $ritBase)
                ->distinct()->pluck('kode_sopir');
            $sopirs = Sopir::whereIn('kode_sopir', $allSopirCodes)
                ->orderBy('id', 'asc')
                ->get()->keyBy('kode_sopir');
            $allSopirCodes = $sopirs->keys();

            $tujuanCodes = (clone $ritBase)
                ->whereNotNull('kode_tujuan')->distinct()->pluck('kode_tujuan');
            $allTujuans = Tujuan::whereIn('kode_tujuan', $tujuanCodes)->get()->keyBy('kode_tujuan');

            // --- BATCH: existing penggajian ---
            $penggajianData = Penggajian::with(['sopir', 'details'])
                ->where('periode_id', $periodeId)->get()->keyBy('kode_sopir');

            // --- DEFAULT RATES ---
            $defaultRates = [];
            $refPeriodeId = $penggajianData->isNotEmpty()
                ? $periodeId
                : Penggajian::where('periode_id', '<', $periodeId)->max('periode_id');

            if ($refPeriodeId) {
                $rates = PenggajianDetail::whereHas('penggajian', function ($q) use ($refPeriodeId) {
                    $q->where('periode_id', $refPeriodeId);
                })->selectRaw('kode_tujuan, AVG(solar_per_rit) as bbm, AVG(upah_per_rit) as upah, AVG(tol_per_rit) as tol')
                  ->groupBy('kode_tujuan')->get();
                foreach ($rates as $r) {
                    $defaultRates[$r->kode_tujuan] = [
                        'bbm_per_rit' => floatval($r->bbm),
                        'upah_per_rit' => floatval($r->upah),
                        'tol_per_rit' => floatval($r->tol),
                    ];
                }
            }

            $kompensasiPerTujuan = (clone $ritBase)
                ->where('status', 'gagal_produksi')
                ->selectRaw('kode_tujuan, MAX(nominal_kompensasi) as kompensasi_per_rit')
                ->groupBy('kode_tujuan')->pluck('kompensasi_per_rit', 'kode_tujuan')->toArray();

            foreach ($kompensasiPerTujuan as $kodeTujuan => $kompPerRit) {
                if (!isset($defaultRates[$kodeTujuan])) {
                    $defaultRates[$kodeTujuan] = ['bbm_per_rit' => 0, 'upah_per_rit' => 0];
                }
                $defaultRates[$kodeTujuan]['kompensasi_gagal'] = floatval($kompPerRit);
            }

            $lemburPerTujuan = (clone $ritBase)
                ->where('is_lembur', true)
                ->selectRaw('kode_tujuan, MAX(upah_lembur) as lembur_per_rit')
                ->groupBy('kode_tujuan')->pluck('lembur_per_rit', 'kode_tujuan')->toArray();

            foreach ($lemburPerTujuan as $kodeTujuan => $lemburPerRit) {
                if (!isset($defaultRates[$kodeTujuan])) {
                    $defaultRates[$kodeTujuan] = ['bbm_per_rit' => 0, 'upah_per_rit' => 0];
                }
                $defaultRates[$kodeTujuan]['lembur_per_rit'] = floatval($lemburPerRit);
            }

            // --- BUILD RESULT ---
            $result = [];

            foreach ($allSopirCodes as $kodeSopir) {
                $gaji = $penggajianData->get($kodeSopir);
                $sopir = $sopirs->get($kodeSopir);
                if (!$sopir) continue;

                $ritPerTujuan = [];
                foreach ($tujuanCodes as $kt) {
                    $key = $kodeSopir.'|'.$kt;
                    $totalRit = isset($ritCounts[$key]) ? (int)$ritCounts[$key]->total : 0;
                    $totalRitValid = isset($validCounts[$key]) ? (int)$validCounts[$key]->total : 0;
                    if ($totalRit === 0) continue;

                    $detail = $gaji?->details->firstWhere('kode_tujuan', $kt);
                    $rate = $defaultRates[$kt] ?? null;

                    $ritPerTujuan[$kt] = [
                        'total_rit' => $totalRit,
                        'total_rit_valid' => $totalRitValid,
                        'solar_per_rit' => $detail?->solar_per_rit ? (float)$detail->solar_per_rit : ($rate ? $rate['bbm_per_rit'] : 0),
                        'upah_per_rit' => $detail?->upah_per_rit ? (float)$detail->upah_per_rit : ($rate ? $rate['upah_per_rit'] : 0),
                        'tol_per_rit' => $detail?->tol_per_rit ? (float)$detail->tol_per_rit : ($rate ? ($rate['tol_per_rit'] ?? 0) : 0),
                        'total_solar' => $detail?->total_solar ? (float)$detail->total_solar : ($rate ? $rate['bbm_per_rit'] * $totalRitValid : 0),
                        'total_upah' => $detail?->total_upah ? (float)$detail->total_upah : ($rate ? $rate['upah_per_rit'] * $totalRitValid : 0),
                        'total_tol' => $detail?->total_tol ? (float)$detail->total_tol : (($rate['tol_per_rit'] ?? 0) * $totalRitValid),
                        'subtotal' => $detail?->subtotal ? (float)$detail->subtotal : ($rate ? ($rate['bbm_per_rit'] + $rate['upah_per_rit']) * $totalRitValid : 0),
                    ];
                }

                if (empty($ritPerTujuan)) continue;

                $totalDT = isset($dtSums[$kodeSopir]) ? (float)$dtSums[$kodeSopir]->total_dt : 0;
            $upahLembur = $gaji ? (float)$gaji->upah_lembur : (float)($ritLemburSum->get($kodeSopir)?->total_lembur ?? 0);

                $gagalCollect = $allGagalRits->get($kodeSopir, collect());
                $gagalRits = $gagalCollect->map(fn($rit) => [
                    'id' => $rit->id,
                    'tanggal' => $rit->tanggal instanceof \Carbon\Carbon ? $rit->tanggal->format('Y-m-d') : $rit->tanggal,
                    'kode_tujuan' => $rit->kode_tujuan,
                ])->values()->toArray();

                $previewSolar = array_sum(array_column($ritPerTujuan, 'total_solar'));
                $previewUpah = array_sum(array_column($ritPerTujuan, 'total_upah'));
                $previewTol = $gaji ? (float)$gaji->tol : 0;

                $result[] = [
                    'kode_sopir' => $kodeSopir,
                    'nama_sopir' => $sopir->nama,
                    'periode_id' => $periodeId,
                    'total_dt' => $totalDT,
                    'total_tol' => $previewTol,
                    'total_kompensasi' => $gaji ? (float)$gaji->kompensasi_gagal : 0,
                    'total_solar' => $previewSolar,
                    'total_upah' => $previewUpah,
                    'upah_lembur' => $upahLembur,
                    'grand_total' => $previewSolar + $previewUpah + $totalDT + $previewTol + $upahLembur,
                    'rit_per_tujuan' => $ritPerTujuan,
                    'gagal_rits' => $gagalRits,
                    'belum_dihitung' => !$gaji,
                ];
            }

            return response()->json([
                'sopir' => $result,
                'default_rates' => $defaultRates,
                'detected_periode_id' => $periodeId,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'periode_id' => 'required|exists:periodes,id',
            'detail' => 'required|array|min:1',
            'detail.*.kode_tujuan' => 'required|exists:tujuans,kode_tujuan',
            'detail.*.bbm_per_rit' => 'required|numeric|min:0',
            'detail.*.upah_per_rit' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $periodeId = $request->periode_id;

            if (cache()->get('aturan_validasi_enabled', false)) {
                $ritaseList = Ritase::where('periode_id', $periodeId)
                    ->where('status', '!=', 'gagal_produksi')
                    ->get();
                foreach ($ritaseList as $rit) {
                    $valid = \App\Models\ValidasiBukti::where('kode_sopir', $rit->kode_sopir)
                        ->where('kode_tujuan', $rit->kode_tujuan)
                        ->where('tanggal', $rit->tanggal)
                        ->where('status', 'disetujui')
                        ->exists();
                    if (!$valid) {
                        DB::rollback();
                        return back()->with('error', 'Ritase ' . $rit->kode_ritase . ' belum memiliki bukti validasi disetujui.');
                    }
                }
            }

            Penggajian::where('periode_id', $periodeId)->delete();

            Ritase::where('periode_id', $periodeId)->update([
                'upah_sopir' => 0,
                'nominal_kompensasi' => 0,
            ]);

            $sopirs = Sopir::whereHas('ritase', function ($q) use ($periodeId) {
                $q->where('periode_id', $periodeId);
            })->get();

            $detailTujuanMap = [];
            foreach ($request->detail as $d) {
                $detailTujuanMap[$d['kode_tujuan']] = [
                    'bbm_per_rit' => floatval($d['bbm_per_rit']) ?: 0,
                    'upah_per_rit' => floatval($d['upah_per_rit']) ?: 0,
                    'tol_per_rit' => floatval($d['tol_per_rit'] ?? 0) ?: 0,
                    'kompensasi_gagal' => floatval($d['kompensasi_gagal'] ?? 0) ?: 0,
                    'lembur_per_rit' => floatval($d['lembur_per_rit'] ?? 0) ?: 0,
                ];
            }

            foreach ($detailTujuanMap as $kodeTujuan => $biaya) {
                $kompensasiPerRit = $biaya['kompensasi_gagal'];
                if ($kompensasiPerRit > 0) {
                    Ritase::where('periode_id', $periodeId)
                        ->where('kode_tujuan', $kodeTujuan)
                        ->where('status', 'gagal_produksi')
                        ->update(['nominal_kompensasi' => $kompensasiPerRit]);
                }

                // Lembur per-tujuan: update semua ritase non-gagal di tujuan ini
                $lemburPerRit = $biaya['lembur_per_rit'];
                Ritase::where('periode_id', $periodeId)
                    ->where('kode_tujuan', $kodeTujuan)
                    ->where('status', '!=', 'gagal_produksi')
                    ->update([
                        'is_lembur' => $lemburPerRit > 0,
                        'upah_lembur' => $lemburPerRit,
                    ]);
            }

            $this->processPenggajian($periodeId, $request, $detailTujuanMap);

            DB::commit();
            return redirect()->route('gaji.index', ['periode' => $periodeId])
                ->with('success', 'Data gaji berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $periode = Periode::findOrFail($id);

        // Only show Tujuan that have ritase/penggajian data in this period
        $tujuanCodes = Ritase::where('periode_id', $id)
            ->whereNotNull('kode_tujuan')
            ->distinct()
            ->pluck('kode_tujuan');
        $allTujuans = Tujuan::whereIn('kode_tujuan', $tujuanCodes)
            ->orderBy('id', 'asc')
            ->get();

        $existingGaji = Penggajian::with(['details', 'sopir'])
            ->where('periode_id', $id)
            ->get()
            ->keyBy('kode_sopir');

        $detailPerTujuan = [];
        $kompensasiGagal = [];
        foreach ($existingGaji as $gaji) {
            $kompensasiGagal[$gaji->kode_sopir] = $gaji->kompensasi_gagal ?? 0;
            foreach ($gaji->details as $detail) {
                if (!isset($detailPerTujuan[$detail->kode_tujuan])) {
                    $detailPerTujuan[$detail->kode_tujuan] = [
                        'bbm_per_rit' => $detail->solar_per_rit,
                        'upah_per_rit' => $detail->upah_per_rit,
                        'tol_per_rit' => $detail->tol_per_rit ?? 0,
                        'kompensasi_gagal' => 0,
                        'lembur_per_rit' => 0,
                    ];
                }
            }
        }

        // Ambil nilai lembur dari ritase (per-tujuan)
        $ritLemburTujuan = Ritase::where('periode_id', $id)
            ->where('is_lembur', true)
            ->selectRaw('kode_tujuan, MAX(upah_lembur) as lembur_per_rit')
            ->groupBy('kode_tujuan')
            ->pluck('lembur_per_rit', 'kode_tujuan');
        foreach ($detailPerTujuan as $kodeTujuan => &$data) {
            if (isset($ritLemburTujuan[$kodeTujuan])) {
                $data['lembur_per_rit'] = (float) $ritLemburTujuan[$kodeTujuan];
            }
        }

        $kompensasiPerTujuan = Ritase::where('periode_id', $id)
            ->where('status', 'gagal_produksi')
            ->selectRaw('kode_tujuan, SUM(nominal_kompensasi) as total_kompensasi')
            ->groupBy('kode_tujuan')
            ->pluck('total_kompensasi', 'kode_tujuan')
            ->toArray();

        foreach ($detailPerTujuan as $kodeTujuan => &$data) {
            $data['kompensasi_gagal'] = floatval($kompensasiPerTujuan[$kodeTujuan] ?? 0);
        }

        return view('penggajian.edit', compact('periode', 'allTujuans', 'existingGaji', 'detailPerTujuan', 'kompensasiGagal'));
    }

    private function processPenggajian($periodeId, $request, $detailTujuanMap)
    {
        // BATCH: pre-fetch all counts, dt sums, kompensasi sums in 5 queries
        $ritCounts = Ritase::where('periode_id', $periodeId)
            ->where('status', '!=', 'gagal_produksi')
            ->selectRaw('kode_sopir, kode_tujuan, COUNT(*) as total')
            ->groupBy('kode_sopir', 'kode_tujuan')
            ->get();
        $ritDtSum = Ritase::where('periode_id', $periodeId)
            ->where('status', '!=', 'gagal_produksi')
            ->selectRaw('kode_sopir, SUM(dt) as total')
            ->groupBy('kode_sopir')
            ->get()->keyBy('kode_sopir');
        $ritKompensasiSum = Ritase::where('periode_id', $periodeId)
            ->where('status', 'gagal_produksi')
            ->selectRaw('kode_sopir, SUM(nominal_kompensasi) as total')
            ->groupBy('kode_sopir')
            ->get()->keyBy('kode_sopir');
        $ritLemburSum = Ritase::where('periode_id', $periodeId)
            ->where('is_lembur', true)
            ->selectRaw('kode_sopir, SUM(upah_lembur) as total_lembur')
            ->groupBy('kode_sopir')
            ->get()->keyBy('kode_sopir');
        $countBySopir = $ritCounts->groupBy('kode_sopir');

        // BATCH update upah_sopir — per tujuan, not per sopir+tujuan
        foreach ($detailTujuanMap as $kodeTujuan => $biaya) {
            Ritase::where('periode_id', $periodeId)
                ->where('kode_tujuan', $kodeTujuan)
                ->where('status', '!=', 'gagal_produksi')
                ->update(['upah_sopir' => $biaya['upah_per_rit']]);
        }

        $sopirs = Sopir::whereHas('ritase', function ($q) use ($periodeId) {
            $q->where('periode_id', $periodeId);
        })->get();

        foreach ($sopirs as $sopir) {
            $totalSolar = 0;
            $totalUpah = 0;
            $totalTol = 0;
            $totalSubtotal = 0;
            $detailList = [];

            $sopirCounts = isset($countBySopir[$sopir->kode_sopir])
                ? $countBySopir[$sopir->kode_sopir]->keyBy('kode_tujuan')
                : collect();

            foreach ($detailTujuanMap as $kodeTujuan => $biaya) {
                $row = $sopirCounts->get($kodeTujuan);
                $jumlahRit = $row ? (int) $row->total : 0;

                if ($jumlahRit > 0) {
                    $bbmPerRit = $biaya['bbm_per_rit'];
                    $upahPerRit = $biaya['upah_per_rit'];
                    $tolPerRit = $biaya['tol_per_rit'];
                    $totalSolar += $bbmPerRit * $jumlahRit;
                    $totalUpah += $upahPerRit * $jumlahRit;
                    $totalTol += $tolPerRit * $jumlahRit;
                    $totalSubtotal += ($bbmPerRit * $jumlahRit) + ($upahPerRit * $jumlahRit);

                    $detailList[] = [
                        'kode_tujuan' => $kodeTujuan,
                        'jumlah_rit' => $jumlahRit,
                        'bbm_per_rit' => $bbmPerRit,
                        'upah_per_rit' => $upahPerRit,
                        'tol_per_rit' => $tolPerRit,
                    ];
                }
            }

            $totalDT = (int) ($ritDtSum->get($sopir->kode_sopir)?->total ?? 0);
            $kompensasiGagal = (int) ($ritKompensasiSum->get($sopir->kode_sopir)?->total ?? 0);
            $upahLembur = (int) ($ritLemburSum->get($sopir->kode_sopir)?->total_lembur ?? 0);

            $grandTotal = $totalSubtotal + $totalDT + $totalTol + $kompensasiGagal + $upahLembur;

            $gaji = Penggajian::create([
                'kode_sopir' => $sopir->kode_sopir,
                'periode_id' => $periodeId,
                'tanggal' => now(),
                'uang_solar' => $totalSolar,
                'upah_sopir' => $totalUpah,
                'dt' => $totalDT,
                'tol' => $totalTol,
                'upah_lembur' => $upahLembur,
                'kompensasi_gagal' => $kompensasiGagal,
                'total' => $grandTotal,
            ]);

            foreach ($detailList as $dl) {
                $subtotal = ($dl['bbm_per_rit'] * $dl['jumlah_rit']) + ($dl['upah_per_rit'] * $dl['jumlah_rit']);

                PenggajianDetail::create([
                    'penggajian_id' => $gaji->id,
                    'kode_tujuan' => $dl['kode_tujuan'],
                    'jumlah_rit' => $dl['jumlah_rit'],
                    'solar_per_rit' => $dl['bbm_per_rit'],
                    'upah_per_rit' => $dl['upah_per_rit'],
                    'total_solar' => $dl['bbm_per_rit'] * $dl['jumlah_rit'],
                    'total_upah' => $dl['upah_per_rit'] * $dl['jumlah_rit'],
                    'sewa_dt' => 0,
                    'tol_per_rit' => $dl['tol_per_rit'],
                    'total_tol' => $dl['tol_per_rit'] * $dl['jumlah_rit'],
                    'subtotal' => $subtotal,
                ]);
            }
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'periode_id' => 'required|exists:periodes,id',
            'detail' => 'required|array|min:1',
            'detail.*.kode_tujuan' => 'required|exists:tujuans,kode_tujuan',
            'detail.*.bbm_per_rit' => 'required|numeric|min:0',
            'detail.*.upah_per_rit' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $periodeId = $request->periode_id;

            if (cache()->get('aturan_validasi_enabled', false)) {
                $ritaseList = Ritase::where('periode_id', $periodeId)
                    ->where('status', '!=', 'gagal_produksi')
                    ->get();
                foreach ($ritaseList as $rit) {
                    $valid = \App\Models\ValidasiBukti::where('kode_sopir', $rit->kode_sopir)
                        ->where('kode_tujuan', $rit->kode_tujuan)
                        ->where('tanggal', $rit->tanggal)
                        ->where('status', 'disetujui')
                        ->exists();
                    if (!$valid) {
                        DB::rollback();
                        return back()->with('error', 'Ritase ' . $rit->kode_ritase . ' belum memiliki bukti validasi disetujui.');
                    }
                }
            }

            Penggajian::where('periode_id', $periodeId)->delete();

            Ritase::where('periode_id', $periodeId)->update([
                'upah_sopir' => 0,
                'nominal_kompensasi' => 0,
            ]);

            $sopirs = Sopir::whereHas('ritase', function ($q) use ($periodeId) {
                $q->where('periode_id', $periodeId);
            })->get();

            // update: ambil detail dari request (sama seperti store)
            $detailTujuanMap = [];
            foreach ($request->detail as $d) {
                $detailTujuanMap[$d['kode_tujuan']] = [
                    'bbm_per_rit' => floatval($d['bbm_per_rit']) ?: 0,
                    'upah_per_rit' => floatval($d['upah_per_rit']) ?: 0,
                    'tol_per_rit' => floatval($d['tol_per_rit'] ?? 0) ?: 0,
                    'kompensasi_gagal' => floatval($d['kompensasi_gagal'] ?? 0) ?: 0,
                    'lembur_per_rit' => floatval($d['lembur_per_rit'] ?? 0) ?: 0,
                ];
            }

            foreach ($detailTujuanMap as $kodeTujuan => $biaya) {
                $kompensasiPerRit = $biaya['kompensasi_gagal'];
                if ($kompensasiPerRit > 0) {
                    Ritase::where('periode_id', $periodeId)
                        ->where('kode_tujuan', $kodeTujuan)
                        ->where('status', 'gagal_produksi')
                        ->update(['nominal_kompensasi' => $kompensasiPerRit]);
                }

                // Lembur per-tujuan: update semua ritase non-gagal di tujuan ini
                $lemburPerRit = $biaya['lembur_per_rit'];
                Ritase::where('periode_id', $periodeId)
                    ->where('kode_tujuan', $kodeTujuan)
                    ->where('status', '!=', 'gagal_produksi')
                    ->update([
                        'is_lembur' => $lemburPerRit > 0,
                        'upah_lembur' => $lemburPerRit,
                    ]);
            }

            $this->processPenggajian($periodeId, $request, $detailTujuanMap);

            DB::commit();
            return redirect()->route('gaji.index', ['periode' => $periodeId])
                ->with('success', 'Data gaji berhasil diupdate!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            Penggajian::where('periode_id', $id)->delete();
            return redirect()->route('gaji.index')->with('success', 'Data gaji berhasil dihapus!');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function slipGaji($periodeId, $kodeSopir)
    {
        $periode = Periode::findOrFail($periodeId);
        $sopir = Sopir::where('kode_sopir', $kodeSopir)->firstOrFail();

        $gaji = Penggajian::with('details.tujuan')
            ->where('periode_id', $periodeId)
            ->where('kode_sopir', $kodeSopir)
            ->first();

        if (!$gaji) {
            $ritaseData = Ritase::where('periode_id', $periodeId)
                ->where('kode_sopir', $kodeSopir)
                ->get();

            if ($ritaseData->isEmpty()) {
                return view('penggajian.slip', [
                    'periode' => $periode,
                    'sopir' => $sopir,
                    'gaji' => null,
                    'dataPerHari' => [],
                    'detailTujuan' => collect(),
                    'error' => 'Tidak ada data ritase untuk sopir ini pada periode tersebut'
                ]);
            }

            $ritByTujuan = $ritaseData->groupBy('kode_tujuan');

            $lastSolarPerRit = PenggajianDetail::whereIn('kode_tujuan', $ritByTujuan->keys())
                ->orderBy('id', 'desc')
                ->get()
                ->groupBy('kode_tujuan')
                ->map(fn($items) => $items->first()->solar_per_rit);

            $details = collect();
            $totalUangSolar = 0;
            $totalUpahSopir = 0;
            $totalDT = 0;
            $totalSubtotal = 0;

            foreach ($ritByTujuan as $kodeTujuan => $rits) {
                $jumlahRit = $rits->count();
                $upahPerRit = $rits->first()->upah_sopir ?? 0;
                $solarPerRit = $lastSolarPerRit[$kodeTujuan] ?? 0;
                $totalSolar = $solarPerRit * $jumlahRit;
                $totalUpah = $upahPerRit * $jumlahRit;
                $upahLemburTujuan = $rits->sum('upah_lembur');
                $totalUpah += $upahLemburTujuan;
                $dtPerTujuan = $rits->sum('dt');
                $subtotal = $totalSolar + $totalUpah + $dtPerTujuan;

                $detail = new \stdClass();
                $detail->kode_tujuan = $kodeTujuan;
                $detail->jumlah_rit = $jumlahRit;
                $detail->solar_per_rit = $solarPerRit;
                $detail->upah_per_rit = $upahPerRit;
                $detail->total_solar = $totalSolar;
                $detail->total_upah = $totalUpah;
                $detail->sewa_dt = $dtPerTujuan;
                $detail->subtotal = $subtotal;
                $detail->tujuan = Tujuan::where('kode_tujuan', $kodeTujuan)->first();

                $details->push($detail);

                $totalUangSolar += $totalSolar;
                $totalUpahSopir += $totalUpah;
                $totalDT += $dtPerTujuan;
                $totalSubtotal += $subtotal;
            }

            $gaji = new \stdClass();
            $gaji->dt = $totalDT;
            $gaji->tol = 0;
            $gaji->uang_solar = $totalUangSolar;
            $gaji->upah_sopir = $totalUpahSopir;
            $gaji->total = $totalSubtotal;
            $gaji->kode_sopir = $kodeSopir;
            $gaji->kompensasi_gagal = 0;
            $gaji->details = $details;

            $detailTujuan = $details;
            $ritasePerHari = $ritaseData->groupBy(function ($rit) {
                return $rit->tanggal instanceof \Carbon\Carbon ? $rit->tanggal->format('Y-m-d') : $rit->tanggal;
            });
        } else {
            $detailTujuan = $gaji->details;

            $ritasePerHari = Ritase::where('periode_id', $periodeId)
                ->where('kode_sopir', $kodeSopir)
                ->orderBy('tanggal', 'asc')
                ->get()
                ->groupBy(function ($rit) {
                    return $rit->tanggal instanceof \Carbon\Carbon ? $rit->tanggal->format('Y-m-d') : $rit->tanggal;
                });
        }

        $startDate = \Carbon\Carbon::parse($periode->tanggal_mulai);
        $endDate = \Carbon\Carbon::parse($periode->tanggal_selesai);
        $hariList = [];

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $hariList[] = $date->format('Y-m-d');
        }

        $namaHari = [
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => "Jum'at",
        ];

        $dataPerHari = [];

        foreach ($hariList as $tanggal) {
            $ritHari = $ritasePerHari->get($tanggal, collect());

            foreach ($ritHari as $ritIndex => $rit) {
                $dateObj = \Carbon\Carbon::parse($tanggal);
                $hari = $namaHari[$dateObj->format('l')] ?? $dateObj->format('l');

                $detail = $detailTujuan->first(function ($d) use ($rit) {
                    return $d->kode_tujuan === $rit->kode_tujuan;
                });

                $isGagal = $rit->status === 'gagal_produksi';
                $solarPerRit = 0;
                $upahPerRit = 0;
                $kompensasiRit = 0;
                $tolPerRit = 0;

                if ($isGagal) {
                    $kompensasiRit = $rit->nominal_kompensasi ?? 0;
                } elseif ($detail) {
                    $jmlRit = $detail->jumlah_rit ?? 1;
                    $solarPerRit = $jmlRit > 0 ? ($detail->solar_per_rit ?? $detail->total_solar / $jmlRit) : 0;
                    $upahPerRit = $jmlRit > 0 ? ($detail->upah_per_rit ?? $detail->total_upah / $jmlRit) : ($rit->upah_sopir ?? 0);
                    if (isset($detail->tol_per_rit)) {
                        $tolPerRit = $jmlRit > 0 ? ($detail->tol_per_rit ?? $detail->total_tol / $jmlRit) : 0;
                    }
                } else {
                    $upahPerRit = $rit->upah_sopir ?? 0;
                }

                $tujuanNama = '-';
                if ($detail && $detail->tujuan) {
                    $tujuanNama = $detail->tujuan->nama;
                } elseif ($rit->tujuan) {
                    $tujuanNama = $rit->tujuan->nama;
                } else {
                    $tujuanNama = $rit->kode_tujuan;
                }

                $dataPerHari[] = [
                    'tanggal' => $tanggal,
                    'hari' => $hari,
                    'rit_ke' => $ritIndex + 1,
                    'total_rit_hari' => $ritHari->count(),
                    'solar' => round($solarPerRit),
                    'upah' => round($upahPerRit),
                    'jumlah' => $isGagal ? round($kompensasiRit) : round($solarPerRit + $upahPerRit + ($rit->upah_lembur ?? 0)),
                    'tujuan' => $tujuanNama,
                    'is_gagal' => $isGagal,
                    'is_lembur' => $rit->is_lembur ?? false,
                    'upah_lembur' => (float)($rit->upah_lembur ?? 0),
                    'dt' => $isGagal ? 0 : (floatval($rit->dt) ?? 0),
                    'tol' => $isGagal ? 0 : round($tolPerRit),
                ];
            }
        }

        return view('penggajian.slip', compact(
            'periode', 'sopir', 'gaji', 'dataPerHari', 'detailTujuan'
        ));
    }

    public function laporan(Request $request)
    {
        $periodeId = $request->get('periode');
        $periodes = Periode::orderBy('id', 'desc')->get();

        $data = null;
        $periode = null;

        if ($periodeId) {
            $periode = Periode::findOrFail($periodeId);

            $hariKerja = Ritase::where('periode_id', $periodeId)
                ->where('status', '!=', 'gagal_produksi')
                ->distinct('tanggal')
                ->count('tanggal');

            $totalSopir = Sopir::whereHas('ritase', function ($q) use ($periodeId) {
                $q->where('periode_id', $periodeId);
            })->count();

            $totalRitase = Ritase::where('periode_id', $periodeId)
                ->where('status', '!=', 'gagal_produksi')
                ->count();

            $totalRitaseGagal = Ritase::where('periode_id', $periodeId)
                ->where('status', 'gagal_produksi')
                ->count();

            $gajiPerTujuan = PenggajianDetail::whereHas('penggajian', function ($q) use ($periodeId) {
                $q->where('periode_id', $periodeId);
            })
                ->selectRaw('kode_tujuan, SUM(jumlah_rit) as total_rit, SUM(total_solar) as total_solar, SUM(total_upah) as total_upah, SUM(total_tol) as total_tol, SUM(subtotal) as subtotal')
                ->groupBy('kode_tujuan')
                ->get()
                ->keyBy('kode_tujuan');

            $nonGagalPerTujuan = Ritase::where('periode_id', $periodeId)
                ->where('status', '!=', 'gagal_produksi')
                ->selectRaw('kode_tujuan, COUNT(*) as total_rit, SUM(dt) as total_dt')
                ->groupBy('kode_tujuan')
                ->get()
                ->keyBy('kode_tujuan');

            $gagalPerTujuan = Ritase::where('periode_id', $periodeId)
                ->where('status', 'gagal_produksi')
                ->selectRaw('kode_tujuan, COUNT(*) as jumlah_gagal, SUM(nominal_kompensasi) as total_kompensasi')
                ->groupBy('kode_tujuan')
                ->get()
                ->keyBy('kode_tujuan');

            $allTujuanCodes = $gajiPerTujuan->keys()
                ->merge($nonGagalPerTujuan->keys())
                ->merge($gagalPerTujuan->keys())
                ->unique();
            $tujuanList = Tujuan::whereIn('kode_tujuan', $allTujuanCodes)->get()->keyBy('kode_tujuan');

            $totalSolarAll = 0;
            $totalUpahAll = 0;
            $totalDTAll = 0;
            $totalGagalAll = 0;
            $grandTotalAll = 0;
            $detailRows = [];
            $no = 1;

            foreach ($allTujuanCodes as $kodeTujuan) {
                $tujuan = $tujuanList->get($kodeTujuan);
                $namaTujuan = $tujuan ? $tujuan->nama : $kodeTujuan;
                $detail = $gajiPerTujuan->get($kodeTujuan);
                $nonGagal = $nonGagalPerTujuan->get($kodeTujuan);
                $gagal = $gagalPerTujuan->get($kodeTujuan);

                $dtTotal = floatval($nonGagal->total_dt ?? 0);
                $rit = intval($detail ? $detail->total_rit : ($nonGagal->total_rit ?? 0));
                $solarTotal = floatval($detail ? $detail->total_solar : 0);
                $upahTotal = floatval($detail ? $detail->total_upah : 0);
                $gagalQty = $gagal ? intval($gagal->jumlah_gagal) : 0;
                $gagalTotal = $gagal ? floatval($gagal->total_kompensasi) : 0;
                $gagalPerUnit = $gagalQty > 0 ? $gagalTotal / $gagalQty : 0;

                $solarPerRit = $rit > 0 ? $solarTotal / $rit : 0;
                $upahPerRit = $rit > 0 ? $upahTotal / $rit : 0;
                $dtPerRit = $rit > 0 ? $dtTotal / $rit : 0;

                $tolTotal = floatval($detail ? $detail->total_tol : 0);
                $tolPerRit = $rit > 0 ? $tolTotal / $rit : 0;
                $subtotal = $solarTotal + $upahTotal + $dtTotal + $tolTotal + $gagalTotal;
                $groupNo = $no++;

                $detailRows[] = [
                    'no' => $groupNo, 'tujuan' => $namaTujuan, 'jenis' => 'Solar',
                    'harga' => $solarPerRit, 'qty' => $rit, 'total' => $solarTotal, 'is_subtotal' => false,
                ];
                $detailRows[] = [
                    'no' => $groupNo, 'tujuan' => $namaTujuan, 'jenis' => 'Upah Sopir',
                    'harga' => $upahPerRit, 'qty' => $rit, 'total' => $upahTotal, 'is_subtotal' => false,
                ];
                $detailRows[] = [
                    'no' => $groupNo, 'tujuan' => $namaTujuan, 'jenis' => 'DT',
                    'harga' => $dtPerRit, 'qty' => $rit, 'total' => $dtTotal, 'is_subtotal' => false,
                ];
                if ($tolTotal > 0) {
                    $detailRows[] = [
                        'no' => $groupNo, 'tujuan' => $namaTujuan, 'jenis' => 'Tol',
                        'harga' => $tolPerRit, 'qty' => $rit, 'total' => $tolTotal, 'is_subtotal' => false,
                    ];
                }
                if ($gagalQty > 0) {
                    $detailRows[] = [
                        'no' => $groupNo, 'tujuan' => $namaTujuan, 'jenis' => 'Gagal',
                        'harga' => $gagalPerUnit, 'qty' => $gagalQty, 'total' => $gagalTotal, 'is_subtotal' => false,
                    ];
                }
                $detailRows[] = [
                    'no' => '', 'tujuan' => $namaTujuan, 'jenis' => 'SUBTOTAL',
                    'harga' => 0, 'qty' => $rit + $gagalQty, 'total' => $subtotal, 'is_subtotal' => true,
                ];

                $totalSolarAll += $solarTotal;
                $totalUpahAll += $upahTotal;
                $totalDTAll += $dtTotal;
                $totalGagalAll += $gagalTotal;
                $grandTotalAll += $subtotal; // sudah include tol
            }

            $data = [
                'hari_kerja' => $hariKerja,
                'total_sopir' => $totalSopir,
                'total_ritase' => $totalRitase,
                'total_ritase_gagal' => $totalRitaseGagal,
                'detail_rows' => $detailRows,
                'total_solar_all' => $totalSolarAll,
                'total_upah_all' => $totalUpahAll,
                'total_dt_all' => $totalDTAll,
                'total_gagal_all' => $totalGagalAll,
                'grand_total_all' => $grandTotalAll,
            ];
        }

        return view('penggajian.laporan', compact('periodes', 'periodeId', 'data', 'periode'));
    }

    private function buildPeriodeSlipData($periodeId)
    {
        $periode = Periode::findOrFail($periodeId);

        $sopirIds = Penggajian::where('periode_id', $periodeId)
            ->pluck('kode_sopir')->unique()->values();

        $ritaseSopirIds = Ritase::where('periode_id', $periodeId)
            ->whereNotIn('kode_sopir', $sopirIds)
            ->pluck('kode_sopir')->unique()->values();

        $sopirIds = $sopirIds->concat($ritaseSopirIds)->unique()->values();

        $allSlips = [];
        foreach ($sopirIds as $kodeSopir) {
            $slipData = $this->buildSlipData($periodeId, $kodeSopir);
            if ($slipData && count($slipData['dataPerHari']) > 0) {
                $allSlips[] = $slipData;
            }
        }

        usort($allSlips, fn($a, $b) => $a['sopir']->id <=> $b['sopir']->id);

        $startDate = \Carbon\Carbon::parse($periode->tanggal_mulai);
        $endDate = \Carbon\Carbon::parse($periode->tanggal_selesai);

        $namaHari = [
            'Saturday' => 'Sabtu', 'Sunday' => 'Minggu',
            'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis',
            'Friday' => "Jum'at",
        ];

        $dateHeaders = [];
        for ($d = $startDate->copy(); $d <= $endDate; $d->addDay()) {
            $dayName = $namaHari[$d->format('l')] ?? $d->format('l');
            $dateHeaders[] = [
                'label' => $dayName,
                'date' => $d->format('d/m'),
                'tanggal' => $d->format('Y-m-d'),
            ];
        }

        $organizedSlips = [];
        foreach ($allSlips as $slip) {
            $ritMap = [];
            foreach ($slip['dataPerHari'] as $entry) {
                $ritMap[$entry['tanggal']][$entry['rit_ke']] = $entry;
            }
            $totalRitValid = 0;
            foreach ($slip['dataPerHari'] as $entry) {
                if (!$entry['is_gagal']) $totalRitValid++;
            }
            $potonganOperasional = $totalRitValid * 20000;

            $organizedSlips[] = [
                'sopir' => $slip['sopir'],
                'ritMap' => $ritMap,
                'totalSolarAll' => $slip['totalSolarAll'],
                'totalUpahAll' => $slip['totalUpahAll'],
                'totalJumlahAll' => $slip['totalJumlahAll'],
                'totalDTAll' => $slip['totalDTAll'],
                'totalTolAll' => $slip['totalTolAll'] ?? 0,
                'potonganOperasional' => $potonganOperasional,
                'grandTotal' => $slip['totalJumlahAll'] + $slip['totalDTAll'] + ($slip['totalTolAll'] ?? 0),
            ];
        }

        $slipEntries = [];
        foreach ($organizedSlips as $slip) {
            $sopirRits = [];
            foreach ($slip['ritMap'] as $rits) {
                foreach ($rits as $rit => $entry) {
                    $sopirRits[$rit] = true;
                }
            }
            $sopirRits = array_keys($sopirRits);
            sort($sopirRits);

            foreach ($sopirRits as $rit) {
                $totalSolar = $totalUpah = $totalJumlah = $totalDT = $totalTol = 0;
                foreach ($slip['ritMap'] as $rits) {
                    if (isset($rits[$rit])) {
                        $e = $rits[$rit];
                        $totalSolar += $e['solar'];
                        $totalUpah += $e['upah'];
                        $totalJumlah += $e['jumlah'];
                        $totalDT += $e['dt'];
                        $totalTol += $e['tol'];
                    }
                }
                $slipEntries[] = [
                    'sopir' => $slip['sopir'],
                    'ritMap' => $slip['ritMap'],
                    'ritKe' => $rit,
                    'totalSolarAll' => $totalSolar,
                    'totalUpahAll' => $totalUpah,
                    'totalJumlahAll' => $totalJumlah,
                    'totalDTAll' => $totalDT,
                    'totalTolAll' => $totalTol,
                    'potonganOperasional' => $slip['potonganOperasional'] ?? 0,
                    'grandTotal' => $totalJumlah + $totalDT + $totalTol,
                ];
            }
        }

        usort($slipEntries, fn($a, $b) =>
            $a['ritKe'] !== $b['ritKe'] ? $a['ritKe'] <=> $b['ritKe'] : $a['sopir']->id <=> $b['sopir']->id
        );

        $sopirPerPages = collect($slipEntries)->chunk(4)->map->values()->toArray();

        return compact('sopirPerPages', 'dateHeaders', 'periode');
    }

    public function lihatSlip($periodeId)
    {
        $data = $this->buildPeriodeSlipData($periodeId);
        return view('penggajian.slip-pdf', $data);
    }

    public function downloadSlipPdf($periodeId)
    {
        $data = $this->buildPeriodeSlipData($periodeId);
        $fileName = 'Slip_Gaji_' . str_replace(' ', '_', $data['periode']->nama_periode) . '.pdf';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('penggajian.slip-pdf', $data)
            ->setPaper([0, 0, 595, 935], 'landscape')
            ->setOption('isPhpEnabled', true)
            ->setOption('defaultFont', 'Times New Roman')
            ->setOption('isRemoteEnabled', false)
            ->setOption('dpi', 72);

        return $pdf->download($fileName);
    }

    private function buildSlipData($periodeId, $kodeSopir)
    {
        $periode = Periode::findOrFail($periodeId);
        $sopir = Sopir::where('kode_sopir', $kodeSopir)->first();
        if (!$sopir) return null;

        $gaji = Penggajian::with('details.tujuan')
            ->where('periode_id', $periodeId)
            ->where('kode_sopir', $kodeSopir)
            ->first();

        if ($gaji) {
            $detailTujuan = $gaji->details;
        } else {
            $detailTujuan = PenggajianDetail::whereHas('penggajian', function ($q) use ($periodeId) {
                $q->where('periode_id', $periodeId);
            })->get();
        }

        $ritasePerHari = Ritase::where('periode_id', $periodeId)
            ->where('kode_sopir', $kodeSopir)
            ->orderBy('tanggal', 'asc')
            ->get()
            ->groupBy(function ($rit) {
                return $rit->tanggal instanceof \Carbon\Carbon ? $rit->tanggal->format('Y-m-d') : $rit->tanggal;
            });

        $startDate = \Carbon\Carbon::parse($periode->tanggal_mulai);
        $endDate = \Carbon\Carbon::parse($periode->tanggal_selesai);
        $hariList = [];
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $hariList[] = $date->format('Y-m-d');
        }

        $namaHari = [
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => "Jum'at",
        ];

        $dataPerHari = [];
        foreach ($hariList as $tanggal) {
            $ritHari = $ritasePerHari->get($tanggal, collect());
            foreach ($ritHari as $ritIndex => $rit) {
                $dateObj = \Carbon\Carbon::parse($tanggal);
                $hari = $namaHari[$dateObj->format('l')] ?? $dateObj->format('l');

                $detail = $detailTujuan->first(function ($d) use ($rit) {
                    return $d->kode_tujuan === $rit->kode_tujuan;
                });

                $isGagal = $rit->status === 'gagal_produksi';
                $solarPerRit = 0;
                $upahPerRit = 0;
                $kompensasiRit = 0;

                if ($isGagal) {
                    $kompensasiRit = $rit->nominal_kompensasi ?? 0;
                } elseif ($detail) {
                    $jmlRit = $detail->jumlah_rit ?? 1;
                    $solarPerRit = $jmlRit > 0 ? ($detail->solar_per_rit ?? $detail->total_solar / $jmlRit) : 0;
                    $upahPerRit = $jmlRit > 0 ? ($detail->upah_per_rit ?? $detail->total_upah / $jmlRit) : ($rit->upah_sopir ?? 0);
                } else {
                    $upahPerRit = $rit->upah_sopir ?? 0;
                }

                $tujuanNama = '-';
                if ($detail && $detail->tujuan) {
                    $tujuanNama = $detail->tujuan->nama;
                } elseif ($rit->tujuan) {
                    $tujuanNama = $rit->tujuan->nama;
                } else {
                    $tujuanNama = $rit->kode_tujuan;
                }

                $tolPerRit = 0;
                if (!$isGagal && $detail && isset($detail->tol_per_rit)) {
                    $jmlRit = $detail->jumlah_rit ?? 1;
                    $tolPerRit = $jmlRit > 0 ? ($detail->tol_per_rit ?? $detail->total_tol / $jmlRit) : 0;
                }

                $dataPerHari[] = [
                    'tanggal' => $tanggal,
                    'hari' => $hari,
                    'rit_ke' => $ritIndex + 1,
                    'total_rit_hari' => $ritHari->count(),
                    'solar' => round($solarPerRit),
                    'upah' => round($upahPerRit),
                    'jumlah' => $isGagal ? round($kompensasiRit) : round($solarPerRit + $upahPerRit + ($rit->upah_lembur ?? 0)),
                    'tujuan' => $tujuanNama,
                    'is_gagal' => $isGagal,
                    'is_lembur' => $rit->is_lembur ?? false,
                    'upah_lembur' => (float)($rit->upah_lembur ?? 0),
                    'dt' => $isGagal ? 0 : (floatval($rit->dt) ?? 0),
                    'tol' => $isGagal ? 0 : round($tolPerRit),
                ];
            }
        }

        $totalSolarAll = array_sum(array_column($dataPerHari, 'solar'));
        $totalUpahAll = array_sum(array_column($dataPerHari, 'upah'));
        $totalJumlahAll = array_sum(array_column($dataPerHari, 'jumlah'));
        $totalDTAll = array_sum(array_column($dataPerHari, 'dt'));
        $totalTolAll = array_sum(array_column($dataPerHari, 'tol'));
        $totalKompensasiAll = $gaji ? ($gaji->kompensasi_gagal ?? 0) : 0;

        $grandTotal = $totalJumlahAll + $totalDTAll + $totalTolAll;

        return [
            'sopir' => $sopir,
            'gaji' => $gaji,
            'dataPerHari' => $dataPerHari,
            'totalSolarAll' => $totalSolarAll,
            'totalUpahAll' => $totalUpahAll,
            'totalJumlahAll' => $totalJumlahAll,
            'totalDTAll' => $totalDTAll,
            'totalTolAll' => $totalTolAll,
            'totalKompensasiAll' => $totalKompensasiAll,
            'grandTotal' => $grandTotal,
        ];
    }

    public function riwayat()
    {
        Periode::syncActiveStatus();

        $paginatedPeriodes = Periode::orderBy('id', 'desc')->paginate(10);
        $periodeIds = $paginatedPeriodes->pluck('id');

        $ritaseSummary = Ritase::whereIn('periode_id', $periodeIds)
            ->where('status', '!=', 'gagal_produksi')
            ->selectRaw('periode_id, SUM(upah_sopir) as total_upah_rit, SUM(dt) as total_dt_rit, COUNT(*) as total_rit')
            ->groupBy('periode_id')
            ->get()
            ->keyBy('periode_id');

        $gajiSummary = Penggajian::whereIn('periode_id', $periodeIds)
            ->selectRaw('periode_id, SUM(uang_solar) as total_solar, SUM(upah_sopir) as total_upah, SUM(dt) as total_dt, SUM(total) as grand_total, SUM(kompensasi_gagal) as total_kompensasi, COUNT(*) as gaji_count')
            ->groupBy('periode_id')
            ->get()
            ->keyBy('periode_id');

        $periodes = $paginatedPeriodes->getCollection()->map(function ($periode) use ($ritaseSummary, $gajiSummary) {
            $rit = $ritaseSummary->get($periode->id);
            $gaji = $gajiSummary->get($periode->id);
            $hasGaji = $gaji && $gaji->gaji_count > 0;
            $ritUpah = floatval($rit->total_upah_rit ?? 0);
            $ritDt = floatval($rit->total_dt_rit ?? 0);

            if ($hasGaji) {
                $jumlahSopir = Penggajian::where('periode_id', $periode->id)
                    ->distinct('kode_sopir')
                    ->count('kode_sopir');
            } else {
                $jumlahSopir = Ritase::where('periode_id', $periode->id)
                    ->distinct('kode_sopir')
                    ->count('kode_sopir');
            }

            return [
                'id' => $periode->id,
                'nama_periode' => $periode->nama_periode,
                'tanggal_mulai' => $periode->tanggal_mulai,
                'tanggal_selesai' => $periode->tanggal_selesai,
                'total_ritase' => $rit ? $rit->total_rit : 0,
                'total_solar' => $hasGaji ? floatval($gaji->total_solar) : 0,
                'total_upah' => $hasGaji ? floatval($gaji->total_upah) : $ritUpah,
                'total_dt' => $hasGaji ? floatval($gaji->total_dt) : $ritDt,
                'total_kompensasi' => $hasGaji ? floatval($gaji->total_kompensasi) : 0,
                'grand_total' => $hasGaji ? floatval($gaji->grand_total) : ($ritUpah + $ritDt),
                'jumlah_sopir' => $jumlahSopir,
            ];
        });

        $paginatedPeriodes->setCollection($periodes);

        return view('penggajian.riwayat', ['periodes' => $paginatedPeriodes]);
    }

    public function downloadLaporanPdf($periodeId)
    {
        $periode = Periode::findOrFail($periodeId);

        $hariKerja = Ritase::where('periode_id', $periodeId)
            ->where('status', '!=', 'gagal_produksi')
            ->distinct('tanggal')
            ->count('tanggal');

        $totalSopir = Sopir::whereHas('ritase', function ($q) use ($periodeId) {
            $q->where('periode_id', $periodeId);
        })->count();

        $totalRitase = Ritase::where('periode_id', $periodeId)
            ->where('status', '!=', 'gagal_produksi')
            ->count();

        $totalRitaseGagal = Ritase::where('periode_id', $periodeId)
            ->where('status', 'gagal_produksi')
            ->count();

        $gajiPerTujuan = PenggajianDetail::whereHas('penggajian', function ($q) use ($periodeId) {
            $q->where('periode_id', $periodeId);
        })
            ->selectRaw('kode_tujuan, SUM(jumlah_rit) as total_rit, SUM(total_solar) as total_solar, SUM(total_upah) as total_upah, SUM(total_tol) as total_tol, SUM(subtotal) as subtotal')
            ->groupBy('kode_tujuan')
            ->get()
            ->keyBy('kode_tujuan');

        $nonGagalPerTujuan = Ritase::where('periode_id', $periodeId)
            ->where('status', '!=', 'gagal_produksi')
            ->selectRaw('kode_tujuan, COUNT(*) as total_rit, SUM(dt) as total_dt')
            ->groupBy('kode_tujuan')
            ->get()
            ->keyBy('kode_tujuan');

        $gagalPerTujuan = Ritase::where('periode_id', $periodeId)
            ->where('status', 'gagal_produksi')
            ->selectRaw('kode_tujuan, COUNT(*) as jumlah_gagal, SUM(nominal_kompensasi) as total_kompensasi')
            ->groupBy('kode_tujuan')
            ->get()
            ->keyBy('kode_tujuan');

        $allTujuanCodes = $gajiPerTujuan->keys()
            ->merge($nonGagalPerTujuan->keys())
            ->merge($gagalPerTujuan->keys())
            ->unique();
        $tujuanList = Tujuan::whereIn('kode_tujuan', $allTujuanCodes)->get()->keyBy('kode_tujuan');

        $totalSolarAll = 0;
        $totalUpahAll = 0;
        $totalDTAll = 0;
        $totalGagalAll = 0;
        $grandTotalAll = 0;
        $detailRows = [];
        $no = 1;

        foreach ($allTujuanCodes as $kodeTujuan) {
            $tujuan = $tujuanList->get($kodeTujuan);
            $namaTujuan = $tujuan ? $tujuan->nama : $kodeTujuan;
            $detail = $gajiPerTujuan->get($kodeTujuan);
            $nonGagal = $nonGagalPerTujuan->get($kodeTujuan);
            $gagal = $gagalPerTujuan->get($kodeTujuan);

            $dtTotal = floatval($nonGagal->total_dt ?? 0);
            $rit = intval($detail ? $detail->total_rit : ($nonGagal->total_rit ?? 0));
            $solarTotal = floatval($detail ? $detail->total_solar : 0);
            $upahTotal = floatval($detail ? $detail->total_upah : 0);
            $tolTotal = floatval($detail ? $detail->total_tol : 0);
            $gagalQty = $gagal ? intval($gagal->jumlah_gagal) : 0;
            $gagalTotal = $gagal ? floatval($gagal->total_kompensasi) : 0;
            $gagalPerUnit = $gagalQty > 0 ? $gagalTotal / $gagalQty : 0;

            $solarPerRit = $rit > 0 ? $solarTotal / $rit : 0;
            $upahPerRit = $rit > 0 ? $upahTotal / $rit : 0;
            $dtPerRit = $rit > 0 ? $dtTotal / $rit : 0;
            $tolPerRit = $rit > 0 ? $tolTotal / $rit : 0;

            $subtotal = $solarTotal + $upahTotal + $dtTotal + $tolTotal + $gagalTotal;
            $groupNo = $no++;

            $detailRows[] = [
                'no' => $groupNo, 'tujuan' => $namaTujuan, 'jenis' => 'Solar',
                'harga' => $solarPerRit, 'qty' => $rit, 'total' => $solarTotal, 'is_subtotal' => false,
            ];
            $detailRows[] = [
                'no' => $groupNo, 'tujuan' => $namaTujuan, 'jenis' => 'Upah Sopir',
                'harga' => $upahPerRit, 'qty' => $rit, 'total' => $upahTotal, 'is_subtotal' => false,
            ];
            $detailRows[] = [
                'no' => $groupNo, 'tujuan' => $namaTujuan, 'jenis' => 'DT',
                'harga' => $dtPerRit, 'qty' => $rit, 'total' => $dtTotal, 'is_subtotal' => false,
            ];
            if ($tolTotal > 0) {
                $detailRows[] = [
                    'no' => $groupNo, 'tujuan' => $namaTujuan, 'jenis' => 'Tol',
                    'harga' => $tolPerRit, 'qty' => $rit, 'total' => $tolTotal, 'is_subtotal' => false,
                ];
            }
            if ($gagalQty > 0) {
                $detailRows[] = [
                    'no' => $groupNo, 'tujuan' => $namaTujuan, 'jenis' => 'Gagal',
                    'harga' => $gagalPerUnit, 'qty' => $gagalQty, 'total' => $gagalTotal, 'is_subtotal' => false,
                ];
            }
            $detailRows[] = [
                'no' => '', 'tujuan' => $namaTujuan, 'jenis' => 'SUBTOTAL',
                'harga' => 0, 'qty' => $rit + $gagalQty, 'total' => $subtotal, 'is_subtotal' => true,
            ];

            $totalSolarAll += $solarTotal;
            $totalUpahAll += $upahTotal;
            $totalDTAll += $dtTotal;
            $totalGagalAll += $gagalTotal;
            $grandTotalAll += $subtotal;
        }

        $data = [
            'hari_kerja' => $hariKerja,
            'total_sopir' => $totalSopir,
            'total_ritase' => $totalRitase,
            'total_ritase_gagal' => $totalRitaseGagal,
            'detail_rows' => $detailRows,
            'total_solar_all' => $totalSolarAll,
            'total_upah_all' => $totalUpahAll,
            'total_dt_all' => $totalDTAll,
            'total_gagal_all' => $totalGagalAll,
            'grand_total_all' => $grandTotalAll,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('penggajian.laporan-pdf', compact('periode', 'data'));
        $pdf->setPaper('folio', 'landscape');
        return $pdf->stream("laporan-gaji-{$periode->kode_periode}.pdf");
    }
}
