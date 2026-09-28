<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function index()
    {
        $aturanEnabled = Cache::get('aturan_validasi_enabled', false);
        $dtNominal = Cache::get('dt_nominal', config('dt.value', 330000));

        return view('settings.index', compact('aturanEnabled', 'dtNominal'));
    }

    public function updateDtNominal(Request $request)
    {
        $request->validate([
            'nominal' => 'required|numeric|min:1',
        ]);

        $nominal = (int) $request->nominal;
        Cache::forever('dt_nominal', $nominal);

        $sync = app(\App\Services\RitaseService::class)->syncActivePeriodeDt($nominal);

        if (!$sync['periode']) {
            return back()->with('success', 'Nominal sewa DT berhasil diperbarui. Tidak ada periode aktif, jadi tidak ada ritase yang diubah.');
        }

        return back()->with('success', 'Nominal sewa DT berhasil diperbarui. ' . $sync['ritase'] . ' ritase dan ' . $sync['gaji'] . ' data gaji periode aktif (' . $sync['periode']->nama_periode . ') ikut disesuaikan.');
    }
}
