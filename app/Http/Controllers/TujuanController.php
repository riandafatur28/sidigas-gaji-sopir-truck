<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Ritase;
use App\Models\Tujuan;
use App\Http\Requests\StoreTujuanRequest;
use App\Services\RitaseService;
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
        Tujuan::syncActiveStatus();

        $search = $request->get('search', '');
        $statusFilter = $request->get('status', '');

        $base = Tujuan::where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('kode_tujuan', 'like', "%{$search}%");
                })
                ->when($statusFilter, fn($q) => $q->where('status', $statusFilter));

        $tujuans = (clone $base)->orderBy('id', 'asc')
                ->paginate(10)
                ->withQueryString();

        $totalTujuan = (clone $base)->count();
        $tujuanAktif = (clone $base)->where('status', 'aktif')->count();
        $tujuanNonaktif = (clone $base)->where('status', 'nonaktif')->count();
        $totalRitase = Ritase::count();

        return view('tujuan.index', compact('tujuans', 'search', 'statusFilter', 'totalTujuan', 'tujuanAktif', 'tujuanNonaktif', 'totalRitase'));
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
            $namaLama = $tujuan->nama;
            $tujuan->update([
                'nama' => $request->nama,
                'status' => $request->status,
            ]);

            $msg = 'Data tujuan berhasil diperbarui!';
            if ($namaLama !== $request->nama) {
                $sync = app(RitaseService::class)->syncKabupatenForTujuan($tujuan->kode_tujuan, $request->nama);
                $msg .= " Kabupaten ritase terkait disesuaikan menjadi {$sync['kabupaten']} ({$sync['ritase']} ritase, {$sync['gaji']} data gaji).";
            }

            return redirect()->back()
                ->with('success', $msg);
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
