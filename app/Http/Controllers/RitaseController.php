<?php

namespace App\Http\Controllers;

use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
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
        $totalRitase = (clone $statBase)->count();
        $ritaseValid = (clone $statBase)->where('status', 'valid')->count();
        $ritasePending = (clone $statBase)->where('status', 'pending')->count();
        $ritaseGagal = (clone $statBase)->where('status', 'gagal_produksi')->count();
        $sopirTerlibat = (clone $statBase)->distinct('kode_sopir')->count('kode_sopir');

        return view('ritase.index', compact(
            'ritases', 'periodes', 'sopirs', 'tujuans',
            'search', 'filterPeriode', 'filterSopir', 'filterTujuan', 'tanggal',
            'totalRitase', 'ritaseValid', 'ritasePending', 'ritaseGagal', 'sopirTerlibat'
        ));
    }

    public function store(StoreRitaseRequest $request)
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
                return back()->withInput()->with('error', 'Sopir ini belum memiliki bukti validasi yang disetujui untuk tanggal dan tujuan ini.');
            }
        }

        Sopir::where('kode_sopir', $request->kode_sopir)->where('status', '!=', 'aktif')->update(['status' => 'aktif']);
        Tujuan::where('kode_tujuan', $request->kode_tujuan)->where('status', '!=', 'aktif')->update(['status' => 'aktif']);

        $dtValue = $this->ritaseService->hitungDT($request, null);

        Ritase::create([
            'periode_id' => $request->periode_id,
            'kode_sopir' => $request->kode_sopir,
            'kode_tujuan' => $request->kode_tujuan,
            'tanggal' => $request->tanggal,
            'waktu' => $request->waktu,
            'kabupaten' => $request->kabupaten,
            'status' => $request->status,
            'dt' => $dtValue,
            'upah_sopir' => $this->ritaseService->resolveUpahSopir($request->periode_id, $request->kode_tujuan),
            'nominal_kompensasi' => $validated['nominal_kompensasi'],
            'catatan' => $request->catatan,
            'is_lembur' => $isLembur,
            'upah_lembur' => $upahLembur,
        ]);

        return redirect()->back()
            ->with('success', 'Ritase berhasil ditambahkan! DT: Rp ' . number_format($dtValue, 0, ',', '.'));
    }

    public function update(UpdateRitaseRequest $request, $id)
    {
        $validated = $request->validated();
        $validated['nominal_kompensasi'] = is_numeric($validated['nominal_kompensasi'] ?? 0) ? (float) $validated['nominal_kompensasi'] : 0;
        $isLembur = $request->boolean('is_lembur');
        $upahLembur = $isLembur ? (float) ($request->upah_lembur ?? 0) : 0;

        Sopir::where('kode_sopir', $request->kode_sopir)->where('status', '!=', 'aktif')->update(['status' => 'aktif']);
        Tujuan::where('kode_tujuan', $request->kode_tujuan)->where('status', '!=', 'aktif')->update(['status' => 'aktif']);

        $ritase = Ritase::findOrFail($id);
        $dtValue = $this->ritaseService->hitungDT($request, $id);

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
        $result = $this->ritaseService->cekAturanSewaDT($request);
        return response()->json($result);
    }

    public function detailData(Request $request)
    {
        $result = $this->ritaseService->detailData($request);
        return response()->json($result);
    }

    public function parserForm()
    {
        $periodes = Periode::orderBy('id', 'desc')->get();
        $activePeriode = Periode::where('status', 'aktif')->first();
        return view('ritase.parser', compact('periodes', 'activePeriode'));
    }

    public function parserProcess(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'periode_id' => 'required|exists:periodes,id',
            'auto_create' => 'boolean',
        ]);

        $parser = new \App\Services\RitaseParserService();
        $parsed = $parser->parse($request->text);

        if (empty($parsed['date'])) {
            return back()->withErrors(['text' => 'Tanggal tidak terdeteksi. Format: DD MM YY hari'])
                ->withInput();
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

        return view('ritase.parser-result', ['results' => $results, 'periodeId' => $request->periode_id]);
    }

    public function detailPdf(Request $request)
    {
        $result = $this->ritaseService->detailPdf($request);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('ritase.detail-pdf', $result)
            ->setPaper('A4', 'landscape');

        $filename = 'detail-ritase-' . $result['periode']->nama_periode . '.pdf';

        if ($request->has('view')) {
            return view('ritase.detail-html', $result);
        }

        return $pdf->download($filename);
    }
}
