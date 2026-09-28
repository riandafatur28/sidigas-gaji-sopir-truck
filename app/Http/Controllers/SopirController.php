<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Ritase;
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
        Sopir::syncActiveStatus();

        $search = $request->get('search', '');
        $statusFilter = $request->get('status', '');

        $base = Sopir::where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('kode_sopir', 'like', "%{$search}%");
                })
                ->when($statusFilter, fn($q) => $q->where('status', $statusFilter));

        $sopirs = (clone $base)->orderBy('id', 'asc')
                ->paginate(10)
                ->withQueryString();

        $totalSopir = (clone $base)->count();
        $sopirAktif = (clone $base)->where('status', 'aktif')->count();
        $sopirNonaktif = (clone $base)->where('status', 'nonaktif')->count();
        $totalRitase = Ritase::count();

        return view('sopir.index', compact('sopirs', 'search', 'statusFilter', 'totalSopir', 'sopirAktif', 'sopirNonaktif', 'totalRitase'));
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
