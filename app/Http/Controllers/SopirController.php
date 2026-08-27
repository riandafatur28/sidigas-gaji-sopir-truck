<?php

namespace App\Http\Controllers;

use App\Models\Sopir;
use App\Http\Requests\StoreSopirRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SopirController extends Controller
{
    /**
     * Display sopir index with search and stats.
     */
    public function index(Request $request): View
    {
        $search = $request->get('search', '');

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
     * Store a new sopir.
     */
    public function store(StoreSopirRequest $request): RedirectResponse
    {
        try {
            Sopir::create([
                'nama' => $request->nama,
                'status' => 'aktif',
            ]);

            return redirect()->back()
                ->with('success', "Sopir berhasil ditambahkan dengan kode otomatis!");
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan sopir: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing sopir.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'nama' => 'required|string|max:255|min:3',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        try {
            $sopir = Sopir::findOrFail($id);
            $sopir->update([
                'nama' => $request->nama,
                'status' => $request->status,
            ]);

            return redirect()->back()
                ->with('success', 'Data sopir berhasil diperbarui!');
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui sopir: ' . $e->getMessage());
        }
    }

    /**
     * Delete a sopir (only if no ritase records exist).
     */
    public function destroy(int $id): RedirectResponse
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
            report($e);
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
