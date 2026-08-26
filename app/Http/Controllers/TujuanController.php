<?php

namespace App\Http\Controllers;

use App\Models\Tujuan;
use App\Http\Requests\StoreTujuanRequest;
use Illuminate\Http\Request;

class TujuanController extends Controller
{
    /**
     * Tampilkan halaman Kelola Tujuan
     */
    public function index(Request $request)
    {
        $search = $request->get('search', '');

        $tujuans = Tujuan::where('nama', 'like', "%{$search}%")
            ->orWhere('kode_tujuan', 'like', "%{$search}%")
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        $totalTujuan = Tujuan::count();
        $tujuanAktif = Tujuan::aktif()->count();
        $tujuanNonaktif = Tujuan::nonaktif()->count();

        return view('tujuan.index', compact('tujuans', 'search', 'totalTujuan', 'tujuanAktif', 'tujuanNonaktif'));
    }

    /**
     * Simpan tujuan baru
     */
    public function store(StoreTujuanRequest $request)
    {
        Tujuan::create([
            'nama' => $request->nama,
            'status' => 'aktif',
        ]);

        return redirect()->back()
            ->with('success', "Tujuan berhasil ditambahkan dengan kode otomatis!");
    }

    /**
     * Update tujuan
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255|min:3',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        $tujuan = Tujuan::findOrFail($id);
        $tujuan->update([
            'nama' => $request->nama,
            'status' => $request->status,
        ]);

        return redirect()->back()
            ->with('success', 'Data tujuan berhasil diperbarui!');
    }

    /**
     * Hapus tujuan
     */
    public function destroy($id)
    {
        try {
            $tujuan = Tujuan::findOrFail($id);

            if ($tujuan->ritase()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Tujuan tidak dapat dihapus karena sudah memiliki data ritase!');
            }

            $tujuan->delete();

            return redirect()->back()
                ->with('success', 'Data tujuan berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
