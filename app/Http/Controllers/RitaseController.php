<?php

namespace App\Http\Controllers;

use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use Illuminate\Http\Request;

class RitaseController extends Controller
{
    /**
     * Clean destination name: remove filler words for compact display.
     */
    private function cleanTujuan(?string $nama): string
    {
        if (!$nama) return '?';
        $clean = preg_replace('/\b(paket|overlay|patching|cmm|kormuling)\b/i', '', $nama);
        $clean = preg_replace('/\s{2,}/', ' ', trim($clean));
        return $clean ?: $nama;
    }
    public function index(Request $request)
    {
        // Auto-sync: periode yg mencakup hari ini jadi aktif, lainnya selesai
        Periode::syncActiveStatus();

        $search = $request->get('search', '');
        $filterPeriode = $request->get('periode', '');
        $filterSopir = $request->get('sopir', '');
        $filterTujuan = $request->get('tujuan', '');
        $tanggal = $request->get('tanggal', '');

        // Default ke periode aktif
        if (!$filterPeriode) {
            $active = Periode::where('status', 'aktif')->first();
            if ($active) $filterPeriode = $active->id;
        }

        $periodes = Periode::orderBy('id', 'asc')->get();
        $sopirs = Sopir::orderBy('id', 'asc')->get();
        $tujuans = Tujuan::orderBy('id', 'asc')->get();

        // Base query for filtering (shared for table + stat cards)
        $ritBase = Ritase::with(['periode', 'sopir', 'tujuan']);

        if ($filterPeriode) {
            $ritBase->where('periode_id', $filterPeriode);
        }
        if ($tanggal) {
            $ritBase->whereDate('tanggal', $tanggal);
        }

        $ritases = (clone $ritBase)
            ->when($filterSopir, function ($query) use ($filterSopir) {
                $query->where('kode_sopir', $filterSopir);
            })
            ->when($filterTujuan, function ($query) use ($filterTujuan) {
                $query->where('kode_tujuan', $filterTujuan);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('kode_ritase', 'like', "%{$search}%")
                        ->orWhereHas('sopir', function ($sq) use ($search) {
                            $sq->where('nama', 'like', "%{$search}%");
                        })
                        ->orWhereHas('tujuan', function ($sq) use ($search) {
                            $sq->where('nama', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        // Stat counts filtered by periode + tanggal
        $statBase = Ritase::query();
        if ($filterPeriode) {
            $statBase->where('periode_id', $filterPeriode);
        }
        if ($tanggal) {
            $statBase->whereDate('tanggal', $tanggal);
        }
        $totalRitase = (clone $statBase)->count();
        $ritaseValid = (clone $statBase)->where('status', 'valid')->count();
        $ritasePending = (clone $statBase)->where('status', 'pending')->count();
        $ritaseGagal = (clone $statBase)->where('status', 'gagal_produksi')->count();
        $sopirTerlibat = (clone $statBase)->distinct('kode_sopir')->count('kode_sopir');

        return view('ritase.index', compact(
            'ritases',
            'periodes',
            'sopirs',
            'tujuans',
            'search',
            'filterPeriode',
            'filterSopir',
            'filterTujuan',
            'tanggal',
            'totalRitase',
            'ritaseValid',
            'ritasePending',
            'ritaseGagal',
            'sopirTerlibat'
        ));
    }

    public function store(Request $request)
    {
        $rules = [
            'periode_id' => 'required|exists:periodes,id',
            'kode_sopir' => 'required|exists:sopirs,kode_sopir',
            'kode_tujuan' => 'required|exists:tujuans,kode_tujuan',
            'tanggal' => 'required|date',
            'waktu' => 'required|in:pagi,malam',
            'kabupaten' => 'required|in:Nganjuk,Kediri,Kota Kediri,Jombang,Lainnya',
            'status' => 'required|in:valid,pending,gagal_produksi',
            'nominal_kompensasi' => 'nullable',
            'catatan' => 'nullable|string|max:500',
            'is_lembur' => 'nullable|in:0,1',
            'upah_lembur' => 'nullable|numeric|min:0',
        ];

        $validated = $request->validate($rules);
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

        // Auto-aktifkan sopir/tujuan kalo lagi dipake
        Sopir::where('kode_sopir', $request->kode_sopir)->where('status', '!=', 'aktif')->update(['status' => 'aktif']);
        Tujuan::where('kode_tujuan', $request->kode_tujuan)->where('status', '!=', 'aktif')->update(['status' => 'aktif']);

        // 🔥🔥🔥 HITUNG DT - PASTIKAN INI BERJALAN 🔥🔥🔥
        $dtValue = $this->hitungDT($request, null);

        // Debug: log nilai DT (hapus setelah berhasil)
        \Log::info('STORE - DT Value:', [
            'dt' => $dtValue,
            'status' => $request->status,
            'kabupaten' => $request->kabupaten,
            'kode_sopir' => $request->kode_sopir,
            'tanggal' => $request->tanggal,
            'waktu' => $request->waktu
        ]);

        $ritase = Ritase::create([
            'periode_id' => $request->periode_id,
            'kode_sopir' => $request->kode_sopir,
            'kode_tujuan' => $request->kode_tujuan,
            'tanggal' => $request->tanggal,
            'waktu' => $request->waktu,
            'kabupaten' => $request->kabupaten,
            'status' => $request->status,
            'dt' => $dtValue,
            'upah_sopir' => 0,
            'nominal_kompensasi' => $validated['nominal_kompensasi'],
            'catatan' => $request->catatan,
            'is_lembur' => $isLembur,
            'upah_lembur' => $upahLembur,
        ]);

        return redirect()->back()
            ->with('success', 'Ritase berhasil ditambahkan! DT: Rp ' . number_format($dtValue, 0, ',', '.'));
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'periode_id' => 'required|exists:periodes,id',
            'kode_sopir' => 'required|exists:sopirs,kode_sopir',
            'kode_tujuan' => 'required|exists:tujuans,kode_tujuan',
            'tanggal' => 'required|date',
            'waktu' => 'required|in:pagi,malam',
            'kabupaten' => 'required|in:Nganjuk,Kediri,Kota Kediri,Jombang,Lainnya',
            'status' => 'required|in:valid,pending,gagal_produksi',
            'nominal_kompensasi' => 'nullable',
            'catatan' => 'nullable|string|max:500',
            'is_lembur' => 'nullable|in:0,1',
            'upah_lembur' => 'nullable|numeric|min:0',
        ];

        $validated = $request->validate($rules);
        $validated['nominal_kompensasi'] = is_numeric($validated['nominal_kompensasi'] ?? 0) ? (float) $validated['nominal_kompensasi'] : 0;
        $isLembur = $request->boolean('is_lembur');
        $upahLembur = $isLembur ? (float) ($request->upah_lembur ?? 0) : 0;

        // Auto-aktifkan sopir/tujuan kalo lagi dipake
        Sopir::where('kode_sopir', $request->kode_sopir)->where('status', '!=', 'aktif')->update(['status' => 'aktif']);
        Tujuan::where('kode_tujuan', $request->kode_tujuan)->where('status', '!=', 'aktif')->update(['status' => 'aktif']);

        $ritase = Ritase::findOrFail($id);

        // HITUNG ULANG DT
        $dtValue = $this->hitungDT($request, $id);

        \Log::info('UPDATE - DT Value:', [
            'dt' => $dtValue,
            'status' => $request->status,
            'id' => $id
        ]);

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
            $ritase = Ritase::findOrFail($id);
            $ritase->delete();

            return redirect()->back()
                ->with('success', 'Data ritase berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * 🔥🔥🔥 FUNGSI HITUNG DT 🔥🔥🔥
     *
     * ATURAN (berdasarkan user):
     * 1. Gagal Produksi → DT = 0
     * 2. 2 rit, kabupaten SAMA, waktu SAMA, sehari:
     *    - Nganjuk/Kediri/Kota Kediri/Jombang → DT 1x (rit ke-2 = 0)
     *    - Lainnya → DT 2x (rit ke-2 tetap 330.000)
     * 3. 2 rit, kabupaten SAMA, waktu BEDA, sehari → DT 2x
     * 4. 2 rit, kabupaten BEDA, waktu sama/beda, sehari → DT 2x
     */
    private function hitungDT($request, $excludeId = null)
    {
        if ($request->status === 'gagal_produksi') {
            return 0;
        }

        $kabSatuDt = ['Nganjuk', 'Kediri', 'Kota Kediri', 'Jombang'];

        // Cek apakah sudah ada rit non-gagal dengan kabupaten & waktu SAMA
        $query = Ritase::where('kode_sopir', $request->kode_sopir)
            ->where('tanggal', $request->tanggal)
            ->where('kabupaten', $request->kabupaten)
            ->where('waktu', $request->waktu)
            ->where('status', '!=', 'gagal_produksi');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $ritLain = $query->first();

        // Hanya batasi 1 DT untuk kabupaten tertentu (selain Lainnya)
        if ($ritLain && in_array($request->kabupaten, $kabSatuDt)) {
            return 0;
        }

        return 330000;
    }

    public function cekAturanSewaDT(Request $request)
    {
        $kabupaten = $request->kabupaten;
        $waktu = $request->waktu;
        $status = $request->status;
        $kodeSopir = $request->kode_sopir;
        $tanggal = $request->tanggal;
        $nominalKompensasi = $request->nominal_kompensasi ?? 0;

        $dt = 0;
        $keterangan = '';
        $kabSatuDt = ['Nganjuk', 'Kediri', 'Kota Kediri', 'Jombang'];

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
                $dt = 330000;
                if ($ritLain) {
                    $keterangan = "✅ Rit ke-2 kabupaten {$kabupaten} waktu {$waktu} → DT Rp 330.000 (Lainnya/hitung 2x)";
                } else {
                    $keterangan = "✅ Rit pertama kabupaten {$kabupaten} waktu {$waktu} → DT Rp 330.000";
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

        return response()->json([
            'rit_keberapa' => $ritKeberapa,
            'sewa_dt' => $dt,
            'keterangan' => $keterangan,
            'kompensasi' => $nominalKompensasi,
        ]);
    }

    /**
     * Data detail ritase (pivot sopir x tanggal).
     */
    public function detailData(Request $request)
    {
        $periodeId = $request->get('periode');
        $search = $request->get('search', '');

        if (!$periodeId) {
            return response()->json(['sopirs' => [], 'dates' => [], 'data' => []]);
        }

        $periode = Periode::find($periodeId);
        if (!$periode) {
            return response()->json(['sopirs' => [], 'dates' => [], 'data' => []]);
        }

        // Generate all dates in the period
        $start = \Carbon\Carbon::parse($periode->tanggal_mulai);
        $end = \Carbon\Carbon::parse($periode->tanggal_selesai);
        $dates = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dates[] = $d->format('Y-m-d');
        }

        // Build columns: each date split into P (pagi) and M (malam)
        $columns = [];
        foreach ($dates as $ymd) {
            $columns[] = ['key' => $ymd . '_P', 'date' => $ymd, 'waktu' => 'P'];
            $columns[] = ['key' => $ymd . '_M', 'date' => $ymd, 'waktu' => 'M'];
        }

        // Get all ritase in this period
        $ritases = Ritase::with(['sopir', 'tujuan'])
            ->where('periode_id', $periodeId)
            ->when($search, function ($q) use ($search) {
                $q->whereHas('sopir', function ($sq) use ($search) {
                    $sq->where('nama', 'like', "%{$search}%");
                })->orWhereHas('tujuan', function ($sq) use ($search) {
                    $sq->where('nama', 'like', "%{$search}%");
                });
            })
            ->orderBy('tanggal', 'asc')
            ->get();

        // Collect unique sopirs, data keyed by column key (date+waktu), counts
        $sopirList = [];
        $data = [];
        $counts = []; // kode_sopir => ['ritase_berhasil' => ..., 'ritase_gagal' => ...]
        foreach ($ritases as $r) {
            if (!$r->sopir) continue;
            $sk = $r->kode_sopir;
            if (!isset($sopirList[$sk])) {
                $sopirList[$sk] = ['kode_sopir' => $sk, 'nama' => $r->sopir->nama];
                $counts[$sk] = ['ritase_berhasil' => 0, 'ritase_gagal' => 0];
            }
            $tgl = $r->tanggal->format('Y-m-d');
            $wkt = $r->waktu == 'pagi' ? 'P' : 'M';
            $colKey = $tgl . '_' . $wkt;
            $tujuanNama = $r->is_lembur ? 'Lembur ' . $this->cleanTujuan($r->tujuan?->nama) : $this->cleanTujuan($r->tujuan?->nama);

            if (!isset($data[$sk])) $data[$sk] = [];
            if (!isset($data[$sk][$colKey])) $data[$sk][$colKey] = [];
            $data[$sk][$colKey][] = $tujuanNama;

            // Count berhasil/gagal
            if ($r->status === 'gagal_produksi') {
                $counts[$sk]['ritase_gagal']++;
            } else {
                $counts[$sk]['ritase_berhasil']++;
            }
        }

        // Sort by kode_sopir
        $allSopirs = collect($sopirList)->sortBy('kode_sopir')->values();

        $total = $allSopirs->count();
        $perPage = 10;
        $page = max(1, (int) $request->get('page', 1));
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;

        $sopirs = $allSopirs->slice($offset, $perPage)->values();

        // Filter data for current page sopirs only
        $pageSopirKeys = $sopirs->pluck('kode_sopir')->toArray();
        $pageData = array_intersect_key($data, array_flip($pageSopirKeys));

        // Filter counts for current page
        $pageCounts = array_intersect_key($counts, array_flip($pageSopirKeys));

        return response()->json([
            'sopirs' => $sopirs,
            'columns' => $columns,
            'data' => $pageData,
            'counts' => $pageCounts,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ]);
    }

    /**
     * Show parser form (NER-based).
     */
    public function parserForm()
    {
        $periodes = Periode::orderBy('id', 'desc')->get();
        return view('ritase.parser', compact('periodes'));
    }

    /**
     * Process parsed text using NER-based matching.
     */
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
            collect($parsed['packages'])
                ->reject(fn($p) => !empty($p['is_bongkar']))
                ->pluck('route_name')->unique()->values()->all()
        );

        $results = [
            'date' => $parsed['date'],
            'packages' => $parsed['packages'],
            'driver_matches' => $driverMatches,
            'route_matches' => $routeMatches,
            'created' => 0,
            'skipped' => 0,
            'errors' => [],
            'details' => [],
        ];

        if ($request->boolean('auto_create')) {
            $createResult = $parser->createRitases($parsed, $request->periode_id, $driverMatches, $routeMatches);
            $results['created'] = $createResult['created'];
            $results['skipped'] = $createResult['skipped'];
            $results['errors'] = array_merge($results['errors'], $createResult['errors']);
            $results['details'] = $createResult['details'];

            // Auto-sync status sopir & tujuan setelah parsing
            \App\Models\Sopir::syncActiveStatus();
            \App\Models\Tujuan::syncActiveStatus();
        }

        return view('ritase.parser-result', [
            'results' => $results,
            'periodeId' => $request->periode_id,
        ]);
    }

    /**
     * Download PDF detail ritase per sopir.
     */
    public function detailPdf(Request $request)
    {
        $periodeId = $request->get('periode');
        if (!$periodeId) abort(404);

        $periode = Periode::findOrFail($periodeId);

        $start = \Carbon\Carbon::parse($periode->tanggal_mulai);
        $end = \Carbon\Carbon::parse($periode->tanggal_selesai);
        $dates = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dates[] = $d->format('Y-m-d');
        }

        // Build columns with dynamic sub-columns per waktu (P1,P2 / M1,M2)
        $ritases = Ritase::with(['sopir', 'tujuan'])
            ->where('periode_id', $periodeId)
            ->orderBy('tanggal', 'asc')
            ->get();

        $sopirList = [];
        $data = [];
        $counts = [];
        // Track max ritase per date+waktu across all sopirs
        $maxRitByWaktu = [];
        foreach ($dates as $ymd) {
            $maxRitByWaktu[$ymd] = ['P' => 0, 'M' => 0];
        }

        $kabSatuDt = ['Nganjuk', 'Kediri', 'Kota Kediri', 'Jombang'];
        // [sopir][tanggal_kabupaten_waktu] = first occurrence seen
        $firstRit = [];

        foreach ($ritases as $r) {
            if (!$r->sopir) continue;
            $sk = $r->kode_sopir;
            if (!isset($sopirList[$sk])) {
                $sopirList[$sk] = ['kode_sopir' => $sk, 'nama' => $r->sopir->nama];
                $counts[$sk] = ['total' => 0, 'gagal' => 0, 'eligible' => 0];
            }
            $tgl = $r->tanggal->format('Y-m-d');
            $wkt = $r->waktu == 'pagi' ? 'P' : 'M';
            $colKey = $tgl . '_' . $wkt;
            $tujuanNama = $r->is_lembur ? 'Lembur ' . $this->cleanTujuan($r->tujuan?->nama) : $this->cleanTujuan($r->tujuan?->nama);
            if (!isset($data[$sk])) $data[$sk] = [];
            if (!isset($data[$sk][$colKey])) $data[$sk][$colKey] = [];
            $data[$sk][$colKey][] = $tujuanNama;

            // Track max count per date+waktu
            $c = count($data[$sk][$colKey]);
            if ($c > $maxRitByWaktu[$tgl][$wkt]) {
                $maxRitByWaktu[$tgl][$wkt] = $c;
            }

            // Hitung DT eligibility per sopir per (date + kab + waktu)
            $counts[$sk]['total']++;
            if ($r->status === 'gagal_produksi') {
                $counts[$sk]['gagal']++;
            } else {
                // DT rules: hanya 1 DT per (kab+waktu) utk kabSatuDt
                $dkw = $tgl . '_' . ($r->kabupaten ?? '') . '_' . $wkt;
                $isFirst = empty($firstRit[$sk][$dkw]);
                $firstRit[$sk][$dkw] = true;
                $eligibleKab = in_array($r->kabupaten, $kabSatuDt);
                if (!$eligibleKab || $isFirst) {
                    $counts[$sk]['eligible']++;
                }
            }
        }

        // Build columns — multiple sub-columns per waktu if max > 1
        $columns = [];
        foreach ($dates as $ymd) {
            $pCount = $maxRitByWaktu[$ymd]['P'];
            $mCount = $maxRitByWaktu[$ymd]['M'];
            for ($i = 1; $i <= max($pCount, 1); $i++) {
                $label = $pCount > 1 ? "P{$i}" : 'P';
                $columns[] = ['key' => $ymd . "_P_" . ($i-1), 'date' => $ymd, 'waktu' => 'P', 'rit_idx' => $i-1, 'label' => $label];
            }
            for ($i = 1; $i <= max($mCount, 1); $i++) {
                $label = $mCount > 1 ? "M{$i}" : 'M';
                $columns[] = ['key' => $ymd . "_M_" . ($i-1), 'date' => $ymd, 'waktu' => 'M', 'rit_idx' => $i-1, 'label' => $label];
            }
        }

        $sopirs = collect($sopirList)->sortBy('kode_sopir')->values()->all();

        $dayNames = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('ritase.detail-pdf', compact(
            'periode', 'sopirs', 'columns', 'data', 'counts', 'dates', 'dayNames'
        ))->setPaper('A4', 'landscape');

        $filename = 'detail-ritase-' . $periode->nama_periode . '.pdf';

        if ($request->has('view')) {
            return view('ritase.detail-html', compact(
                'periode', 'sopirs', 'columns', 'data', 'counts', 'dates', 'dayNames'
            ));
        }

        return $pdf->download($filename);
    }
}
