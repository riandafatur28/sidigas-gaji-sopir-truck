<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Periode;
use App\Models\Ritase;
use App\Http\Requests\StoreRitaseRequest;
use App\Http\Requests\UpdateRitaseRequest;
use App\Services\RitaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RitaseController extends Controller
{
    public function __construct(
        private readonly RitaseService $ritaseService
    ) {}

    /**
     * Display ritase index with filters.
     */
    public function index(Request $request): View
    {
        Periode::syncActiveStatus();
        return view('ritase.index', $this->ritaseService->getIndexData($request));
    }

    /**
     * Store a new ritase record.
     */
    public function store(StoreRitaseRequest $request)
    {
        try {
            $dtValue = $this->ritaseService->storeRitase($request);
            return redirect()->back()
                ->with('success', 'Ritase berhasil ditambahkan! DT: Rp ' . number_format($dtValue, 0, ',', '.'));
        } catch (\Exception $e) {
            report($e); // Log for debugging
            return back()->withInput()->with('error', 'Gagal menyimpan ritase: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing ritase record.
     */
    public function update(UpdateRitaseRequest $request, int $id)
    {
        try {
            $dtValue = $this->ritaseService->updateRitase($request, $id);
            return redirect()->back()
                ->with('success', 'Data ritase berhasil diperbarui! DT: Rp ' . number_format($dtValue, 0, ',', '.'));
        } catch (\Exception $e) {
            report($e);
            return back()->withInput()->with('error', 'Gagal memperbarui ritase: ' . $e->getMessage());
        }
    }

    /**
     * Delete a ritase record.
     */
    public function destroy(int $id)
    {
        try {
            Ritase::findOrFail($id)->delete();
            return redirect()->back()->with('success', 'Data ritase berhasil dihapus!');
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * Check DT rental rules via AJAX.
     */
    public function cekAturanSewaDT(Request $request): JsonResponse
    {
        try {
            return response()->json($this->ritaseService->cekAturanSewaDT($request));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get detail data for pivot table via AJAX.
     */
    public function detailData(Request $request): JsonResponse
    {
        try {
            return response()->json($this->ritaseService->detailData($request));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Show parser form.
     */
    public function parserForm(): View
    {
        return view('ritase.parser', $this->ritaseService->getParserFormData());
    }

    /**
     * Process text parser input.
     */
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
            report($e);
            return back()->withErrors(['text' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Download or view detail PDF.
     */
    public function detailPdf(Request $request)
    {
        try {
            $result = $this->ritaseService->detailPdf($request);
            if ($request->has('view')) {
                return view('ritase.detail-html', $result);
            }
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('ritase.detail-pdf', $result)->setPaper('A4', 'landscape');
            return $pdf->download('detail-ritase-' . $result['periode']->nama_periode . '.pdf');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Gagal generate PDF: ' . $e->getMessage());
        }
    }
}
