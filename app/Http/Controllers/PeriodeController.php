<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Models\ValidasiBukti;
use App\Http\Requests\StorePeriodeRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PeriodeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $statusFilter = $request->get('status', '');

        $base = Periode::where(function ($q) use ($search) {
                    $q->where('nama_periode', 'like', "%{$search}%")
                      ->orWhere('kode_periode', 'like', "%{$search}%");
                })
                ->when($statusFilter, fn($q) => $q->where('status', $statusFilter));

        $periodes = (clone $base)->orderBy('id', 'asc')
                ->paginate(10)
                ->withQueryString();

        $totalPeriode = (clone $base)->count();
        $periodeAktif = (clone $base)->where('status', 'aktif')->count();
        $periodeSelesai = (clone $base)->where('status', 'selesai')->count();
        $totalRitase = Ritase::count();

        return view('periode.index', compact(
            'periodes',
            'search',
            'statusFilter',
            'totalPeriode',
            'periodeAktif',
            'periodeSelesai',
            'totalRitase'
        ));
    }

    public function store(StorePeriodeRequest $request)
    {
        // Cek overlap periode
        $overlap = Periode::where(function($q) use ($request) {
            $q->whereBetween('tanggal_mulai', [$request->tanggal_mulai, $request->tanggal_selesai])
              ->orWhereBetween('tanggal_selesai', [$request->tanggal_mulai, $request->tanggal_selesai])
              ->orWhere(function($q2) use ($request) {
                  $q2->where('tanggal_mulai', '<=', $request->tanggal_mulai)
                     ->where('tanggal_selesai', '>=', $request->tanggal_selesai);
              });
        })->exists();

        if ($overlap) {
            return redirect()->back()
                ->with('error', 'Periode ini beririsan dengan periode yang sudah ada!')
                ->withInput();
        }

        $periode = Periode::create([
            'nama_periode' => $request->nama_periode,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'status' => 'aktif',
        ]);

        $this->lampirkanBuktiTanpaPeriode($periode);

        return redirect()->back()
            ->with('success', 'Periode berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_periode' => 'required|string|max:255|min:3',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status' => 'required|in:aktif,selesai',
        ]);

        $periode = Periode::findOrFail($id);
        $periode->update([
            'nama_periode' => $request->nama_periode,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'status' => $request->status,
        ]);

        $this->lampirkanBuktiTanpaPeriode($periode);

        return redirect()->back()
            ->with('success', 'Data periode berhasil diperbarui!');
    }

    /**
     * Lampirkan bukti validasi yang belum punya periode
     * dan tanggalnya masuk rentang periode ini.
     */
    private function lampirkanBuktiTanpaPeriode(Periode $periode): void
    {
        ValidasiBukti::whereNull('periode_id')
            ->whereBetween('tanggal', [$periode->tanggal_mulai, $periode->tanggal_selesai])
            ->update(['periode_id' => $periode->id]);
    }

    public function destroy($id)
    {
        try {
            $periode = Periode::findOrFail($id);

            if ($periode->ritase()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Periode tidak dapat dihapus karena sudah memiliki data ritase!');
            }

            if ($periode->validasiBukti()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Periode tidak dapat dihapus karena sudah memiliki data validasi bukti!');
            }

            $periode->delete();

            return redirect()->back()
                ->with('success', 'Data periode berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
