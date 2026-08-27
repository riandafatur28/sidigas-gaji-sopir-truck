<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tujuan;
use App\Http\Requests\StoreTujuanRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TujuanController extends Controller
{
    /**
     * Display tujuan index with search and stats.
     */
    public function index(Request $request): View
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
     * Store a new tujuan.
     */
    public function store(StoreTujuanRequest $request): RedirectResponse
    {
        try {
            Tujuan::create([
                'nama' => $request->nama,
                'status' => 'aktif',
            ]);

            return redirect()->back()
                ->with('success', "Tujuan berhasil ditambahkan dengan kode otomatis!");
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan tujuan: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing tujuan.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'nama' => 'required|string|max:255|min:3',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        try {
            $tujuan = Tujuan::findOrFail($id);
            $tujuan->update([
                'nama' => $request->nama,
                'status' => $request->status,
            ]);

            return redirect()->back()
                ->with('success', 'Data tujuan berhasil diperbarui!');
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui tujuan: ' . $e->getMessage());
        }
    }

    /**
     * Delete a tujuan (only if no ritase records exist).
     */
    public function destroy(int $id): RedirectResponse
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
            report($e);
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
