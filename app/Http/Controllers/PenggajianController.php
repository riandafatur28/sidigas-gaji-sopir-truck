<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
        return view('penggajian.index', $this->penggajianService->getIndexData($request));
    }

    public function getRitaseData(Request $request)
    {
        try {
            $result = $this->penggajianService->getRitaseData($request);
            return isset($result['error'])
                ? response()->json(['error' => $result['error']], 400)
                : response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(StorePenggajianRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->penggajianService->storePenggajian($request);
            DB::commit();
            return redirect()->back()->with('success', 'Data gaji berhasil disimpan!')->withInput();
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function edit($id) { return view('penggajian.edit', $this->penggajianService->getEditData($id)); }

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
            $this->penggajianService->updatePenggajian($request, $id);
            DB::commit();
            return redirect()->back()->with('success', 'Data gaji berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            \App\Models\Penggajian::where('periode_id', $id)->delete();
            return redirect()->back()->with('success', 'Data gaji berhasil dihapus!');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function slipGaji($periodeId, $kodeSopir)
    {
        return view('penggajian.slip', $this->penggajianService->getSlipViewData($periodeId, $kodeSopir));
    }

    public function laporan(Request $request)
    {
        $result = $this->penggajianService->laporan($request);
        return view('penggajian.laporan', array_merge($result, ['periodes' => \App\Models\Periode::orderBy('id', 'desc')->get()]));
    }

    public function lihatSlip($periodeId) { return view('penggajian.slip-pdf', $this->penggajianService->getSlipPdfData($periodeId)); }

    public function downloadSlipPdf($periodeId)
    {
        $data = $this->penggajianService->getSlipPdfData($periodeId);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('penggajian.slip-pdf', $data)
            ->setPaper([0, 0, 595, 935], 'landscape')
            ->setOption('isPhpEnabled', true)
            ->setOption('defaultFont', 'Times New Roman')
            ->setOption('isRemoteEnabled', false)
            ->setOption('dpi', 72);
        return $pdf->download('Slip_Gaji_' . str_replace(' ', '_', $data['periode']->nama_periode) . '.pdf');
    }

    public function riwayat(Request $request)
    {
        return view('penggajian.riwayat', $this->penggajianService->getRiwayatData($request));
    }

    public function downloadLaporanPdf($periodeId)
    {
        $data = $this->penggajianService->getLaporanData($periodeId);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('penggajian.laporan-pdf', $data)->setPaper('folio', 'landscape');
        return $pdf->stream("laporan-gaji-{$data['periode']->kode_periode}.pdf");
    }
}
