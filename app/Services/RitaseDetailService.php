<?php

namespace App\Services;

use App\Models\Periode;
use App\Models\Ritase;
use Illuminate\Http\Request;

/**
 * Detail view & PDF data for ritase pivot table (sopir × tanggal).
 */
class RitaseDetailService
{
    public function detailData(Request $request): array
    {
        $periodeId = $request->get('periode');
        $search = $request->get('search', '');

        if (!$periodeId || !($periode = Periode::find($periodeId))) {
            return ['sopirs' => [], 'dates' => [], 'data' => []];
        }

        $start = \Carbon\Carbon::parse($periode->tanggal_mulai);
        $end = \Carbon\Carbon::parse($periode->tanggal_selesai);
        $dates = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dates[] = $d->format('Y-m-d');
        }

        $columns = [];
        foreach ($dates as $ymd) {
            $columns[] = ['key' => $ymd . '_P', 'date' => $ymd, 'waktu' => 'P'];
            $columns[] = ['key' => $ymd . '_M', 'date' => $ymd, 'waktu' => 'M'];
        }

        $ritases = Ritase::with(['sopir', 'tujuan'])
            ->where('periode_id', $periodeId)
            ->when($search, function ($q) use ($search) {
                $q->whereHas('sopir', fn($sq) => $sq->where('nama', 'like', "%{$search}%"))
                    ->orWhereHas('tujuan', fn($sq) => $sq->where('nama', 'like', "%{$search}%"));
            })
            ->orderBy('tanggal', 'asc')
            ->get();

        $sopirList = $data = $counts = [];
        foreach ($ritases as $r) {
            if (!$r->sopir) continue;
            $sk = $r->kode_sopir;
            if (!isset($sopirList[$sk])) {
                $sopirList[$sk] = ['kode_sopir' => $sk, 'nama' => $r->sopir->nama];
                $counts[$sk] = ['ritase_berhasil' => 0, 'ritase_gagal' => 0];
            }
            $colKey = $r->tanggal->format('Y-m-d') . '_' . ($r->waktu == 'pagi' ? 'P' : 'M');
            $tujuanNama = $r->is_lembur ? 'Lembur ' . $this->cleanTujuan($r->tujuan?->nama) : $this->cleanTujuan($r->tujuan?->nama);
            $data[$sk][$colKey][] = $tujuanNama;
            $counts[$sk][$r->status === 'gagal_produksi' ? 'ritase_gagal' : 'ritase_berhasil']++;
        }

        $allSopirs = collect($sopirList)->sortBy('kode_sopir')->values();
        $total = $allSopirs->count();
        $perPage = 10;
        $page = min(max(1, (int) $request->get('page', 1)), max(1, (int) ceil($total / $perPage)));
        $offset = ($page - 1) * $perPage;
        $sopirs = $allSopirs->slice($offset, $perPage)->values();
        $pageKeys = $sopirs->pluck('kode_sopir')->toArray();

        return [
            'sopirs' => $sopirs, 'columns' => $columns,
            'data' => array_intersect_key($data, array_flip($pageKeys)),
            'counts' => array_intersect_key($counts, array_flip($pageKeys)),
            'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => max(1, (int) ceil($total / $perPage))],
        ];
    }

    public function detailPdf(Request $request): array
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

        $ritases = Ritase::with(['sopir', 'tujuan'])->where('periode_id', $periodeId)->orderBy('tanggal', 'asc')->get();
        $sopirList = $data = $counts = [];
        $maxRitByWaktu = array_fill_keys($dates, ['P' => 0, 'M' => 0]);
        $kabSatuDt = config('dt.single_dt_regencies');
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
            $tujuanNama = $r->is_lembur ? 'Lembur ' . $this->cleanTujuan($r->tujuan?->nama) : $this->cleanTujuan($r->tujuan?->nama);
            $data[$sk][$tgl . '_' . $wkt][] = $tujuanNama;

            $c = count($data[$sk][$tgl . '_' . $wkt]);
            if ($c > $maxRitByWaktu[$tgl][$wkt]) $maxRitByWaktu[$tgl][$wkt] = $c;

            $counts[$sk]['total']++;
            if ($r->status === 'gagal_produksi') {
                $counts[$sk]['gagal']++;
            } else {
                $dkw = $tgl . '_' . ($r->kabupaten ?? '') . '_' . $wkt;
                $isFirst = empty($firstRit[$sk][$dkw]);
                $firstRit[$sk][$dkw] = true;
                if (!in_array($r->kabupaten, $kabSatuDt) || $isFirst) $counts[$sk]['eligible']++;
            }
        }

        $columns = [];
        foreach ($dates as $ymd) {
            for ($i = 0; $i < max($maxRitByWaktu[$ymd]['P'], 1); $i++) {
                $label = $maxRitByWaktu[$ymd]['P'] > 1 ? "P" . ($i + 1) : 'P';
                $columns[] = ['key' => "{$ymd}_P_{$i}", 'date' => $ymd, 'waktu' => 'P', 'rit_idx' => $i, 'label' => $label];
            }
            for ($i = 0; $i < max($maxRitByWaktu[$ymd]['M'], 1); $i++) {
                $label = $maxRitByWaktu[$ymd]['M'] > 1 ? "M" . ($i + 1) : 'M';
                $columns[] = ['key' => "{$ymd}_M_{$i}", 'date' => $ymd, 'waktu' => 'M', 'rit_idx' => $i, 'label' => $label];
            }
        }

        return [
            'periode' => $periode, 'sopirs' => collect($sopirList)->sortBy('kode_sopir')->values()->all(),
            'columns' => $columns, 'data' => $data, 'counts' => $counts,
            'dates' => $dates, 'dayNames' => ['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
        ];
    }

    public function cleanTujuan(?string $nama): string
    {
        if (!$nama) return '?';
        $clean = preg_replace('/\b(paket|overlay|patching|cmm|kormuling|rekon)\b/i', '', $nama);
        return preg_replace('/\s{2,}/', ' ', trim($clean)) ?: $nama;
    }
}
