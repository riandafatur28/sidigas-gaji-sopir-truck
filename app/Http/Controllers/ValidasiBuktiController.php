<?php

namespace App\Http\Controllers;

use App\Models\ValidasiBukti;
use App\Services\ValidasiBuktiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ValidasiBuktiController extends Controller
{
    public function __construct(
        private readonly ValidasiBuktiService $validasiService
    ) {}

    public function form()
    {
        return view('validasi-bukti.index', $this->validasiService->getFormData());
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

        $this->validasiService->submit($request);
        return redirect()->route('validasi-bukti.form')
            ->with('success', 'Bukti berhasil dikirim! Menunggu verifikasi mitra.');
    }

    public function kelola(Request $request)
    {
        return view('validasi-bukti.kelola', $this->validasiService->getKelolaData($request));
    }

    public function destroy($id)
    {
        try {
            ValidasiBukti::findOrFail($id)->delete();
            return redirect()->back()->with('success', 'Permintaan validasi berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    public function detail($id)
    {
        return view('validasi-bukti.detail', $this->validasiService->getDetailData($id));
    }

    public function setujui(Request $request, $id)
    {
        $this->validasiService->setujui($request, $id);
        return back()->with('success', 'Bukti disetujui. Silakan tambah ritase.');
    }

    public function tolak(Request $request, $id)
    {
        $request->validate(['catatan_mitra' => 'required|string|max:255']);
        $this->validasiService->tolak($request, $id);
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
            $kodeRitase = $this->validasiService->tambahRitase($request, $id);
            DB::commit();
            return redirect()->route('validasi-bukti.kelola')
                ->with('success', "Ritase $kodeRitase berhasil ditambahkan!");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function toggleAturan()
    {
        $status = !cache()->get('aturan_validasi_enabled', false);
        cache()->forever('aturan_validasi_enabled', $status);
        return back()->with('success', 'Aturan validasi bukti ' . ($status ? 'diaktifkan' : 'dinonaktifkan'));
    }
}
