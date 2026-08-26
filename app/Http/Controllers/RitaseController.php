<?php

namespace App\Http\Controllers;

use App\Models\Periode;
use App\Models\Ritase;
use App\Http\Requests\StoreRitaseRequest;
use App\Http\Requests\UpdateRitaseRequest;
use App\Services\RitaseService;
use Illuminate\Http\Request;

class RitaseController extends Controller
{
    public function __construct(
        private readonly RitaseService $ritaseService
    ) {}

    public function index(Request $request)
    {
        Periode::syncActiveStatus();
        return view('ritase.index', $this->ritaseService->getIndexData($request));
    }

    public function store(StoreRitaseRequest $request)
    {
        try {
            $dtValue = $this->ritaseService->storeRitase($request);
            return redirect()->back()
                ->with('success', 'Ritase berhasil ditambahkan! DT: Rp ' . number_format($dtValue, 0, ',', '.'));
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function update(UpdateRitaseRequest $request, $id)
    {
        $dtValue = $this->ritaseService->updateRitase($request, $id);
        return redirect()->back()
            ->with('success', 'Data ritase berhasil diperbarui! DT: Rp ' . number_format($dtValue, 0, ',', '.'));
    }

    public function destroy($id)
    {
        try {
            Ritase::findOrFail($id)->delete();
            return redirect()->back()->with('success', 'Data ritase berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    public function cekAturanSewaDT(Request $request)
    {
        return response()->json($this->ritaseService->cekAturanSewaDT($request));
    }

    public function detailData(Request $request)
    {
        return response()->json($this->ritaseService->detailData($request));
    }

    public function parserForm()
    {
        return view('ritase.parser', $this->ritaseService->getParserFormData());
    }

    public function parserProcess(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'periode_id' => 'required|exists:periodes,id',
            'auto_create' => 'boolean',
        ]);

        try {
            $results = $this->ritaseService->processParser($request);
            return view('ritase.parser-result', ['results' => $results, 'periodeId' => $request->periode_id]);
        } catch (\Exception $e) {
            return back()->withErrors(['text' => $e->getMessage()])->withInput();
        }
    }

    public function detailPdf(Request $request)
    {
        $result = $this->ritaseService->detailPdf($request);
        if ($request->has('view')) {
            return view('ritase.detail-html', $result);
        }
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('ritase.detail-pdf', $result)->setPaper('A4', 'landscape');
        return $pdf->download('detail-ritase-' . $result['periode']->nama_periode . '.pdf');
    }
}
