<?php

namespace App\Services;

use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Models\ValidasiBukti;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ValidasiBuktiService
{
    public function getFormData(): array
    {
        return [
            'sopirs' => Sopir::orderBy('nama')->get(['kode_sopir', 'nama']),
            'tujuans' => Tujuan::orderBy('nama')->get(['kode_tujuan', 'nama']),
        ];
    }

    public function submit(Request $request): void
    {
        $foto = $request->foto;
        $ext = str_starts_with($foto, 'data:image/png') ? 'png' : 'jpg';
        $foto = preg_replace('/^data:image\/\w+;base64,/', '', $foto);
        $foto = str_replace(' ', '+', $foto);
        $fileName = 'bukti/' . uniqid() . '.' . $ext;
        Storage::disk('public')->put($fileName, base64_decode($foto));

        $periode = Periode::where('tanggal_mulai', '<=', $request->tanggal)
            ->where('tanggal_selesai', '>=', $request->tanggal)->first();

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
    }

    public function getKelolaData(Request $request): array
    {
        $status = $request->get('status', 'pending');
        $search = trim($request->get('search', ''));

        $list = ValidasiBukti::with(['sopir', 'tujuan', 'periode'])
            ->when($status !== 'semua', fn($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('nama_sopir', 'like', "%{$search}%")
                        ->orWhere('nama_tujuan', 'like', "%{$search}%");
                    $date = $this->parseSearchDate($search);
                    if ($date) $q2->orWhereDate('tanggal', $date);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return compact('list', 'status', 'search');
    }

    public function getDetailData($id): array
    {
        $item = ValidasiBukti::with(['sopir', 'tujuan', 'periode'])->findOrFail($id);
        $sopirs = Sopir::orderBy('nama')->get(['kode_sopir', 'nama']);
        $tujuans = Tujuan::orderBy('nama')->get(['kode_tujuan', 'nama']);
        return compact('item', 'sopirs', 'tujuans');
    }

    public function setujui(Request $request, $id): void
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
    }

    public function tolak(Request $request, $id): void
    {
        $item = ValidasiBukti::findOrFail($id);
        $item->update([
            'status' => 'ditolak',
            'catatan_mitra' => $request->catatan_mitra,
        ]);
    }

    public function tambahRitase(Request $request, $id): string
    {
        $item = ValidasiBukti::findOrFail($id);

        $periode = $item->periode_id
            ? Periode::find($item->periode_id)
            : Periode::where('tanggal_mulai', '<=', $request->tanggal)
                ->where('tanggal_selesai', '>=', $request->tanggal)->first();

        if (!$periode) {
            throw new \Exception('Tidak ada periode yang mencakup tanggal ini. Buat periode terlebih dahulu.');
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

        return $kodeRitase;
    }

    private function hitungDt($kodeSopir, $tanggal, $kabupaten, $waktu): float
    {
        $exists = Ritase::where('kode_sopir', $kodeSopir)
            ->where('tanggal', $tanggal)
            ->where('kabupaten', $kabupaten)
            ->where('waktu', $waktu)
            ->where('status', '!=', 'gagal_produksi')
            ->exists();

        return $exists ? 0 : config('dt.value', 330000);
    }

    private function parseSearchDate(string $search): ?string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $search)) {
            return $search;
        }
        if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{2,4})$#', $search, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3] > 99 ? $m[3] : 2000 + $m[3], $m[2], $m[1]);
        }
        return null;
    }
}
