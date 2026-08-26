<?php

namespace App\Http\Controllers;

use App\Models\Penggajian;
use App\Models\PenggajianDetail;
use App\Models\Periode;
use App\Models\Tujuan;
use App\Models\Sopir;
use App\Models\Ritase;
use App\Http\Requests\StorePenggajianRequest;
use App\Services\PenggajianService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenggajianController extends Controller
{
    public function __construct(
        private readonly PenggajianService $penggajianService
    ) {}

    public function index(Request $request)
    {
        Periode::syncActiveStatus();

        $periodeId = $request->get('periode');
        if (!$periodeId) {
            $latest = Periode::orderBy('id', 'desc')->first();
            if ($latest) $periodeId = $latest->id;
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
                'total_sopir' => $hasGaji ? (floatval($gaji->total_upah ?? 0)) : (floatval($rit->total_upah_rit ?? 0)),
                'total_dt' => $hasGaji ? (floatval($gaji->total_dt ?? 0)) : (floatval($rit->total_dt_rit ?? 0)),
                'grand_total' => $hasGaji
                    ? (floatval($gaji->grand_total ?? 0))
                    : ((floatval($rit->total_upah_rit ?? 0)) + (floatval($rit->total_dt_rit ?? 0))),
            ];
        });

        if ($periodeId) {
            $tujuanCodes = Ritase::where('periode_id', $periodeId)
                ->whereNotNull('kode_tujuan')->distinct()->pluck('kode_tujuan');
            $allTujuans = Tujuan::whereIn('kode_tujuan', $tujuanCodes)->orderBy('id', 'asc')->get();
        } else {
            $allTujuans = Tujuan::where('status', 'aktif')->orderBy('id', 'asc')->get();
        }

        $periodesForDropdown = Periode::all();

        return view('penggajian.index', compact('periodes', 'allTujuans', 'periodesForDropdown', 'periodeId'));
    }

    public function getRitaseData(Request $request)
    {
        try {
            $result = $this->penggajianService->getRitaseData($request);

            if (isset($result['error'])) {
                return response()->json(['error' => $result['error']], 400);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(StorePenggajianRequest $request)
    {
        DB::beginTransaction();
        try {
            $periodeId = $request->periode_id;

            if (cache()->get('aturan_validasi_enabled', false)) {
                $ritaseList = Ritase::where('periode_id', $periodeId)
                    ->where('status', '!=', 'gagal_produksi')->get();
                foreach ($ritaseList as $rit) {
                    $valid = \App\Models\ValidasiBukti::where('kode_sopir', $rit->kode_sopir)
                        ->where('kode_tujuan', $rit->kode_tujuan)
                        ->where('tanggal', $rit->tanggal)
                        ->where('status', 'disetujui')->exists();
                    if (!$valid) {
                        DB::rollback();
                        return back()->withInput()->with('error', 'Ritase ' . $rit->kode_ritase . ' belum memiliki bukti validasi disetujui.');
                    }
                }
            }

            Penggajian::where('periode_id', $periodeId)->delete();

            Ritase::where('periode_id', $periodeId)->update([
                'upah_sopir' => 0,
                'nominal_kompensasi' => 0,
            ]);

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

                $lemburPerRit = $biaya['lembur_per_rit'];
                Ritase::where('periode_id', $periodeId)
                    ->where('kode_tujuan', $kodeTujuan)
                    ->where('status', '!=', 'gagal_produksi')
                    ->update([
                        'is_lembur' => $lemburPerRit > 0,
                        'upah_lembur' => $lemburPerRit,
                    ]);
            }

            $this->penggajianService->processPenggajian($periodeId, $request, $detailTujuanMap);

            DB::commit();
            return redirect()->back()
                ->with('success', 'Data gaji berhasil disimpan!')
                ->withInput();

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $periode = Periode::findOrFail($id);

        $tujuanCodes = Ritase::where('periode_id', $id)
            ->whereNotNull('kode_tujuan')->distinct()->pluck('kode_tujuan');
        $allTujuans = Tujuan::whereIn('kode_tujuan', $tujuanCodes)->orderBy('id', 'asc')->get();

        $existingGaji = Penggajian::with(['details', 'sopir'])
            ->where('periode_id', $id)->get()->keyBy('kode_sopir');

        $detailPerTujuan = [];
        foreach ($existingGaji as $gaji) {
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
            ->pluck('total_kompensasi', 'kode_tujuan')->toArray();

        foreach ($detailPerTujuan as $kodeTujuan => &$data) {
            $data['kompensasi_gagal'] = floatval($kompensasiPerTujuan[$kodeTujuan] ?? 0);
        }

        return view('penggajian.edit', compact('periode', 'allTujuans', 'existingGaji', 'detailPerTujuan'));
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
                    ->where('status', '!=', 'gagal_produksi')->get();
                foreach ($ritaseList as $rit) {
                    $valid = \App\Models\ValidasiBukti::where('kode_sopir', $rit->kode_sopir)
                        ->where('kode_tujuan', $rit->kode_tujuan)
                        ->where('tanggal', $rit->tanggal)
                        ->where('status', 'disetujui')->exists();
                    if (!$valid) {
                        DB::rollback();
                        return back()->withInput()->with('error', 'Ritase ' . $rit->kode_ritase . ' belum memiliki bukti validasi disetajui.');
                    }
                }
            }

            Penggajian::where('periode_id', $periodeId)->delete();

            Ritase::where('periode_id', $periodeId)->update([
                'upah_sopir' => 0,
                'nominal_kompensasi' => 0,
            ]);

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

                $lemburPerRit = $biaya['lembur_per_rit'];
                Ritase::where('periode_id', $periodeId)
                    ->where('kode_tujuan', $kodeTujuan)
                    ->where('status', '!=', 'gagal_produksi')
                    ->update([
                        'is_lembur' => $lemburPerRit > 0,
                        'upah_lembur' => $lemburPerRit,
                    ]);
            }

            $this->penggajianService->processPenggajian($periodeId, $request, $detailTujuanMap);

            DB::commit();
            return redirect()->back()
                ->with('success', 'Data gaji berhasil diupdate!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            Penggajian::where('periode_id', $id)->delete();
            return redirect()->back()->with('success', 'Data gaji berhasil dihapus!');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function slipGaji($periodeId, $kodeSopir)
    {
        $slipData = $this->penggajianService->buildSlipData($periodeId, $kodeSopir);

        if (!$slipData || empty($slipData['dataPerHari'])) {
            return view('penggajian.slip', [
                'periode' => Periode::findOrFail($periodeId),
                'sopir' => Sopir::where('kode_sopir', $kodeSopir)->firstOrFail(),
                'gaji' => null,
                'dataPerHari' => [],
                'detailTujuan' => collect(),
                'error' => 'Tidak ada data ritase untuk sopir ini pada periode tersebut'
            ]);
        }

        return view('penggajian.slip', $slipData);
    }

    public function laporan(Request $request)
    {
        $periodes = Periode::orderBy('id', 'desc')->get();
        $result = $this->penggajianService->laporan($request);

        return view('penggajian.laporan', array_merge($result, ['periodes' => $periodes]));
    }

    public function lihatSlip($periodeId)
    {
        $data = $this->penggajianService->buildPeriodeSlipData($periodeId);
        return view('penggajian.slip-pdf', $data);
    }

    public function downloadSlipPdf($periodeId)
    {
        $data = $this->penggajianService->buildPeriodeSlipData($periodeId);
        $fileName = 'Slip_Gaji_' . str_replace(' ', '_', $data['periode']->nama_periode) . '.pdf';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('penggajian.slip-pdf', $data)
            ->setPaper([0, 0, 595, 935], 'landscape')
            ->setOption('isPhpEnabled', true)
            ->setOption('defaultFont', 'Times New Roman')
            ->setOption('isRemoteEnabled', false)
            ->setOption('dpi', 72);

        return $pdf->download($fileName);
    }

    public function riwayat()
    {
        Periode::syncActiveStatus();

        $sort = request('sort', 'terbaru');
        $bulan = request('bulan');
        $tahun = request('tahun');

        $query = Periode::query();

        if ($bulan && $tahun) {
            $query->whereMonth('tanggal_mulai', $bulan)
                  ->whereYear('tanggal_mulai', $tahun);
        } elseif ($bulan) {
            $query->whereMonth('tanggal_mulai', $bulan);
        }

        if ($tahun && !$bulan) {
            $query->whereYear('tanggal_mulai', $tahun);
        }

        $query->orderBy('tanggal_mulai', $sort === 'terlama' ? 'asc' : 'desc');

        $paginatedPeriodes = $query->paginate(10)->withQueryString();
        $periodeIds = $paginatedPeriodes->pluck('id');

        $ritaseSummary = Ritase::whereIn('periode_id', $periodeIds)
            ->where('status', '!=', 'gagal_produksi')
            ->selectRaw('periode_id, SUM(upah_sopir) as total_upah_rit, SUM(dt) as total_dt_rit, COUNT(*) as total_rit')
            ->groupBy('periode_id')->get()->keyBy('periode_id');

        $gajiSummary = Penggajian::whereIn('periode_id', $periodeIds)
            ->selectRaw('periode_id, SUM(uang_solar) as total_solar, SUM(upah_sopir) as total_upah, SUM(dt) as total_dt, SUM(total) as grand_total, SUM(kompensasi_gagal) as total_kompensasi, COUNT(*) as gaji_count')
            ->groupBy('periode_id')->get()->keyBy('periode_id');

        $availableYears = Periode::selectRaw('YEAR(tanggal_mulai) as tahun')
            ->distinct()->orderBy('tahun', 'desc')->pluck('tahun');

        $periodes = $paginatedPeriodes->getCollection()->map(function ($periode) use ($ritaseSummary, $gajiSummary) {
            $rit = $ritaseSummary->get($periode->id);
            $gaji = $gajiSummary->get($periode->id);
            $hasGaji = $gaji && $gaji->gaji_count > 0;
            $ritUpah = floatval($rit->total_upah_rit ?? 0);
            $ritDt = floatval($rit->total_dt_rit ?? 0);

            if ($hasGaji) {
                $jumlahSopir = Penggajian::where('periode_id', $periode->id)
                    ->distinct('kode_sopir')->count('kode_sopir');
            } else {
                $jumlahSopir = Ritase::where('periode_id', $periode->id)
                    ->distinct('kode_sopir')->count('kode_sopir');
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

        return view('penggajian.riwayat', [
            'periodes' => $paginatedPeriodes,
            'sort' => $sort,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'availableYears' => $availableYears,
        ]);
    }

    public function downloadLaporanPdf($periodeId)
    {
        $periode = Periode::findOrFail($periodeId);
        $result = $this->penggajianService->laporan(new Request(['periode' => $periodeId]));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('penggajian.laporan-pdf', [
            'periode' => $periode,
            'data' => $result['data'],
        ]);
        $pdf->setPaper('folio', 'landscape');
        return $pdf->stream("laporan-gaji-{$periode->kode_periode}.pdf");
    }
}
