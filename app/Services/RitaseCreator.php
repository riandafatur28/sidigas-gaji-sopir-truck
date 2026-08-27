<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;

/**
 * Create ritase records from parsed data with auto-matching and auto-create.
 */
class RitaseCreator
{
    private RitaseFuzzyMatcher $matcher;

    public function __construct()
    {
        $this->matcher = new RitaseFuzzyMatcher();
    }

    public function create(array $parsed, int $periodeId, array $driverMatches = [], array $routeMatches = []): array
    {
        $created = $skipped = 0;
        $errors = $details = [];

        if (empty($parsed['date'])) {
            $errors[] = 'No date found in parsed data';
            return compact('created', 'skipped', 'errors', 'details');
        }

        $periode = Periode::find($periodeId);
        if (!$periode) {
            $errors[] = "Periode not found with ID: $periodeId";
            return compact('created', 'skipped', 'errors', 'details');
        }

        $driverMap = collect($driverMatches)->keyBy('input_name');
        $routeMap = collect($routeMatches)->keyBy('input_route');

        [$driverMap, $routeMap] = $this->autoCreateUnmatched($parsed, $driverMap, $routeMap);

        foreach ($parsed['packages'] as $package) {
            $result = $this->processPackage($package, $parsed, $periodeId, $driverMap, $routeMap);
            $created += $result['created'];
            $skipped += $result['skipped'];
            $errors = array_merge($errors, $result['errors']);
            $details = array_merge($details, $result['details']);
        }

        return compact('created', 'skipped', 'errors', 'details');
    }

    private function autoCreateUnmatched(array $parsed, $driverMap, $routeMap): array
    {
        $createdDrivers = [];
        $createdRoutes = [];

        foreach ($parsed['packages'] as $package) {
            foreach (($package['drivers'] ?? []) as $driverName) {
                if (isset($createdDrivers[$driverName])) continue;
                $dm = $driverMap[$driverName] ?? null;
                if ($dm && $dm['matched']) continue;

                $last = Sopir::orderBy('id', 'desc')->first();
                $num = $last ? (int)substr($last->kode_sopir, 4) + 1 : 1;
                $sopir = Sopir::create([
                    'kode_sopir' => 'SPR-' . str_pad((string) $num, 3, '0', STR_PAD_LEFT),
                    'nama' => $driverName, 'status' => 'aktif',
                ]);
                $createdDrivers[$driverName] = true;
                $driverMap[$driverName] = ['input_name' => $driverName, 'matched' => true, 'sopir' => $sopir, 'confidence' => 100];
            }

            $routeName = $package['route_name'];
            if (!str_contains(strtolower($routeName), 'gagal') && !isset($createdRoutes[$routeName])) {
                $rm = $routeMap[$routeName] ?? null;
                if (!$rm || !$rm['matched']) {
                    $last = Tujuan::orderBy('id', 'desc')->first();
                    $num = $last ? (int)substr($last->kode_tujuan, 4) + 1 : 1;
                    $tujuan = Tujuan::create([
                        'kode_tujuan' => 'TUJ-' . str_pad((string) $num, 3, '0', STR_PAD_LEFT),
                        'nama' => $routeName, 'status' => 'aktif',
                    ]);
                    $createdRoutes[$routeName] = true;
                    $tujuan->setAttribute('kabupaten', $this->guessKabupaten($routeName));
                    $routeMap[$routeName] = ['input_route' => $routeName, 'matched' => true, 'tujuan' => $tujuan, 'confidence' => 100];
                }
            }
        }

        return [$driverMap, $routeMap];
    }

    private function processPackage(array $package, array $parsed, int $periodeId, $driverMap, $routeMap): array
    {
        $created = $skipped = 0;
        $errors = $details = [];
        $routeName = $package['route_name'];
        $routeMatch = $routeMap[$routeName] ?? null;
        $isGagal = str_contains(strtolower($routeName), 'gagal');
        $kodeTujuan = ($routeMatch && $routeMatch['matched'] && !$isGagal) ? $routeMatch['tujuan']->kode_tujuan : null;

        if ($isGagal) {
            $this->cleanupGagalTujuan($routeName);
        }

        $matchedSopirs = $this->resolveDrivers($package['drivers'] ?? [], $driverMap);
        if (empty($matchedSopirs)) {
            $details[] = ['route' => $routeName, 'status' => 'Skipped', 'reason' => 'No valid drivers matched'];
            return compact('created', 'skipped', 'errors', 'details');
        }

        $tujuan = $routeMatch ? $routeMatch['tujuan'] : null;
        $kabupaten = $tujuan->kabupaten ?? $this->guessKabupaten($routeName);
        $waktu = $this->guessWaktu($routeName);

        if ($isGagal) {
            return $this->handleGagal($package, $parsed, $periodeId, $routeName, $matchedSopirs, $kabupaten, $waktu);
        }

        if (!empty($package['is_bongkar'])) {
            $this->adjustBongkarWaktu($package, $parsed, $routeMap, $driverMap);
            $waktu = $this->guessWaktu($routeName);
        }

        foreach ($matchedSopirs as ['sopir' => $sopir, 'confidence' => $confidence]) {
            $isRitKe2 = !empty($package['is_rit_ke_2']);
            if (!$isRitKe2) {
                $duplicate = Ritase::where('periode_id', $periodeId)
                    ->where('kode_sopir', $sopir->kode_sopir)
                    ->where('tanggal', $parsed['date'])
                    ->where('waktu', $waktu)
                    ->where('kode_tujuan', $kodeTujuan)
                    ->exists();
                if ($duplicate) { $skipped++; $details[] = ['route' => $routeName, 'status' => 'Skipped', 'sopir' => $sopir->nama, 'reason' => 'Duplicate']; continue; }
            }

            $dtValue = $this->calculateDt($sopir, $parsed['date'], $kabupaten, $waktu);

            try {
                $ritase = new Ritase();
                $ritase->periode_id = $periodeId;
                $ritase->kode_sopir = $sopir->kode_sopir;
                $ritase->kode_tujuan = $kodeTujuan;
                $ritase->tanggal = $parsed['date'];
                $ritase->waktu = $waktu;
                $ritase->kabupaten = $kabupaten;
                $ritase->dt = $dtValue;
                $ritase->status = 'valid';
                $ritase->catatan = $isRitKe2 ? "Rit ke-2 (parser)" : "Auto-create from parser (mode: " . ($parsed['source'] ?? 'rule-based') . ")";
                $ritase->save();
                $created++;
                $details[] = ['route' => $routeName, 'status' => 'Created', 'sopir' => $sopir->nama, 'kode_sopir' => $sopir->kode_sopir, 'kode_tujuan' => $kodeTujuan, 'waktu' => $waktu, 'kabupaten' => $kabupaten];
            } catch (\Exception $e) {
                $errors[] = "Failed to create for '{$routeName}' / {$sopir->nama}: " . $e->getMessage();
                $skipped++;
            }
        }

        return compact('created', 'skipped', 'errors', 'details');
    }

    private function handleGagal(array $package, array $parsed, int $periodeId, string $routeName, array $matchedSopirs, string $kabupaten, string $waktu): array
    {
        $created = $skipped = 0;
        $errors = $details = [];

        [$gagalTarget, $gagalWaktu] = $this->resolveGagalTarget($package, $parsed, $routeName);

        $isPureGagal = preg_match('/^gagal(\s*produksi)?$/i', trim($routeName));

        if ($isPureGagal) {
            foreach ($matchedSopirs as ['sopir' => $sopir]) {
                $affected = Ritase::where('periode_id', $periodeId)->where('kode_sopir', $sopir->kode_sopir)->where('tanggal', $parsed['date'])->where('waktu', $waktu)->where('status', 'valid')->latest('id')->limit(1)->update(['dt' => 0, 'status' => 'gagal_produksi']);
                if (!$affected) {
                    $affected = Ritase::where('periode_id', $periodeId)->where('kode_sopir', $sopir->kode_sopir)->where('tanggal', $parsed['date'])->where('status', 'valid')->latest('id')->limit(1)->update(['dt' => 0, 'status' => 'gagal_produksi']);
                }
                if ($affected) {
                    $update = [];
                    if ($gagalTarget) $update['kode_tujuan'] = $gagalTarget;
                    if ($gagalWaktu) $update['waktu'] = $gagalWaktu;
                    if ($update) {
                        Ritase::where('periode_id', $periodeId)->where('kode_sopir', $sopir->kode_sopir)->where('tanggal', $parsed['date'])->where('status', 'gagal_produksi')->latest('id')->limit(1)->update($update);
                    }
                    $details[] = ['route' => $routeName, 'status' => 'Updated to gagal', 'sopir' => $sopir->nama, 'reason' => 'DT=0, status=gagal_produksi'];
                }
            }
        } else {
            foreach ($matchedSopirs as ['sopir' => $sopir]) {
                $gagalWaktuVal = $gagalWaktu ?: $waktu;
                $duplicate = Ritase::where('periode_id', $periodeId)->where('kode_sopir', $sopir->kode_sopir)->where('tanggal', $parsed['date'])->where('waktu', $gagalWaktuVal)->where('kode_tujuan', $gagalTarget ?? $package['route_name'])->exists();
                if ($duplicate) { $skipped++; $details[] = ['route' => $routeName, 'status' => 'Skipped', 'sopir' => $sopir->nama, 'reason' => 'Duplicate']; continue; }

                try {
                    $ritase = new Ritase();
                    $ritase->periode_id = $periodeId;
                    $ritase->kode_sopir = $sopir->kode_sopir;
                    $ritase->kode_tujuan = $gagalTarget;
                    $ritase->tanggal = $parsed['date'];
                    $ritase->waktu = $gagalWaktuVal;
                    $ritase->dt = 0;
                    $ritase->status = 'gagal_produksi';
                    $ritase->kabupaten = $kabupaten;
                    $ritase->save();
                    $created++;
                    $details[] = ['route' => $routeName, 'status' => 'Created gagal', 'sopir' => $sopir->nama, 'reason' => 'DT=0, status=gagal_produksi'];
                } catch (\Exception $e) {
                    $errors[] = "Failed to create gagal for {$sopir->nama}: {$e->getMessage()}";
                }
            }
        }

        return compact('created', 'skipped', 'errors', 'details');
    }

    private function resolveGagalTarget(array $package, array $parsed, string $routeName): array
    {
        $gagalTarget = $gagalWaktu = null;

        if (!empty($package['gagal_route'])) {
            $matches = $this->matcher->matchRoutes([$package['gagal_route']]);
            if (!empty($matches) && $matches[0]['matched']) {
                $gagalTarget = $matches[0]['tujuan']->kode_tujuan;
                $gagalWaktu = $this->guessWaktu($package['gagal_route']);
            }
        }

        if (empty($gagalTarget)) {
            $clean = preg_replace('/\s+gagal(\s+produksi)?$/i', '', $routeName);
            if ($clean !== $routeName && !empty($clean)) {
                $gm = $this->matcher->matchRoutes([$clean]);
                if (!empty($gm) && $gm[0]['matched']) {
                    $gagalTarget = $gm[0]['tujuan']->kode_tujuan;
                    $gagalWaktu = $this->guessWaktu($clean);
                }
            }
        }

        if (empty($gagalTarget) && !empty($parsed['header_kode_tujuan'])) {
            $gagalTarget = $parsed['header_kode_tujuan'];
            $gagalWaktu = $parsed['header_waktu'] ?? null;
        }

        return [$gagalTarget, $gagalWaktu];
    }

    private function resolveDrivers(array $driverNames, $driverMap): array
    {
        $result = [];
        foreach ($driverNames as $name) {
            $dm = $driverMap[$name] ?? null;
            if ($dm && $dm['matched']) {
                $result[] = ['sopir' => $dm['sopir'], 'confidence' => $dm['confidence']];
            }
        }
        return $result;
    }

    private function cleanupGagalTujuan(string $routeName): void
    {
        $tujuan = Tujuan::where('nama', $routeName)->first();
        if ($tujuan) {
            Ritase::where('kode_tujuan', $tujuan->kode_tujuan)->update(['kode_tujuan' => null]);
            $tujuan->delete();
        }
    }

    private function adjustBongkarWaktu(array $package, array $parsed, $routeMap, $driverMap): void
    {
        $sourceIdx = $package['bongkar_source_idx'] ?? null;
        $sourcePkg = $sourceIdx !== null ? ($parsed['packages'][$sourceIdx] ?? null) : null;
        if ($sourcePkg) {
            $sourceRouteMatch = $routeMap[$sourcePkg['route_name']] ?? null;
            if ($sourceRouteMatch && $sourceRouteMatch['matched']) {
                $this->guessWaktu($package['route_name']);
            }
        }
    }

    public function guessKabupaten(string $routeName): string
    {
        $lower = strtolower($routeName);
        $kecamatanMap = [
            'bagor' => 'Nganjuk', 'bandarkedungmulyo' => 'Nganjuk', 'baron' => 'Nganjuk', 'berbek' => 'Nganjuk', 'gondang' => 'Nganjuk', 'jatikalen' => 'Nganjuk', 'kertosono' => 'Nganjuk', 'lengkong' => 'Nganjuk', 'loceret' => 'Nganjuk', 'ngetos' => 'Nganjuk', 'ngluyu' => 'Nganjuk', 'ngronggot' => 'Nganjuk', 'pace' => 'Nganjuk', 'patianrowo' => 'Nganjuk', 'prambon' => 'Nganjuk', 'rejoso' => 'Nganjuk', 'sukomoro' => 'Nganjuk', 'tanjunganom' => 'Nganjuk', 'wilangan' => 'Nganjuk',
            'bareng' => 'Jombang', 'diwek' => 'Jombang', 'gudo' => 'Jombang', 'jogoroto' => 'Jombang', 'kabuh' => 'Jombang', 'kesamben' => 'Jombang', 'kudu' => 'Jombang', 'megaluh' => 'Jombang', 'mojoagung' => 'Jombang', 'mojowarno' => 'Jombang', 'ngoro' => 'Jombang', 'ngusikan' => 'Jombang', 'perak' => 'Jombang', 'peterongan' => 'Jombang', 'plandaan' => 'Jombang', 'ploso' => 'Jombang', 'sumobito' => 'Jombang', 'tembelang' => 'Jombang', 'wonosalam' => 'Jombang',
            'badas' => 'Kediri', 'banyakan' => 'Kediri', 'gampengrejo' => 'Kediri', 'grogol' => 'Kediri', 'gurah' => 'Kediri', 'kandangan' => 'Kediri', 'kandat' => 'Kediri', 'kayen kidul' => 'Kediri', 'kepung' => 'Kediri', 'kras' => 'Kediri', 'kunjang' => 'Kediri', 'mojo' => 'Kediri', 'ngadiluwih' => 'Kediri', 'ngancar' => 'Kediri', 'ngasem' => 'Kediri', 'pagu' => 'Kediri', 'papar' => 'Kediri', 'pare' => 'Kediri', 'plemahan' => 'Kediri', 'plosoklaten' => 'Kediri', 'puncu' => 'Kediri', 'purwoasri' => 'Kediri', 'ringinrejo' => 'Kediri', 'semen' => 'Kediri', 'tarokan' => 'Kediri', 'wates' => 'Kediri',
            'mojoroto' => 'Kota Kediri', 'pesantren' => 'Kota Kediri',
        ];
        foreach ($kecamatanMap as $kec => $kab) {
            if (preg_match('/\b' . preg_quote($kec, '/') . '\b/', $lower)) return $kab;
        }
        foreach (['nganjuk' => 'Nganjuk', 'kediri' => 'Kediri', 'jombang' => 'Jombang', 'blitar' => 'Blitar', 'ngawi' => 'Ngawi'] as $kw => $kab) {
            if (str_contains($lower, $kw)) return $kab;
        }
        return 'Lainnya';
    }

    public function guessWaktu(string $routeName): string
    {
        return str_contains(strtolower($routeName), 'malam') ? 'malam' : 'pagi';
    }

    private function calculateDt(Sopir $sopir, $date, string $kabupaten, string $waktu): int
    {
        $dtValue = config('dt.value', 330000);
        $kabNorm = strtolower(trim($kabupaten));
        $specialKabs = array_map('strtolower', config('dt.single_dt_regencies'));
        if (in_array($kabNorm, $specialKabs)) {
            $existing = Ritase::where('kode_sopir', $sopir->kode_sopir)->where('tanggal', $date)->where('kabupaten', $kabupaten)->where('waktu', $waktu)->where('status', '!=', 'gagal_produksi')->first();
            if ($existing) $dtValue = 0;
        }
        return $dtValue;
    }
}
