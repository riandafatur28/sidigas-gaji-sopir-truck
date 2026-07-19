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
        $periodeId = $request->get('periode');

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

        $allTujuans = Tujuan::where('status', 'aktif')->orderBy('id', 'asc')->get();
        $periodesForDropdown = Periode::all();

        return view('penggajian.index', compact('periodes', 'allTujuans', 'periodesForDropdown', 'periodeId'));
    }

    public function getRitaseData(Request $request)
    {
        try {
            $periodeId = $request->get('periode');

            if (!$periodeId) {
                return response()->json(['error' => 'Parameter tidak lengkap'], 400);
            }

            $penggajianData = Penggajian::with(['sopir', 'details'])
                ->where('periode_id', $periodeId)
                ->get();

        $result = [];

        $existingSopirCodes = [];

        foreach ($penggajianData as $gaji) {
            $existingSopirCodes[] = $gaji->kode_sopir;

            $ritPerTujuan = [];
            foreach ($gaji->details as $detail) {
                $ritPerTujuan[$detail->kode_tujuan] = [
                    'total_rit' => $detail->jumlah_rit,
                    'solar_per_rit' => $detail->solar_per_rit,
                    'upah_per_rit' => $detail->upah_per_rit,
                    'total_solar' => $detail->total_solar,
                    'total_upah' => $detail->total_upah,
                    'subtotal' => $detail->subtotal,
                ];
            }

            $gagalRits = Ritase::where('periode_id', $periodeId)
                ->where('kode_sopir', $gaji->kode_sopir)
                ->where('status', 'gagal_produksi')
                ->orderBy('tanggal')
                ->get(['id', 'tanggal', 'kode_tujuan'])
                ->map(function ($rit) {
                    return [
                        'id' => $rit->id,
                        'tanggal' => $rit->tanggal instanceof \Carbon\Carbon ? $rit->tanggal->format('Y-m-d') : $rit->tanggal,
                        'kode_tujuan' => $rit->kode_tujuan,
                    ];
                })->toArray();

            $result[] = [
                'kode_sopir' => $gaji->kode_sopir,
                'nama_sopir' => $gaji->sopir ? $gaji->sopir->nama : 'Unknown',
                'periode_id' => $gaji->periode_id,
                'total_dt' => floatval($gaji->dt),
                'total_kompensasi' => floatval($gaji->kompensasi_gagal ?? 0),
                'total_solar' => floatval($gaji->uang_solar),
                'total_upah' => floatval($gaji->upah_sopir),
                'grand_total' => floatval($gaji->total),
                'rit_per_tujuan' => $ritPerTujuan,
                'gagal_rits' => $gagalRits,
                'belum_dihitung' => false,
            ];
        }

        // Ambil default BBM/Upah/Kompensasi per tujuan dari data periode sama, atau periode sebelumnya
        $defaultRates = [];

        $refPeriodeId = Penggajian::where('periode_id', $periodeId)->exists()
            ? $periodeId
            : Penggajian::where('periode_id', '<', $periodeId)->max('periode_id');

        if ($refPeriodeId) {
            $rates = PenggajianDetail::whereHas('penggajian', function ($q) use ($refPeriodeId) {
                $q->where('periode_id', $refPeriodeId);
            })->selectRaw('kode_tujuan, AVG(solar_per_rit) as bbm, AVG(upah_per_rit) as upah')
              ->groupBy('kode_tujuan')
              ->get();
            foreach ($rates as $r) {
                $defaultRates[$r->kode_tujuan] = [
                    'bbm_per_rit' => floatval($r->bbm),
                    'upah_per_rit' => floatval($r->upah),
                ];
            }
        }

        $kompensasiPerTujuan = Ritase::where('periode_id', $periodeId)
            ->where('status', 'gagal_produksi')
            ->selectRaw('kode_tujuan, MAX(nominal_kompensasi) as kompensasi_per_rit')
            ->groupBy('kode_tujuan')
            ->pluck('kompensasi_per_rit', 'kode_tujuan')
            ->toArray();

        foreach ($kompensasiPerTujuan as $kodeTujuan => $kompPerRit) {
            if (!isset($defaultRates[$kodeTujuan])) {
                $defaultRates[$kodeTujuan] = [
                    'bbm_per_rit' => 0,
                    'upah_per_rit' => 0,
                ];
            }
            $defaultRates[$kodeTujuan]['kompensasi_gagal'] = floatval($kompPerRit);
        }

        // Cari sopir yang punya ritase baru tapi belum ada di penggajian
        $sopirs = Sopir::whereHas('ritase', function ($q) use ($periodeId) {
            $q->where('periode_id', $periodeId);
        })->whereNotIn('kode_sopir', $existingSopirCodes)->get();

        $allTujuans = Tujuan::where('status', 'aktif')->get();

        foreach ($sopirs as $sopir) {
            $ritPerTujuan = [];
            $totalRit = 0;
            foreach ($allTujuans as $tujuan) {
                $jumlahRit = Ritase::where('periode_id', $periodeId)
                    ->where('kode_sopir', $sopir->kode_sopir)
                    ->where('kode_tujuan', $tujuan->kode_tujuan)
                    ->where('status', '!=', 'gagal_produksi')
                    ->count();
                if ($jumlahRit > 0) {
                    $rate = $defaultRates[$tujuan->kode_tujuan] ?? null;
                    $ritPerTujuan[$tujuan->kode_tujuan] = [
                        'total_rit' => $jumlahRit,
                        'solar_per_rit' => $rate ? $rate['bbm_per_rit'] : 0,
                        'upah_per_rit' => $rate ? $rate['upah_per_rit'] : 0,
                        'total_solar' => $rate ? ($rate['bbm_per_rit'] * $jumlahRit) : 0,
                        'total_upah' => $rate ? ($rate['upah_per_rit'] * $jumlahRit) : 0,
                        'subtotal' => $rate ? (($rate['bbm_per_rit'] + $rate['upah_per_rit']) * $jumlahRit) : 0,
                    ];
                    $totalRit += $jumlahRit;
                }
            }

            if ($totalRit > 0) {
                $totalDT = Ritase::where('periode_id', $periodeId)
                    ->where('kode_sopir', $sopir->kode_sopir)
                    ->where('status', '!=', 'gagal_produksi')
                    ->sum('dt') ?? 0;

                $gagalRits = Ritase::where('periode_id', $periodeId)
                    ->where('kode_sopir', $sopir->kode_sopir)
                    ->where('status', 'gagal_produksi')
                    ->orderBy('tanggal')
                    ->get(['id', 'tanggal', 'kode_tujuan'])
                    ->map(function ($rit) {
                        return [
                            'id' => $rit->id,
                            'tanggal' => $rit->tanggal instanceof \Carbon\Carbon ? $rit->tanggal->format('Y-m-d') : $rit->tanggal,
                            'kode_tujuan' => $rit->kode_tujuan,
                        ];
                    })->toArray();

                // Hitung preview grand total pakai default rates
                $previewSolar = array_sum(array_column($ritPerTujuan, 'total_solar'));
                $previewUpah = array_sum(array_column($ritPerTujuan, 'total_upah'));

                $result[] = [
                    'kode_sopir' => $sopir->kode_sopir,
                    'nama_sopir' => $sopir->nama,
                    'periode_id' => $periodeId,
                    'total_dt' => floatval($totalDT),
                    'total_kompensasi' => 0,
                    'total_solar' => $previewSolar,
                    'total_upah' => $previewUpah,
                    'grand_total' => $previewSolar + $previewUpah + floatval($totalDT),
                    'rit_per_tujuan' => $ritPerTujuan,
                    'gagal_rits' => $gagalRits,
                    'belum_dihitung' => true,
                ];
            }
        }

        return response()->json([
            'sopir' => $result,
            'default_rates' => $defaultRates,
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
                    'kompensasi_gagal' => floatval($d['kompensasi_gagal'] ?? 0) ?: 0,
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
            }

            foreach ($sopirs as $sopir) {
                $totalSolar = 0;
                $totalUpah = 0;
                $totalSubtotal = 0;
                $detailList = [];

                foreach ($detailTujuanMap as $kodeTujuan => $biaya) {
                    $jumlahRit = Ritase::where('periode_id', $periodeId)
                        ->where('kode_sopir', $sopir->kode_sopir)
                        ->where('kode_tujuan', $kodeTujuan)
                        ->where('status', '!=', 'gagal_produksi')
                        ->count();

                    if ($jumlahRit > 0) {
                        $bbmPerRit = $biaya['bbm_per_rit'];
                        $upahPerRit = $biaya['upah_per_rit'];
                        $totalSolar += $bbmPerRit * $jumlahRit;
                        $totalUpah += $upahPerRit * $jumlahRit;
                        $totalSubtotal += ($bbmPerRit * $jumlahRit) + ($upahPerRit * $jumlahRit);

                        $detailList[] = [
                            'kode_tujuan' => $kodeTujuan,
                            'jumlah_rit' => $jumlahRit,
                            'bbm_per_rit' => $bbmPerRit,
                            'upah_per_rit' => $upahPerRit,
                        ];

                        Ritase::where('periode_id', $periodeId)
                            ->where('kode_sopir', $sopir->kode_sopir)
                            ->where('kode_tujuan', $kodeTujuan)
                            ->where('status', '!=', 'gagal_produksi')
                            ->update(['upah_sopir' => $upahPerRit]);
                    }
                }

                $totalDT = Ritase::where('periode_id', $periodeId)
                    ->where('kode_sopir', $sopir->kode_sopir)
                    ->where('status', '!=', 'gagal_produksi')
                    ->sum('dt') ?? 0;

                $kompensasiGagal = Ritase::where('periode_id', $periodeId)
                    ->where('kode_sopir', $sopir->kode_sopir)
                    ->where('status', 'gagal_produksi')
                    ->sum('nominal_kompensasi') ?? 0;

                $grandTotal = $totalSubtotal + $totalDT + $kompensasiGagal;

                $gaji = Penggajian::create([
                    'kode_sopir' => $sopir->kode_sopir,
                    'periode_id' => $periodeId,
                    'tanggal' => now(),
                    'uang_solar' => $totalSolar,
                    'upah_sopir' => $totalUpah,
                    'dt' => $totalDT,
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
                        'subtotal' => $subtotal,
                    ]);
                }
            }

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

        $allTujuans = Tujuan::where('status', 'aktif')->orderBy('id', 'asc')->get();

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
                        'kompensasi_gagal' => 0,
                    ];
                }
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
                    'kompensasi_gagal' => floatval($d['kompensasi_gagal'] ?? 0) ?: 0,
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
            }

            foreach ($sopirs as $sopir) {
                $totalSolar = 0;
                $totalUpah = 0;
                $totalSubtotal = 0;
                $detailList = [];

                foreach ($detailTujuanMap as $kodeTujuan => $biaya) {
                    $jumlahRit = Ritase::where('periode_id', $periodeId)
                        ->where('kode_sopir', $sopir->kode_sopir)
                        ->where('kode_tujuan', $kodeTujuan)
                        ->where('status', '!=', 'gagal_produksi')
                        ->count();

                    if ($jumlahRit > 0) {
                        $bbmPerRit = $biaya['bbm_per_rit'];
                        $upahPerRit = $biaya['upah_per_rit'];
                        $totalSolar += $bbmPerRit * $jumlahRit;
                        $totalUpah += $upahPerRit * $jumlahRit;
                        $totalSubtotal += ($bbmPerRit * $jumlahRit) + ($upahPerRit * $jumlahRit);

                        $detailList[] = [
                            'kode_tujuan' => $kodeTujuan,
                            'jumlah_rit' => $jumlahRit,
                            'bbm_per_rit' => $bbmPerRit,
                            'upah_per_rit' => $upahPerRit,
                        ];

                        Ritase::where('periode_id', $periodeId)
                            ->where('kode_sopir', $sopir->kode_sopir)
                            ->where('kode_tujuan', $kodeTujuan)
                            ->where('status', '!=', 'gagal_produksi')
                            ->update(['upah_sopir' => $upahPerRit]);
                    }
                }

                $totalDT = Ritase::where('periode_id', $periodeId)
                    ->where('kode_sopir', $sopir->kode_sopir)
                    ->where('status', '!=', 'gagal_produksi')
                    ->sum('dt') ?? 0;

                $kompensasiGagal = Ritase::where('periode_id', $periodeId)
                    ->where('kode_sopir', $sopir->kode_sopir)
                    ->where('status', 'gagal_produksi')
                    ->sum('nominal_kompensasi') ?? 0;

                $grandTotal = $totalSubtotal + $totalDT + $kompensasiGagal;

                $gaji = Penggajian::create([
                    'kode_sopir' => $sopir->kode_sopir,
                    'periode_id' => $periodeId,
                    'tanggal' => now(),
                    'uang_solar' => $totalSolar,
                    'upah_sopir' => $totalUpah,
                    'dt' => $totalDT,
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
                        'subtotal' => $subtotal,
                    ]);
                }
            }

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

                $dataPerHari[] = [
                    'tanggal' => $tanggal,
                    'hari' => $hari,
                    'rit_ke' => $ritIndex + 1,
                    'total_rit_hari' => $ritHari->count(),
                    'solar' => round($solarPerRit),
                    'upah' => round($upahPerRit),
                    'jumlah' => $isGagal ? round($kompensasiRit) : round($solarPerRit + $upahPerRit),
                    'tujuan' => $tujuanNama,
                    'is_gagal' => $isGagal,
                    'dt' => $isGagal ? 0 : (floatval($rit->dt) ?? 0),
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
                ->selectRaw('kode_tujuan, SUM(jumlah_rit) as total_rit, SUM(total_solar) as total_solar, SUM(total_upah) as total_upah, SUM(subtotal) as subtotal')
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

                $subtotal = $solarTotal + $upahTotal + $dtTotal + $gagalTotal;
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
        }

        return view('penggajian.laporan', compact('periodes', 'periodeId', 'data', 'periode'));
    }

    public function downloadSlipPdf($periodeId)
    {
        $periode = Periode::findOrFail($periodeId);

        $sopirIds = Penggajian::where('periode_id', $periodeId)
            ->pluck('kode_sopir')
            ->unique()
            ->values();

        $ritaseSopirIds = Ritase::where('periode_id', $periodeId)
            ->whereNotIn('kode_sopir', $sopirIds)
            ->pluck('kode_sopir')
            ->unique()
            ->values();

        $sopirIds = $sopirIds->concat($ritaseSopirIds)->unique()->values();

        $allSlips = [];
        foreach ($sopirIds as $kodeSopir) {
            $slipData = $this->buildSlipData($periodeId, $kodeSopir);
            if ($slipData && count($slipData['dataPerHari']) > 0) {
                $allSlips[] = $slipData;
            }
        }

        usort($allSlips, function ($a, $b) {
            return $a['sopir']->id <=> $b['sopir']->id;
        });

        $startDate = \Carbon\Carbon::parse($periode->tanggal_mulai);
        $endDate = \Carbon\Carbon::parse($periode->tanggal_selesai);

        $namaHari = [
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
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

        // Find max rit count across all sopirs
        $maxRitKe = 1;
        foreach ($allSlips as $slip) {
            foreach ($slip['dataPerHari'] as $entry) {
                $maxRitKe = max($maxRitKe, $entry['rit_ke']);
            }
        }

        // Build organized data with [date][rit_ke] map per sopir
        $organizedSlips = [];
        foreach ($allSlips as $slip) {
            $ritMap = [];
            $allDt = $slip['totalDTAll'];
            $allJumlah = $slip['totalJumlahAll'];
            $allSolar = $slip['totalSolarAll'];
            $allUpah = $slip['totalUpahAll'];

            foreach ($slip['dataPerHari'] as $entry) {
                $tgl = $entry['tanggal'];
                $rit = $entry['rit_ke'];
                if (!isset($ritMap[$tgl])) {
                    $ritMap[$tgl] = [];
                }
                $ritMap[$tgl][$rit] = $entry;
            }

            $organizedSlips[] = [
                'sopir' => $slip['sopir'],
                'ritMap' => $ritMap,
                'totalSolarAll' => $allSolar,
                'totalUpahAll' => $allUpah,
                'totalJumlahAll' => $allJumlah,
                'totalDTAll' => $allDt,
                'grandTotal' => $allJumlah + $allDt,
            ];
        }

        // Flatten: satu entry per sopir per rit (hanya rit yang ada datanya)
        $slipEntries = [];
        foreach ($organizedSlips as $slip) {
            $sopirRits = [];
            foreach ($slip['ritMap'] as $tgl => $rits) {
                foreach ($rits as $rit => $entry) {
                    $sopirRits[$rit] = true;
                }
            }
            $sopirRits = array_keys($sopirRits);
            sort($sopirRits);

            foreach ($sopirRits as $rit) {
                $totalSolar = 0;
                $totalUpah = 0;
                $totalJumlah = 0;
                $totalDT = 0;

                foreach ($slip['ritMap'] as $tgl => $rits) {
                    if (isset($rits[$rit])) {
                        $e = $rits[$rit];
                        $totalSolar += $e['solar'];
                        $totalUpah += $e['upah'];
                        $totalJumlah += $e['jumlah'];
                        $totalDT += $e['dt'];
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
                    'grandTotal' => $totalJumlah + $totalDT,
                ];
            }
        }

        // Urut: berdasarkan rit dulu, baru sopir
        usort($slipEntries, function ($a, $b) {
            if ($a['ritKe'] !== $b['ritKe']) {
                return $a['ritKe'] <=> $b['ritKe'];
            }
            return $a['sopir']->id <=> $b['sopir']->id;
        });

        // 4 entry per page, tanpa forced page-break per rit
        $sopirPerPages = collect($slipEntries)->chunk(4)->map->values()->toArray();

        $fileName = 'Slip_Gaji_' . str_replace(' ', '_', $periode->nama_periode) . '.pdf';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('penggajian.slip-pdf', compact(
            'sopirPerPages', 'dateHeaders', 'periode'
        ))->setPaper([0, 0, 595, 935], 'landscape')
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

                $dataPerHari[] = [
                    'tanggal' => $tanggal,
                    'hari' => $hari,
                    'rit_ke' => $ritIndex + 1,
                    'total_rit_hari' => $ritHari->count(),
                    'solar' => round($solarPerRit),
                    'upah' => round($upahPerRit),
                    'jumlah' => $isGagal ? round($kompensasiRit) : round($solarPerRit + $upahPerRit),
                    'tujuan' => $tujuanNama,
                    'is_gagal' => $isGagal,
                    'dt' => $isGagal ? 0 : (floatval($rit->dt) ?? 0),
                ];
            }
        }

        $totalSolarAll = array_sum(array_column($dataPerHari, 'solar'));
        $totalUpahAll = array_sum(array_column($dataPerHari, 'upah'));
        $totalJumlahAll = array_sum(array_column($dataPerHari, 'jumlah'));
        $totalDTAll = array_sum(array_column($dataPerHari, 'dt'));
        $totalKompensasiAll = $gaji ? ($gaji->kompensasi_gagal ?? 0) : 0;

        $grandTotal = $totalJumlahAll + $totalDTAll;

        return [
            'sopir' => $sopir,
            'gaji' => $gaji,
            'dataPerHari' => $dataPerHari,
            'totalSolarAll' => $totalSolarAll,
            'totalUpahAll' => $totalUpahAll,
            'totalJumlahAll' => $totalJumlahAll,
            'totalDTAll' => $totalDTAll,
            'totalKompensasiAll' => $totalKompensasiAll,
            'grandTotal' => $grandTotal,
        ];
    }

    public function riwayat()
    {
        $allPeriodes = Periode::orderBy('id', 'desc')->get();
        $periodeIds = $allPeriodes->pluck('id');

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

        $periodes = $allPeriodes->map(function ($periode) use ($ritaseSummary, $gajiSummary) {
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

        return view('penggajian.riwayat', compact('periodes'));
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
            ->selectRaw('kode_tujuan, SUM(jumlah_rit) as total_rit, SUM(total_solar) as total_solar, SUM(total_upah) as total_upah, SUM(subtotal) as subtotal')
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

            $subtotal = $solarTotal + $upahTotal + $dtTotal + $gagalTotal;
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
