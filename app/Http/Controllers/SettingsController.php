<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\DtConfig;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $aturanEnabled = (bool) Setting::getValue('aturan_validasi_enabled', false);
        $dtNominal = DtConfig::nominal();
        $dtSingleRegencies = \App\Services\DtConfig::singleRegencies();
        $allKabupaten = \App\Services\DtConfig::allKabupaten();

        return view('settings.index', compact('aturanEnabled', 'dtNominal', 'dtSingleRegencies', 'allKabupaten'));
    }

    public function updateDtNominal(Request $request)
    {
        $request->validate([
            'nominal' => 'required|numeric|min:1',
        ]);

        $nominal = (int) $request->nominal;
        Setting::setValue('dt_nominal', $nominal);

        $sync = app(\App\Services\RitaseService::class)->syncActivePeriodeDt($nominal);

        if (!$sync['periode']) {
            return back()->with('success', 'Nominal sewa DT berhasil diperbarui. Tidak ada periode aktif maupun periode selesai, jadi tidak ada ritase yang diubah.');
        }

        $label = ($sync['fallback'] ?? false) ? 'periode nonaktif terakhir' : 'periode aktif';

        return back()->with('success', 'Nominal sewa DT berhasil diperbarui. ' . $sync['ritase'] . ' ritase dan ' . $sync['gaji'] . ' data gaji ' . $label . ' (' . $sync['periode']->nama_periode . ') ikut disesuaikan.');
    }

    public function updateDtAturan(Request $request)
    {
        $request->validate([
            'kabupaten' => 'nullable|array',
            'kabupaten.*' => 'string|max:50',
        ]);

        $selected = array_values(array_unique(array_map(
            fn($n) => trim((string) $n),
            (array) $request->input('kabupaten', [])
        )));
        $selected = array_values(array_filter($selected, fn($n) => $n !== ''));
        Setting::setValue('dt_single_regencies', $selected);

        $sync = app(\App\Services\RitaseService::class)->syncActivePeriodeDt(\App\Services\DtConfig::nominal());

        if (!$sync['periode']) {
            return back()->with('success', 'Ketentuan sewa DT berhasil diperbarui. Tidak ada periode aktif maupun periode selesai, jadi tidak ada ritase yang diubah.');
        }

        $label = ($sync['fallback'] ?? false) ? 'periode nonaktif terakhir' : 'periode aktif';

        return back()->with('success', 'Ketentuan sewa DT berhasil diperbarui. ' . $sync['ritase'] . ' ritase dan ' . $sync['gaji'] . ' data gaji ' . $label . ' (' . $sync['periode']->nama_periode . ') ikut disesuaikan.');
    }
}
