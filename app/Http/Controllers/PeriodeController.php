<?php

namespace App\Http\Controllers;

use App\Models\Periode;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PeriodeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');

        $periodes = Periode::where('nama_periode', 'like', "%{$search}%")
            ->orWhere('kode_periode', 'like', "%{$search}%")
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        $totalPeriode = Periode::count();
        $periodeAktif = Periode::where('status', 'aktif')->count();
        $periodeSelesai = Periode::where('status', 'selesai')->count();

        return view('periode.index', compact(
            'periodes',
            'search',
            'totalPeriode',
            'periodeAktif',
            'periodeSelesai'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_periode' => 'required|string|max:255|min:3',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ], [
            'nama_periode.required' => 'Nama periode wajib diisi.',
            'nama_periode.min' => 'Nama minimal 3 karakter.',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_selesai.required' => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);

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

        Periode::create([
            'nama_periode' => $request->nama_periode,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'status' => 'aktif',
        ]);

        return redirect()->route('periode.index')
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

        return redirect()->route('periode.index')
            ->with('success', 'Data periode berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $periode = Periode::findOrFail($id);

        // Cek apakah periode sudah punya ritase
        if ($periode->ritases()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Periode tidak dapat dihapus karena sudah memiliki data ritase!');
        }

        $periode->delete();

        return redirect()->route('periode.index')
            ->with('success', 'Data periode berhasil dihapus!');
    }
}
