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

        $periodes = Periode::withCount('ritase')
            ->withSum('gaji', 'uang_solar')
            ->withSum('gaji', 'upah_sopir')
            ->withSum('gaji', 'dt')
            ->withSum('gaji', 'total')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($periode) {
                return [
                    'id' => $periode->id,
                    'nama_periode' => $periode->nama_periode,
                    'total_ritase' => $periode->ritase_count,
                    'total_solar' => $periode->gaji_uang_solar_sum ?? 0,
                    'total_sopir' => $periode->gaji_upah_sopir_sum ?? 0,
                    'total_dt' => $periode->gaji_dt_sum ?? 0,
                    'grand_total' => $periode->gaji_total_sum ?? 0,
                ];
            });

        $allTujuans = Tujuan::where('status', 'aktif')->orderBy('id', 'asc')->get();
        $periodesForDropdown = Periode::all();

        return view('penggajian.index', compact('periodes', 'allTujuans', 'periodesForDropdown', 'periodeId'));
    }

    public function getRitaseData(Request $request)
    {
        $periodeId = $request->get('periode');

        if (!$periodeId) {
            return response()->json(['error' => 'Parameter tidak lengkap'], 400);
        }

        $penggajianData = Penggajian::with(['sopir', 'details'])
            ->where('periode_id', $periodeId)
            ->get();

        $result = [];

        foreach ($penggajianData as $gaji) {
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

            $result[] = [
                'kode_sopir' => $gaji->kode_sopir,
                'nama_sopir' => $gaji->sopir ? $gaji->sopir->nama : 'Unknown',
                'periode_id' => $gaji->periode_id,
                'total_dt' => floatval($gaji->dt),
                'total_kompensasi' => floatval($gaji->kompensasi_gagal ?? 0),
                'total_solar' => floatval($gaji->uang_solar),
                'total_upah' => floatval($gaji->upah_sopir),
                'grand_total' => floatval($gaji->total),
                'rit_per_tujuan' => $ritPerTujuan
            ];
        }

        return response()->json($result);
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

            Penggajian::where('periode_id', $periodeId)->delete();

            $sopirs = Sopir::whereHas('ritase', function ($q) use ($periodeId) {
                $q->where('periode_id', $periodeId);
            })->get();

            foreach ($sopirs as $sopir) {
                $totalSolar = 0;
                $totalUpah = 0;
                $totalSubtotal = 0;

                foreach ($request->detail as $detail) {
                    $kodeTujuan = $detail['kode_tujuan'];
                    $bbmPerRit = floatval($detail['bbm_per_rit']) ?? 0;
                    $upahPerRit = floatval($detail['upah_per_rit']) ?? 0;

                    $jumlahRit = Ritase::where('periode_id', $periodeId)
                        ->where('kode_sopir', $sopir->kode_sopir)
                        ->where('kode_tujuan', $kodeTujuan)
                        ->where('status', '!=', 'gagal_produksi')
                        ->count();

                    if ($jumlahRit > 0) {
                        $totalSolar += $bbmPerRit * $jumlahRit;
                        $totalUpah += $upahPerRit * $jumlahRit;
                        $totalSubtotal += ($bbmPerRit * $jumlahRit) + ($upahPerRit * $jumlahRit);
                    }
                }

                $totalDT = Ritase::where('periode_id', $periodeId)
                    ->where('kode_sopir', $sopir->kode_sopir)
                    ->where('status', '!=', 'gagal_produksi')
                    ->sum('dt') ?? 0;

                $kompensasiGagal = floatval($request->kompensasi_gagal[$sopir->kode_sopir] ?? 0);

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

                foreach ($request->detail as $detail) {
                    $kodeTujuan = $detail['kode_tujuan'];
                    $bbmPerRit = floatval($detail['bbm_per_rit']) ?? 0;
                    $upahPerRit = floatval($detail['upah_per_rit']) ?? 0;

                    $jumlahRit = Ritase::where('periode_id', $periodeId)
                        ->where('kode_sopir', $sopir->kode_sopir)
                        ->where('kode_tujuan', $kodeTujuan)
                        ->where('status', '!=', 'gagal_produksi')
                        ->count();

                    if ($jumlahRit > 0) {
                        $subtotal = ($bbmPerRit * $jumlahRit) + ($upahPerRit * $jumlahRit);

                        PenggajianDetail::create([
                            'penggajian_id' => $gaji->id,
                            'kode_tujuan' => $kodeTujuan,
                            'jumlah_rit' => $jumlahRit,
                            'solar_per_rit' => $bbmPerRit,
                            'upah_per_rit' => $upahPerRit,
                            'total_solar' => $bbmPerRit * $jumlahRit,
                            'total_upah' => $upahPerRit * $jumlahRit,
                            'sewa_dt' => 0,
                            'subtotal' => $subtotal,
                        ]);
                    }
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
        $periodes = Periode::withCount('ritase')
            ->withSum('gaji', 'uang_solar')
            ->withSum('gaji', 'upah_sopir')
            ->withSum('gaji', 'dt')
            ->withSum('gaji', 'total')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($periode) {
                return [
                    'id' => $periode->id,
                    'nama_periode' => $periode->nama_periode,
                    'total_ritase' => $periode->ritase_count,
                    'total_solar' => $periode->gaji_uang_solar_sum ?? 0,
                    'total_sopir' => $periode->gaji_upah_sopir_sum ?? 0,
                    'total_dt' => $periode->gaji_dt_sum ?? 0,
                    'grand_total' => $periode->gaji_total_sum ?? 0,
                ];
            });

        $allTujuans = Tujuan::where('status', 'aktif')->orderBy('id', 'asc')->get();
        $periodesForDropdown = Periode::all();

        $existingGaji = Penggajian::with('details')
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
                    ];
                }
            }
        }

        return view('penggajian.edit', compact('periode', 'periodes', 'allTujuans', 'periodesForDropdown', 'existingGaji', 'detailPerTujuan', 'kompensasiGagal'));
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

            Penggajian::where('periode_id', $periodeId)->delete();

            $sopirs = Sopir::whereHas('ritase', function ($q) use ($periodeId) {
                $q->where('periode_id', $periodeId);
            })->get();

            foreach ($sopirs as $sopir) {
                $totalSolar = 0;
                $totalUpah = 0;
                $totalSubtotal = 0;

                foreach ($request->detail as $detail) {
                    $kodeTujuan = $detail['kode_tujuan'];
                    $bbmPerRit = floatval($detail['bbm_per_rit']) ?? 0;
                    $upahPerRit = floatval($detail['upah_per_rit']) ?? 0;

                    $jumlahRit = Ritase::where('periode_id', $periodeId)
                        ->where('kode_sopir', $sopir->kode_sopir)
                        ->where('kode_tujuan', $kodeTujuan)
                        ->where('status', '!=', 'gagal_produksi')
                        ->count();

                    if ($jumlahRit > 0) {
                        $totalSolar += $bbmPerRit * $jumlahRit;
                        $totalUpah += $upahPerRit * $jumlahRit;
                        $totalSubtotal += ($bbmPerRit * $jumlahRit) + ($upahPerRit * $jumlahRit);
                    }
                }

                $totalDT = Ritase::where('periode_id', $periodeId)
                    ->where('kode_sopir', $sopir->kode_sopir)
                    ->where('status', '!=', 'gagal_produksi')
                    ->sum('dt') ?? 0;

                $kompensasiGagal = floatval($request->kompensasi_gagal[$sopir->kode_sopir] ?? 0);

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

                foreach ($request->detail as $detail) {
                    $kodeTujuan = $detail['kode_tujuan'];
                    $bbmPerRit = floatval($detail['bbm_per_rit']) ?? 0;
                    $upahPerRit = floatval($detail['upah_per_rit']) ?? 0;

                    $jumlahRit = Ritase::where('periode_id', $periodeId)
                        ->where('kode_sopir', $sopir->kode_sopir)
                        ->where('kode_tujuan', $kodeTujuan)
                        ->where('status', '!=', 'gagal_produksi')
                        ->count();

                    if ($jumlahRit > 0) {
                        $subtotal = ($bbmPerRit * $jumlahRit) + ($upahPerRit * $jumlahRit);

                        PenggajianDetail::create([
                            'penggajian_id' => $gaji->id,
                            'kode_tujuan' => $kodeTujuan,
                            'jumlah_rit' => $jumlahRit,
                            'solar_per_rit' => $bbmPerRit,
                            'upah_per_rit' => $upahPerRit,
                            'total_solar' => $bbmPerRit * $jumlahRit,
                            'total_upah' => $upahPerRit * $jumlahRit,
                            'sewa_dt' => 0,
                            'subtotal' => $subtotal,
                        ]);
                    }
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
            return view('penggajian.slip', [
                'periode' => $periode,
                'sopir' => $sopir,
                'gaji' => null,
                'dataPerHari' => [],
                'detailTujuan' => collect(),
                'error' => 'Data gaji tidak ditemukan'
            ]);
        }

        $detailTujuan = $gaji->details;

        $ritasePerHari = Ritase::where('periode_id', $periodeId)
            ->where('kode_sopir', $kodeSopir)
            ->where('status', '!=', 'gagal_produksi')
            ->orderBy('tanggal', 'asc')
            ->get()
            ->groupBy('tanggal');

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
        $hariDenganRit = $ritasePerHari->count();

        foreach ($hariList as $tanggal) {
            $dateObj = \Carbon\Carbon::parse($tanggal);
            $hari = $namaHari[$dateObj->format('l')] ?? $dateObj->format('l');

            $ritHari = $ritasePerHari->get($tanggal, collect());
            $ritKe = $ritHari->count();

            $solarHari = 0;
            $upahHari = 0;
            $tujuanList = [];

            if ($ritKe > 0 && $hariDenganRit > 0) {
                foreach ($detailTujuan as $detail) {
                    $solarHari += $detail->total_solar / $hariDenganRit;
                    $upahHari += $detail->total_upah / $hariDenganRit;
                    $tujuanList[] = $detail->tujuan ? $detail->tujuan->nama : $detail->kode_tujuan;
                }
            }

            $dataPerHari[] = [
                'tanggal' => $tanggal,
                'hari' => $hari,
                'rit_ke' => $ritKe,
                'solar' => round($solarHari),
                'upah' => round($upahHari),
                'jumlah' => round($solarHari + $upahHari),
                'tujuan' => !empty($tujuanList) ? $tujuanList[0] : '-',
            ];
        }

        $dataPerHari = array_values(array_filter($dataPerHari, function($d) {
            return $d['rit_ke'] > 0;
        }));

        return view('penggajian.slip', compact(
            'periode', 'sopir', 'gaji', 'dataPerHari', 'detailTujuan'
        ));
    }

    public function riwayat()
    {
        $periodes = Periode::withCount('ritase')
            ->withSum('gaji', 'uang_solar')
            ->withSum('gaji', 'upah_sopir')
            ->withSum('gaji', 'dt')
            ->withSum('gaji', 'total')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($periode) {
                return [
                    'id' => $periode->id,
                    'nama_periode' => $periode->nama_periode,
                    'total_ritase' => $periode->ritase_count,
                    'total_solar' => $periode->gaji_uang_solar_sum ?? 0,
                    'total_sopir' => $periode->gaji_upah_sopir_sum ?? 0,
                    'total_dt' => $periode->gaji_dt_sum ?? 0,
                    'grand_total' => $periode->gaji_total_sum ?? 0,
                ];
            });

        return view('penggajian.riwayat', compact('periodes'));
    }
}
