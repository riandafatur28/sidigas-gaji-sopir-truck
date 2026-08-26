<?php

namespace App\Http\Controllers;

use App\Models\Sopir;
use App\Http\Requests\StoreSopirRequest;
use Illuminate\Http\Request;

class SopirController extends Controller
{
    /**
     * Tampilkan halaman Kelola Sopir
     */
    public function index(Request $request)
    {
        $search = $request->get('search', '');

        // Urutan ASC (ID paling awal di atas) + Pagination 10 per halaman
        $sopirs = Sopir::where('nama', 'like', "%{$search}%")
            ->orWhere('kode_sopir', 'like', "%{$search}%")
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        $totalSopir = Sopir::count();
        $sopirAktif = Sopir::aktif()->count();
        $sopirNonaktif = Sopir::nonaktif()->count();

        return view('sopir.index', compact('sopirs', 'search', 'totalSopir', 'sopirAktif', 'sopirNonaktif'));
    }

    /**
     * Simpan sopir baru
     */
    public function store(StoreSopirRequest $request)
    {
        Sopir::create([
            'nama' => $request->nama,
            'status' => 'aktif',
        ]);

        return redirect()->back()
            ->with('success', "Sopir berhasil ditambahkan dengan kode otomatis!");
    }

    /**
     * Update sopir
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255|min:3',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        $sopir = Sopir::findOrFail($id);
        $sopir->update([
            'nama' => $request->nama,
            'status' => $request->status,
        ]);

        return redirect()->back()
            ->with('success', 'Data sopir berhasil diperbarui!');
    }

    /**
     * Hapus sopir
     */
    public function destroy($id)
    {
        try {
            $sopir = Sopir::findOrFail($id);

            if ($sopir->ritase()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Sopir tidak dapat dihapus karena sudah memiliki data ritase!');
            }

            $sopir->delete();

            return redirect()->back()
                ->with('success', 'Data sopir berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
