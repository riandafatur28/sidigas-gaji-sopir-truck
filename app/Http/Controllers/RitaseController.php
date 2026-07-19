<?php

namespace App\Http\Controllers;

use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use Illuminate\Http\Request;

class RitaseController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $filterPeriode = $request->get('periode', '');
        $filterSopir = $request->get('sopir', '');

        $periodes = Periode::orderBy('id', 'asc')->get();
        $sopirs = Sopir::where('status', 'aktif')->orderBy('id', 'asc')->get();

        $ritases = Ritase::with(['periode', 'sopir', 'tujuan'])
            ->when($filterPeriode, function ($query) use ($filterPeriode) {
                $query->where('periode_id', $filterPeriode);
            })
            ->when($filterSopir, function ($query) use ($filterSopir) {
                $query->where('kode_sopir', $filterSopir);
            })
            ->when($search, function ($query) use ($search) {
                $query->where('kode_ritase', 'like', "%{$search}%")
                    ->orWhereHas('sopir', function ($q) use ($search) {
                        $q->where('nama', 'like', "%{$search}%");
                    })
                    ->orWhereHas('tujuan', function ($q) use ($search) {
                        $q->where('nama', 'like', "%{$search}%");
                    });
            })
            ->orderBy('id', 'asc')
            ->paginate(15)
            ->withQueryString();

        $totalRitase = Ritase::count();
        $ritaseValid = Ritase::where('status', 'valid')->count();
        $ritasePending = Ritase::where('status', 'pending')->count();
        $ritaseGagal = Ritase::where('status', 'gagal_produksi')->count();

        return view('ritase.index', compact(
            'ritases',
            'periodes',
            'sopirs',
            'search',
            'filterPeriode',
            'filterSopir',
            'totalRitase',
            'ritaseValid',
            'ritasePending',
            'ritaseGagal'
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
        ];

        $validated = $request->validate($rules);
        $validated['nominal_kompensasi'] = is_numeric($validated['nominal_kompensasi'] ?? 0) ? (float) $validated['nominal_kompensasi'] : 0;

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
        ]);

        return redirect()->route('ritase.index')
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
        ];

        $validated = $request->validate($rules);
        $validated['nominal_kompensasi'] = is_numeric($validated['nominal_kompensasi'] ?? 0) ? (float) $validated['nominal_kompensasi'] : 0;

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
        ]);

        return redirect()->route('ritase.index')
            ->with('success', 'Data ritase berhasil diperbarui! DT: Rp ' . number_format($dtValue, 0, ',', '.'));
    }

    public function destroy($id)
    {
        try {
            $ritase = Ritase::findOrFail($id);
            $ritase->delete();

            return redirect()->route('ritase.index')
                ->with('success', 'Data ritase berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->route('ritase.index')
                ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * 🔥🔥🔥 FUNGSI HITUNG DT (DIPERBAIKI) 🔥🔥🔥
     *
     * ATURAN:
     * 1. Status = 'gagal_produksi' → DT = 0
     * 2. Kabupaten = 'Lainnya' → TETAP DAPAT DT (Rp 330.000)
     * 3. Cek rit lain di tanggal yang SAMA dengan kabupaten SAMA dan waktu SAMA:
     *    - Jika ada → DT = 0 (hitung 1x)
     *    - Jika tidak ada → DT = 330.000
     */
    private function hitungDT($request, $excludeId = null)
    {
        // 🔥 Jika status Gagal Produksi → DT = 0
        if ($request->status === 'gagal_produksi') {
            return 0;
        }

        // 🔥🔥🔥 KABUPATEN LAINNYA TETAP DAPAT DT 🔥🔥🔥
        // HAPUS SEMUA PENGECEKAN KABUPATEN LAINNYA
        // if ($request->kabupaten === 'Lainnya') {
        //     return 0; // ← INI YANG HARUS DIHAPUS!
        // }

        // Cari rit lain dengan kriteria:
        // - Sopir sama
        // - Tanggal sama
        // - Kabupaten SAMA
        // - Waktu SAMA
        // - Status BUKAN gagal_produksi
        $query = Ritase::where('kode_sopir', $request->kode_sopir)
            ->where('tanggal', $request->tanggal)
            ->where('kabupaten', $request->kabupaten)
            ->where('waktu', $request->waktu)
            ->where('status', '!=', 'gagal_produksi');

        // Jika update, exclude ID sendiri
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $ritLain = $query->first();

        // 🔥 Jika ada rit lain dengan kabupaten & waktu SAMA → DT = 0 (hitung 1x)
        if ($ritLain) {
            return 0;
        }

        // 🔥 Default: DT = 330.000
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

        // HITUNG DT
        $dt = 0;
        $keterangan = '';

        // Jika status Gagal Produksi → DT = 0
        if ($status === 'gagal_produksi') {
            $dt = 0;
            $keterangan = '❌ Gagal Produksi → Tidak dapat DT';
        }
        // 🔥🔥🔥 KABUPATEN LAINNYA TETAP DAPAT DT 🔥🔥🔥
        // HAPUS PENGECEKAN INI
        // else if ($kabupaten === 'Lainnya') {
        //     $dt = 0;
        //     $keterangan = '❌ Kabupaten Lainnya → Tidak dapat DT';
        // }
        else {
            // Cek rit lain di tanggal yang SAMA dengan kabupaten SAMA dan waktu SAMA
            $ritLain = Ritase::where('kode_sopir', $kodeSopir)
                ->where('tanggal', $tanggal)
                ->where('kabupaten', $kabupaten)
                ->where('waktu', $waktu)
                ->where('status', '!=', 'gagal_produksi')
                ->first();

            if ($ritLain) {
                $dt = 0;
                $keterangan = "⚠️ Rit ke-2 dengan kabupaten {$kabupaten} waktu {$waktu} (hitung 1x)";
            } else {
                $dt = 330000;
                if ($kabupaten === 'Lainnya') {
                    $keterangan = "✅ Kabupaten Lainnya → DT 1x Rp 330.000";
                } else {
                    $keterangan = "✅ Rit pertama dengan kabupaten {$kabupaten} waktu {$waktu} → DT 1x Rp 330.000";
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
}
