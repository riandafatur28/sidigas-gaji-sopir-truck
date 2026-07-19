<?php

namespace App\Http\Controllers;

use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Models\ValidasiBukti;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ValidasiBuktiController extends Controller
{
    public function form()
    {
        $sopirs = Sopir::orderBy('nama')->get(['kode_sopir', 'nama']);
        $tujuans = Tujuan::orderBy('nama')->get(['kode_tujuan', 'nama']);
        return view('validasi-bukti.index', compact('sopirs', 'tujuans'));
    }

    public function submit(Request $request)
    {
        $request->validate([
            'nama_sopir' => 'required|string|max:100',
            'kode_sopir' => 'nullable|exists:sopirs,kode_sopir',
            'sopir_baru' => 'boolean',
            'nama_tujuan' => 'required|string|max:100',
            'kode_tujuan' => 'nullable|exists:tujuans,kode_tujuan',
            'tujuan_baru' => 'boolean',
            'foto' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'lokasi' => 'nullable|string|max:255',
            'waktu_foto' => 'required|date',
            'tanggal' => 'required|date',
            'catatan' => 'nullable|string',
        ]);

        $foto = $request->foto;
        $ext = 'jpg';
        if (str_starts_with($foto, 'data:image/png')) $ext = 'png';
        $foto = preg_replace('/^data:image\/\w+;base64,/', '', $foto);
        $foto = str_replace(' ', '+', $foto);
        $fotoData = base64_decode($foto);

        $fileName = 'bukti/' . uniqid() . '.' . $ext;
        Storage::disk('public')->put($fileName, $fotoData);

        $periode = Periode::where('tanggal_mulai', '<=', $request->tanggal)
            ->where('tanggal_selesai', '>=', $request->tanggal)
            ->first();

        ValidasiBukti::create([
            'kode_sopir' => $request->kode_sopir,
            'nama_sopir' => $request->nama_sopir,
            'sopir_baru' => $request->boolean('sopir_baru'),
            'kode_tujuan' => $request->kode_tujuan,
            'nama_tujuan' => $request->nama_tujuan,
            'tujuan_baru' => $request->boolean('tujuan_baru'),
            'foto' => $fileName,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'lokasi' => $request->lokasi,
            'waktu_foto' => $request->waktu_foto,
            'tanggal' => $request->tanggal,
            'periode_id' => $periode?->id,
            'catatan' => $request->catatan,
            'status' => 'pending',
        ]);

        return redirect()->route('validasi-bukti.form')
            ->with('success', 'Bukti berhasil dikirim! Menunggu verifikasi mitra.');
    }

    public function kelola(Request $request)
    {
        $status = $request->get('status', 'pending');
        $list = ValidasiBukti::with(['sopir', 'tujuan', 'periode'])
            ->when($status !== 'semua', fn($q) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('validasi-bukti.kelola', compact('list', 'status'));
    }

    public function detail($id)
    {
        $item = ValidasiBukti::with(['sopir', 'tujuan', 'periode'])->findOrFail($id);
        $sopirs = Sopir::orderBy('nama')->get(['kode_sopir', 'nama']);
        $tujuans = Tujuan::orderBy('nama')->get(['kode_tujuan', 'nama']);
        return view('validasi-bukti.detail', compact('item', 'sopirs', 'tujuans'));
    }

    public function setujui(Request $request, $id)
    {
        $item = ValidasiBukti::findOrFail($id);

        $kodeSopir = $item->kode_sopir;
        $kodeTujuan = $item->kode_tujuan;

        if ($item->sopir_baru && !$kodeSopir) {
            $sopir = Sopir::create(['nama' => $item->nama_sopir, 'status' => 'aktif']);
            $kodeSopir = $sopir->kode_sopir;
        }

        if ($item->tujuan_baru && !$kodeTujuan) {
            $tujuan = Tujuan::create(['nama' => $item->nama_tujuan, 'status' => 'aktif']);
            $kodeTujuan = $tujuan->kode_tujuan;
        }

        $item->update([
            'kode_sopir' => $kodeSopir,
            'kode_tujuan' => $kodeTujuan,
            'status' => 'disetujui',
            'catatan_mitra' => $request->catatan_mitra,
        ]);

        return back()->with('success', 'Bukti disetujui. Silakan tambah ritase.');
    }

    public function tolak(Request $request, $id)
    {
        $request->validate(['catatan_mitra' => 'required|string|max:255']);
        $item = ValidasiBukti::findOrFail($id);
        $item->update([
            'status' => 'ditolak',
            'catatan_mitra' => $request->catatan_mitra,
        ]);
        return back()->with('success', 'Bukti ditolak.');
    }

    public function tambahRitase(Request $request, $id)
    {
        $request->validate([
            'kode_sopir' => 'required|exists:sopirs,kode_sopir',
            'kode_tujuan' => 'required|exists:tujuans,kode_tujuan',
            'tanggal' => 'required|date',
            'waktu' => 'required|in:pagi,malam',
            'kabupaten' => 'required|in:Nganjuk,Kediri,Kota Kediri,Jombang,Lainnya',
        ]);

        DB::beginTransaction();
        try {
            $item = ValidasiBukti::findOrFail($id);

            $periode = $item->periode_id
                ? \App\Models\Periode::find($item->periode_id)
                : \App\Models\Periode::where('tanggal_mulai', '<=', $request->tanggal)
                    ->where('tanggal_selesai', '>=', $request->tanggal)
                    ->first();

            if (!$periode) {
                return back()->with('error', 'Tidak ada periode yang mencakup tanggal ini. Buat periode terlebih dahulu.');
            }

            $lastRit = Ritase::orderBy('id', 'desc')->first();
            $newNumber = $lastRit ? (int) substr($lastRit->kode_ritase, 4) + 1 : 1;
            $kodeRitase = 'RIT-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

            $dt = $this->hitungDt($request->kode_sopir, $request->tanggal, $request->kabupaten, $request->waktu);

            Ritase::create([
                'kode_ritase' => $kodeRitase,
                'periode_id' => $periode->id,
                'kode_sopir' => $request->kode_sopir,
                'kode_tujuan' => $request->kode_tujuan,
                'tanggal' => $request->tanggal,
                'waktu' => $request->waktu,
                'kabupaten' => $request->kabupaten,
                'status' => 'valid',
                'dt' => $dt,
                'upah_sopir' => 0,
                'nominal_kompensasi' => 0,
                'catatan' => $item->catatan,
            ]);

            $item->update(['status' => 'disetujui']);

            DB::commit();
            return redirect()->route('validasi-bukti.kelola')
                ->with('success', "Ritase $kodeRitase berhasil ditambahkan!");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    private function hitungDt($kodeSopir, $tanggal, $kabupaten, $waktu)
    {
        $exists = Ritase::where('kode_sopir', $kodeSopir)
            ->where('tanggal', $tanggal)
            ->where('kabupaten', $kabupaten)
            ->where('waktu', $waktu)
            ->where('status', '!=', 'gagal_produksi')
            ->exists();

        return $exists ? 0 : 330000;
    }

    public function toggleAturan()
    {
        $status = !cache()->get('aturan_validasi_enabled', false);
        cache()->forever('aturan_validasi_enabled', $status);
        return back()->with('success', 'Aturan validasi bukti ' . ($status ? 'diaktifkan' : 'dinonaktifkan'));
    }
}
