<?php

namespace App\Services;

use App\Models\Periode;
use App\Models\PenggajianDetail;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use Illuminate\Http\Request;

class RitaseService
{
    /**
     * Clean destination name: remove filler words for compact display.
     */
    public function cleanTujuan(?string $nama): string
    {
        if (!$nama) return '?';
        $clean = preg_replace('/\b(paket|overlay|patching|cmm|kormuling|rekon)\b/i', '', $nama);
        $clean = preg_replace('/\s{2,}/', ' ', trim($clean));
        return $clean ?: $nama;
    }

    /**
     * Resolve upah per rit untuk ritase manual dari rate PenggajianDetail
     * periode yang sama; fallback ke detail terbaru lintas periode.
     */
    public function resolveUpahSopir($periodeId, $kodeTujuan)
    {
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
        return $upah ?? 0;
    }

    /**
     * Hitung DT berdasarkan aturan bisnis:
     * 1. Gagal Produksi → DT = 0
     * 2. 2 rit, kabupaten SAMA, waktu SAMA, sehari:
     *    - Nganjuk/Kediri/Kota Kediri/Jombang → DT 1x (rit ke-2 = 0)
     *    - Lainnya → DT 2x (rit ke-2 tetap 330.000)
     * 3. 2 rit, kabupaten SAMA, waktu BEDA, sehari → DT 2x
     * 4. 2 rit, kabupaten BEDA, waktu sama/beda, sehari → DT 2x
     */
    public function hitungDT($request, $excludeId = null)
    {
        if ($request->status === 'gagal_produksi') {
            return 0;
        }

        $kabSatuDt = config('dt.single_dt_regencies');
        $dtValue = config('dt.value', 330000);

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

        return $dtValue;
    }

    /**
     * Cek aturan sewa DT untuk ditampilkan ke user.
     */
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

    /**
     * Data detail ritase (pivot sopir x tanggal).
     */
    public function detailData(Request $request)
    {
        $periodeId = $request->get('periode');
        $search = $request->get('search', '');

        if (!$periodeId) {
            return ['sopirs' => [], 'dates' => [], 'data' => []];
        }

        $periode = Periode::find($periodeId);
        if (!$periode) {
            return ['sopirs' => [], 'dates' => [], 'data' => []];
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

        return [
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
        ];
    }

    /**
     * Generate PDF detail ritase per sopir.
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

        $kabSatuDt = config('dt.single_dt_regencies');
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

        return [
            'periode' => $periode,
            'sopirs' => $sopirs,
            'columns' => $columns,
            'data' => $data,
            'counts' => $counts,
            'dates' => $dates,
            'dayNames' => $dayNames,
        ];
    }
}
