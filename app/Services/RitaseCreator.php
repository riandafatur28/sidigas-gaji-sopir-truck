<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Services\Ml\TujuanMlService;
use App\Data\DesaData;
use Illuminate\Support\Facades\Cache;

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
                } else {
                    // FALLBACK: no existing valid ritase to convert -> create new gagal record
                    // This fixes bug where "Gagal produksi" with distinct drivers (10 orang) tidak tersimpan sama sekali
                    $fallbackTarget = $gagalTarget;
                    $fallbackWaktu = $gagalWaktu ?: $waktu;
                    $fallbackKabupaten = $kabupaten;
                    if (empty($fallbackTarget)) {
                        $fallbackTarget = $this->findFallbackTujuanForGagal($parsed, $routeName, $periodeId);
                    }
                    if (empty($fallbackTarget)) {
                        $fallbackTarget = Ritase::where('periode_id', $periodeId)->where('status', 'valid')->orderByDesc('id')->value('kode_tujuan');
                    }
                    if (empty($fallbackTarget)) {
                        $errors[] = "Gagal produksi '{$routeName}' / {$sopir->nama}: tidak ada tujuan fallback (kode_tujuan kosong) - buat Tujuan untuk paket sebelumnya dulu";
                        $skipped++;
                        continue;
                    }
                    // Re-guess kabupaten from fallback tujuan if current is generic Lainnya
                    if ($fallbackKabupaten === 'Lainnya' || $fallbackKabupaten === '') {
                        $tujuanModel = Tujuan::where('kode_tujuan', $fallbackTarget)->first();
                        if ($tujuanModel) {
                            $fallbackKabupaten = $this->guessKabupaten($tujuanModel->nama);
                        }
                    }
                    $duplicate = Ritase::where('periode_id', $periodeId)->where('kode_sopir', $sopir->kode_sopir)->where('tanggal', $parsed['date'])->where('waktu', $fallbackWaktu)->where('kode_tujuan', $fallbackTarget)->exists();
                    if ($duplicate) { $skipped++; $details[] = ['route' => $routeName, 'status' => 'Skipped', 'sopir' => $sopir->nama, 'reason' => 'Duplicate']; continue; }
                    try {
                        $ritase = new Ritase();
                        $ritase->periode_id = $periodeId;
                        $ritase->kode_sopir = $sopir->kode_sopir;
                        $ritase->kode_tujuan = $fallbackTarget;
                        $ritase->tanggal = $parsed['date'];
                        $ritase->waktu = $fallbackWaktu;
                        $ritase->dt = 0;
                        $ritase->status = 'gagal_produksi';
                        $ritase->kabupaten = $fallbackKabupaten;
                        $ritase->catatan = "Gagal produksi (auto fallback dari pure gagal)";
                        $ritase->save();
                        $created++;
                        $details[] = ['route' => $routeName, 'status' => 'Created gagal (fallback)', 'sopir' => $sopir->nama, 'kode_tujuan' => $fallbackTarget, 'reason' => 'DT=0, status=gagal_produksi'];
                    } catch (\Exception $e) {
                        $errors[] = "Failed to create gagal for {$sopir->nama}: {$e->getMessage()}";
                    }
                }
            }
        } else {
            foreach ($matchedSopirs as ['sopir' => $sopir]) {
                $effectiveTarget = $gagalTarget;
                $effectiveKabupaten = $kabupaten;
                if (empty($effectiveTarget)) {
                    $effectiveTarget = $this->findFallbackTujuanForGagal($parsed, $routeName, $periodeId);
                }
                if (empty($effectiveTarget)) {
                    $effectiveTarget = Ritase::where('periode_id', $periodeId)->where('status', 'valid')->orderByDesc('id')->value('kode_tujuan');
                }
                if (empty($effectiveTarget)) {
                    $errors[] = "Gagal '{$routeName}' / {$sopir->nama}: tidak ada tujuan fallback";
                    $skipped++;
                    continue;
                }
                if ($effectiveKabupaten === 'Lainnya' || $effectiveKabupaten === '') {
                    $tujuanModel = Tujuan::where('kode_tujuan', $effectiveTarget)->first();
                    if ($tujuanModel) {
                        $effectiveKabupaten = $this->guessKabupaten($tujuanModel->nama);
                    }
                }
                $gagalWaktuVal = $gagalWaktu ?: $waktu;
                $duplicate = Ritase::where('periode_id', $periodeId)->where('kode_sopir', $sopir->kode_sopir)->where('tanggal', $parsed['date'])->where('waktu', $gagalWaktuVal)->where('kode_tujuan', $effectiveTarget)->exists();
                if ($duplicate) { $skipped++; $details[] = ['route' => $routeName, 'status' => 'Skipped', 'sopir' => $sopir->nama, 'reason' => 'Duplicate']; continue; }

                try {
                    $ritase = new Ritase();
                    $ritase->periode_id = $periodeId;
                    $ritase->kode_sopir = $sopir->kode_sopir;
                    $ritase->kode_tujuan = $effectiveTarget;
                    $ritase->tanggal = $parsed['date'];
                    $ritase->waktu = $gagalWaktuVal;
                    $ritase->dt = 0;
                    $ritase->status = 'gagal_produksi';
                    $ritase->kabupaten = $effectiveKabupaten;
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

    private function findFallbackTujuanForGagal(array $parsed, string $currentRouteName, int $periodeId): ?string
    {
        // Cari paket non-gagal terakhir sebelum paket gagal saat ini
        $foundCurrent = false;
        $prevRoute = null;
        foreach (array_reverse($parsed['packages']) as $pkg) {
            if (!$foundCurrent) {
                if ($pkg['route_name'] === $currentRouteName) {
                    $foundCurrent = true;
                }
                continue;
            }
            if (!str_contains(strtolower($pkg['route_name']), 'gagal')) {
                $prevRoute = $pkg['route_name'];
                break;
            }
        }
        if ($prevRoute) {
            $matches = $this->matcher->matchRoutes([$prevRoute]);
            if (!empty($matches) && $matches[0]['matched']) {
                return $matches[0]['tujuan']->kode_tujuan;
            }
            // If not matched but route was auto-created earlier in autoCreateUnmatched, try DB lookup by name
            $tujuan = Tujuan::where('nama', $prevRoute)->first();
            if ($tujuan) return $tujuan->kode_tujuan;
        }
        return null;
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

    /**
     * Kata kunci kabupaten eksplisit (kata => label baku).
     * Dipakai guessKabupaten() dan data latih ML.
     */
    public static function kabupatenKeywords(): array
    {
        return [
            'nganjuk' => 'Nganjuk', 'kediri' => 'Kediri', 'jombang' => 'Jombang',
            'blitar' => 'Blitar', 'ngawi' => 'Ngawi', 'madiun' => 'Madiun',
            'tulungagung' => 'Tulungagung', 'malang' => 'Malang', 'mojokerto' => 'Mojokerto',
            'surabaya' => 'Surabaya', 'gresik' => 'Gresik', 'lamongan' => 'Lamongan',
            'tuban' => 'Tuban', 'bojonegoro' => 'Bojonegoro', 'pasuruan' => 'Pasuruan',
            'probolinggo' => 'Probolinggo', 'lumajang' => 'Lumajang', 'jember' => 'Jember',
            'banyuwangi' => 'Banyuwangi', 'magetan' => 'Magetan', 'ponorogo' => 'Ponorogo',
            'pacitan' => 'Pacitan', 'trenggalek' => 'Trenggalek', 'situbondo' => 'Situbondo',
            'bondowoso' => 'Bondowoso', 'batu' => 'Batu',
        ];
    }

    /**
     * Peta kecamatan => kabupaten.
     * Dipakai guessKabupaten() dan data latih ML.
     */
    public static function kecamatanMap(): array
    {
        return [
            'nganjuk' => 'Nganjuk', 'bagor' => 'Nganjuk', 'baron' => 'Nganjuk', 'berbek' => 'Nganjuk',
            'jatikalen' => 'Nganjuk', 'kertosono' => 'Nganjuk', 'lengkong' => 'Nganjuk',
            'loceret' => 'Nganjuk', 'ngetos' => 'Nganjuk', 'ngluyu' => 'Nganjuk',
            'ngronggot' => 'Nganjuk', 'pace' => 'Nganjuk', 'patianrowo' => 'Nganjuk',
            'sawahan' => 'Nganjuk', 'sukomoro' => 'Nganjuk', 'tanjunganom' => 'Nganjuk',
            'wilangan' => 'Nganjuk',
            'kediri' => 'Kediri', 'badas' => 'Kediri', 'banyakan' => 'Kediri',
            'gampengrejo' => 'Kediri', 'grogol' => 'Kediri', 'gurah' => 'Kediri',
            'kandangan' => 'Kediri', 'kandat' => 'Kediri', 'kayenkidul' => 'Kediri',
            'kepung' => 'Kediri', 'kras' => 'Kediri', 'kunjang' => 'Kediri',
            'mojo' => 'Kediri', 'ngadiluwih' => 'Kediri', 'ngancar' => 'Kediri',
            'ngasem' => 'Kediri', 'pagu' => 'Kediri', 'papar' => 'Kediri',
            'pare' => 'Kediri', 'plemahan' => 'Kediri', 'plosoklaten' => 'Kediri',
            'puncu' => 'Kediri', 'purwoasri' => 'Kediri', 'ringinrejo' => 'Kediri',
            'semen' => 'Kediri', 'tarokan' => 'Kediri',
            'jombang' => 'Jombang', 'bareng' => 'Jombang', 'diwek' => 'Jombang',
            'gudo' => 'Jombang', 'jogoroto' => 'Jombang', 'kudu' => 'Jombang',
            'megaluh' => 'Jombang', 'mojoagung' => 'Jombang', 'mojowarno' => 'Jombang',
            'ngusikan' => 'Jombang', 'perak' => 'Jombang', 'peterongan' => 'Jombang',
            'plandaan' => 'Jombang', 'ploso' => 'Jombang', 'sumobito' => 'Jombang',
            'tembelang' => 'Jombang', 'wonosalam' => 'Jombang',
            'blitar' => 'Blitar', 'bakung' => 'Blitar', 'binangun' => 'Blitar',
            'doko' => 'Blitar', 'garum' => 'Blitar', 'kademangan' => 'Blitar',
            'kanigoro' => 'Blitar', 'kepanjenkidul' => 'Blitar', 'kesamben' => 'Blitar',
            'nglegok' => 'Blitar', 'panggungrejo' => 'Blitar', 'ponggok' => 'Blitar',
            'sanankulon' => 'Blitar', 'sananwetan' => 'Blitar', 'selopuro' => 'Blitar',
            'selorejo' => 'Blitar', 'srengat' => 'Blitar', 'sutojayan' => 'Blitar',
            'talun' => 'Blitar', 'udanawu' => 'Blitar', 'wates' => 'Blitar',
            'wlingi' => 'Blitar', 'wonodadi' => 'Blitar', 'wonotirto' => 'Blitar',
            'ngawi' => 'Ngawi', 'bringin' => 'Ngawi', 'gerih' => 'Ngawi',
            'jogorogo' => 'Ngawi', 'karanganyar' => 'Ngawi', 'karangjati' => 'Ngawi',
            'kasreman' => 'Ngawi', 'kedunggalar' => 'Ngawi', 'kendal' => 'Ngawi',
            'kwadungan' => 'Ngawi', 'mantingan' => 'Ngawi', 'ngrambe' => 'Ngawi',
            'padas' => 'Ngawi', 'pangkur' => 'Ngawi', 'paron' => 'Ngawi',
            'pitu' => 'Ngawi', 'sine' => 'Ngawi', 'widodaren' => 'Ngawi',
            'madiun' => 'Madiun', 'balerejo' => 'Madiun', 'dagangan' => 'Madiun',
            'dolopo' => 'Madiun', 'geger' => 'Madiun', 'gemarang' => 'Madiun',
            'jiwan' => 'Madiun', 'kare' => 'Madiun', 'kebonsari' => 'Madiun',
            'manguharjo' => 'Madiun', 'mejayan' => 'Madiun', 'pilangkenceng' => 'Madiun',
            'saradan' => 'Madiun', 'wonoasri' => 'Madiun', 'wungu' => 'Madiun',
            'tulungagung' => 'Tulungagung', 'bandung' => 'Tulungagung',
            'besuki' => 'Tulungagung', 'boyolangu' => 'Tulungagung',
            'campurdarat' => 'Tulungagung', 'gondang' => 'Tulungagung',
            'kalidawir' => 'Tulungagung', 'kedungwaru' => 'Tulungagung',
            'ngantru' => 'Tulungagung', 'ngunut' => 'Tulungagung',
            'pagerwojo' => 'Tulungagung', 'pakel' => 'Tulungagung',
            'pucanglaban' => 'Tulungagung', 'sendang' => 'Tulungagung',
            'sumbergempol' => 'Tulungagung', 'tanggunggunung' => 'Tulungagung',
            'malang' => 'Malang', 'bantur' => 'Malang', 'blimbing' => 'Malang',
            'bululawang' => 'Malang', 'dampit' => 'Malang', 'dau' => 'Malang',
            'donomulyo' => 'Malang', 'gedangan' => 'Malang', 'gondanglegi' => 'Malang',
            'jabung' => 'Malang', 'kalipare' => 'Malang', 'karangploso' => 'Malang',
            'kasembon' => 'Malang', 'kedungkandang' => 'Malang', 'kepanjen' => 'Malang',
            'klojen' => 'Malang', 'kromengan' => 'Malang', 'lawang' => 'Malang',
            'lowokwaru' => 'Malang', 'ngajum' => 'Malang', 'ngantang' => 'Malang',
            'pagak' => 'Malang', 'pagelaran' => 'Malang', 'pakis' => 'Malang',
            'pakisaji' => 'Malang', 'poncokusumo' => 'Malang', 'pujon' => 'Malang',
            'singosari' => 'Malang', 'sumbermanjing' => 'Malang',
            'sumberpucung' => 'Malang', 'tajinan' => 'Malang', 'tirtoyudo' => 'Malang',
            'tumpang' => 'Malang', 'turen' => 'Malang', 'wagir' => 'Malang',
            'wajak' => 'Malang', 'wonosari' => 'Malang',
            'mojokerto' => 'Mojokerto', 'bangsal' => 'Mojokerto',
            'dawarblandong' => 'Mojokerto', 'dlanggu' => 'Mojokerto',
            'gedek' => 'Mojokerto', 'jatirejo' => 'Mojokerto', 'kemlagi' => 'Mojokerto',
            'kutorejo' => 'Mojokerto', 'magersari' => 'Mojokerto', 'mojoanyar' => 'Mojokerto',
            'mojosari' => 'Mojokerto', 'ngoro' => 'Mojokerto', 'pacet' => 'Mojokerto',
            'prajuritkulon' => 'Mojokerto', 'pungging' => 'Mojokerto', 'puri' => 'Mojokerto',
            'trawas' => 'Mojokerto', 'trowulan' => 'Mojokerto',
            'surabaya' => 'Surabaya', 'asemrowo' => 'Surabaya', 'benowo' => 'Surabaya',
            'bubutan' => 'Surabaya', 'bulak' => 'Surabaya', 'dukuhpakis' => 'Surabaya',
            'gayungan' => 'Surabaya', 'gubeng' => 'Surabaya', 'gununganyar' => 'Surabaya',
            'jambangan' => 'Surabaya', 'karangpilang' => 'Surabaya', 'kenjeran' => 'Surabaya',
            'krembangan' => 'Surabaya', 'lakarsantri' => 'Surabaya', 'mulyorejo' => 'Surabaya',
            'pakal' => 'Surabaya', 'rungkut' => 'Surabaya', 'sambikerep' => 'Surabaya',
            'semampir' => 'Surabaya', 'simokerto' => 'Surabaya', 'sukolilo' => 'Surabaya',
            'sukomanunggal' => 'Surabaya', 'tambaksari' => 'Surabaya', 'tandes' => 'Surabaya',
            'wiyung' => 'Surabaya',
            'gresik' => 'Gresik', 'balongpanggang' => 'Gresik', 'benjeng' => 'Gresik',
            'bungah' => 'Gresik', 'cerme' => 'Gresik', 'driyorejo' => 'Gresik',
            'duduksampeyan' => 'Gresik', 'dukun' => 'Gresik', 'kedamean' => 'Gresik',
            'manyar' => 'Gresik', 'menganti' => 'Gresik', 'panceng' => 'Gresik',
            'sidayu' => 'Gresik', 'tambak' => 'Gresik', 'ujungpangkah' => 'Gresik',
            'wringinanom' => 'Gresik',
            'lamongan' => 'Lamongan', 'babat' => 'Lamongan', 'bluluk' => 'Lamongan',
            'brondong' => 'Lamongan', 'deket' => 'Lamongan', 'kalitengah' => 'Lamongan',
            'karangbinangun' => 'Lamongan', 'karanggeneng' => 'Lamongan',
            'kedungpring' => 'Lamongan', 'kembangbahu' => 'Lamongan', 'laren' => 'Lamongan',
            'maduran' => 'Lamongan', 'mantup' => 'Lamongan', 'modo' => 'Lamongan',
            'ngimbang' => 'Lamongan', 'paciran' => 'Lamongan', 'pucuk' => 'Lamongan',
            'sambeng' => 'Lamongan', 'sarirejo' => 'Lamongan', 'sekaran' => 'Lamongan',
            'solokuro' => 'Lamongan', 'sugio' => 'Lamongan', 'sukodadi' => 'Lamongan',
            'sukorame' => 'Lamongan', 'tikung' => 'Lamongan', 'turi' => 'Lamongan',
            'tuban' => 'Tuban', 'bancar' => 'Tuban', 'bangilan' => 'Tuban',
            'grabagan' => 'Tuban', 'jatirogo' => 'Tuban', 'jenu' => 'Tuban',
            'kenduruan' => 'Tuban', 'kerek' => 'Tuban', 'merakurak' => 'Tuban',
            'montong' => 'Tuban', 'palang' => 'Tuban', 'parengan' => 'Tuban',
            'plumpang' => 'Tuban', 'rengel' => 'Tuban', 'semanding' => 'Tuban',
            'senori' => 'Tuban', 'singgahan' => 'Tuban', 'soko' => 'Tuban',
            'tambakboyo' => 'Tuban', 'widang' => 'Tuban',
            'bojonegoro' => 'Bojonegoro', 'balen' => 'Bojonegoro', 'baureno' => 'Bojonegoro',
            'dander' => 'Bojonegoro', 'gayam' => 'Bojonegoro', 'kalitidu' => 'Bojonegoro',
            'kanor' => 'Bojonegoro', 'kapas' => 'Bojonegoro', 'kasiman' => 'Bojonegoro',
            'kedewan' => 'Bojonegoro', 'kedungadem' => 'Bojonegoro',
            'kepohbaru' => 'Bojonegoro', 'malo' => 'Bojonegoro', 'margomulyo' => 'Bojonegoro',
            'ngambon' => 'Bojonegoro', 'ngraho' => 'Bojonegoro', 'padangan' => 'Bojonegoro',
            'sekar' => 'Bojonegoro', 'sugihwaras' => 'Bojonegoro', 'sukosewu' => 'Bojonegoro',
            'sumberejo' => 'Bojonegoro', 'tambakrejo' => 'Bojonegoro', 'temayang' => 'Bojonegoro',
            'pasuruan' => 'Pasuruan', 'bangil' => 'Pasuruan', 'beji' => 'Pasuruan',
            'gadingrejo' => 'Pasuruan', 'gempol' => 'Pasuruan',
            'gondangwetan' => 'Pasuruan', 'grati' => 'Pasuruan', 'kejayan' => 'Pasuruan',
            'kraton' => 'Pasuruan', 'nguling' => 'Pasuruan', 'pandaan' => 'Pasuruan',
            'pasrepan' => 'Pasuruan', 'prigen' => 'Pasuruan', 'purwodadi' => 'Pasuruan',
            'purwosari' => 'Pasuruan', 'rejoso' => 'Pasuruan', 'rembang' => 'Pasuruan',
            'tosari' => 'Pasuruan', 'tutur' => 'Pasuruan', 'winongan' => 'Pasuruan',
            'wonorejo' => 'Pasuruan',
            'probolinggo' => 'Probolinggo', 'bantaran' => 'Probolinggo',
            'banyuanyar' => 'Probolinggo', 'besuk' => 'Probolinggo', 'dringu' => 'Probolinggo',
            'gading' => 'Probolinggo', 'gending' => 'Probolinggo', 'kanigaran' => 'Probolinggo',
            'kedopok' => 'Probolinggo', 'kotaanyar' => 'Probolinggo', 'kraksaan' => 'Probolinggo',
            'krejengan' => 'Probolinggo', 'krucil' => 'Probolinggo', 'kuripan' => 'Probolinggo',
            'leces' => 'Probolinggo', 'lumbang' => 'Probolinggo', 'maron' => 'Probolinggo',
            'paiton' => 'Probolinggo', 'pajarakan' => 'Probolinggo', 'pakuniran' => 'Probolinggo',
            'sukapura' => 'Probolinggo', 'sumberasih' => 'Probolinggo',
            'tegalsiwalan' => 'Probolinggo', 'tiris' => 'Probolinggo', 'tongas' => 'Probolinggo',
            'lumajang' => 'Lumajang', 'candipuro' => 'Lumajang', 'gucialit' => 'Lumajang',
            'jatiroto' => 'Lumajang', 'kedungjajang' => 'Lumajang', 'klakah' => 'Lumajang',
            'kunir' => 'Lumajang', 'padang' => 'Lumajang', 'pasirian' => 'Lumajang',
            'pasrujambe' => 'Lumajang', 'pronojiwo' => 'Lumajang', 'randuagung' => 'Lumajang',
            'ranuyoso' => 'Lumajang', 'rowokangkung' => 'Lumajang', 'senduro' => 'Lumajang',
            'sukodono' => 'Lumajang', 'sumbersuko' => 'Lumajang', 'tekung' => 'Lumajang',
            'tempeh' => 'Lumajang', 'tempursari' => 'Lumajang', 'yosowilangun' => 'Lumajang',
            'jember' => 'Jember', 'ajung' => 'Jember', 'ambulu' => 'Jember',
            'arjasa' => 'Jember', 'balung' => 'Jember', 'bangsalsari' => 'Jember',
            'gumukmas' => 'Jember', 'jelbuk' => 'Jember', 'jenggawah' => 'Jember',
            'kalisat' => 'Jember', 'kaliwates' => 'Jember', 'kencong' => 'Jember',
            'ledokombo' => 'Jember', 'mayang' => 'Jember', 'mumbulsari' => 'Jember',
            'pakusari' => 'Jember', 'panti' => 'Jember', 'patrang' => 'Jember',
            'rambipuji' => 'Jember', 'semboro' => 'Jember', 'silo' => 'Jember',
            'sukorambi' => 'Jember', 'sumberbaru' => 'Jember', 'sumberjambe' => 'Jember',
            'sumbersari' => 'Jember', 'tanggul' => 'Jember', 'tempurejo' => 'Jember',
            'umbulsari' => 'Jember', 'wuluhan' => 'Jember',
            'banyuwangi' => 'Banyuwangi', 'bangorejo' => 'Banyuwangi', 'cluring' => 'Banyuwangi',
            'gambiran' => 'Banyuwangi', 'genteng' => 'Banyuwangi', 'giri' => 'Banyuwangi',
            'glagah' => 'Banyuwangi', 'glenmore' => 'Banyuwangi', 'kabat' => 'Banyuwangi',
            'kalibaru' => 'Banyuwangi', 'kalipuro' => 'Banyuwangi', 'licin' => 'Banyuwangi',
            'muncar' => 'Banyuwangi', 'pesanggaran' => 'Banyuwangi',
            'purwoharjo' => 'Banyuwangi', 'rogojampi' => 'Banyuwangi', 'sempu' => 'Banyuwangi',
            'siliragung' => 'Banyuwangi', 'singojuruh' => 'Banyuwangi', 'songgon' => 'Banyuwangi',
            'srono' => 'Banyuwangi', 'tegaldlimo' => 'Banyuwangi', 'tegalsari' => 'Banyuwangi',
            'wongsorejo' => 'Banyuwangi',
            'magetan' => 'Magetan', 'barat' => 'Magetan', 'bendo' => 'Magetan',
            'karangrejo' => 'Magetan', 'karas' => 'Magetan', 'kartoharjo' => 'Magetan',
            'kawedanan' => 'Magetan', 'lembeyan' => 'Magetan', 'maospati' => 'Magetan',
            'ngariboyo' => 'Magetan', 'nguntoronadi' => 'Magetan', 'panekan' => 'Magetan',
            'parang' => 'Magetan', 'plaosan' => 'Magetan', 'poncol' => 'Magetan',
            'sidorejo' => 'Magetan', 'takeran' => 'Magetan',
            'ponorogo' => 'Ponorogo', 'babadan' => 'Ponorogo', 'badegan' => 'Ponorogo',
            'balong' => 'Ponorogo', 'bungkal' => 'Ponorogo', 'jambon' => 'Ponorogo',
            'jenangan' => 'Ponorogo', 'jetis' => 'Ponorogo', 'kauman' => 'Ponorogo',
            'mlarak' => 'Ponorogo', 'ngebel' => 'Ponorogo', 'ngrayun' => 'Ponorogo',
            'pudak' => 'Ponorogo', 'pulung' => 'Ponorogo', 'sambit' => 'Ponorogo',
            'sampung' => 'Ponorogo', 'sawoo' => 'Ponorogo', 'siman' => 'Ponorogo',
            'slahung' => 'Ponorogo', 'sooko' => 'Ponorogo', 'sukorejo' => 'Ponorogo',
            'pacitan' => 'Pacitan', 'arjosari' => 'Pacitan', 'bandar' => 'Pacitan',
            'donorojo' => 'Pacitan', 'kebonagung' => 'Pacitan', 'nawangan' => 'Pacitan',
            'ngadirojo' => 'Pacitan', 'pringkuku' => 'Pacitan', 'punung' => 'Pacitan',
            'sudimoro' => 'Pacitan', 'tegalombo' => 'Pacitan', 'tulakan' => 'Pacitan',
            'trenggalek' => 'Trenggalek', 'bendungan' => 'Trenggalek', 'dongko' => 'Trenggalek',
            'durenan' => 'Trenggalek', 'gandusari' => 'Trenggalek', 'kampak' => 'Trenggalek',
            'karangan' => 'Trenggalek', 'munjungan' => 'Trenggalek', 'panggul' => 'Trenggalek',
            'pogalan' => 'Trenggalek', 'pule' => 'Trenggalek', 'suruh' => 'Trenggalek',
            'tugu' => 'Trenggalek', 'watulimo' => 'Trenggalek',
            'situbondo' => 'Situbondo', 'asembagus' => 'Situbondo',
            'banyuglugur' => 'Situbondo', 'banyuputih' => 'Situbondo',
            'bungatan' => 'Situbondo', 'jangkar' => 'Situbondo',
            'jatibanteng' => 'Situbondo', 'kapongan' => 'Situbondo', 'kendit' => 'Situbondo',
            'mangaran' => 'Situbondo', 'mlandingan' => 'Situbondo',
            'panarukan' => 'Situbondo', 'panji' => 'Situbondo', 'suboh' => 'Situbondo',
            'sumbermalang' => 'Situbondo',
            'bondowoso' => 'Bondowoso', 'binakal' => 'Bondowoso', 'botolinggo' => 'Bondowoso',
            'cermee' => 'Bondowoso', 'curahdami' => 'Bondowoso', 'klabang' => 'Bondowoso',
            'maesan' => 'Bondowoso', 'pakem' => 'Bondowoso', 'prajekan' => 'Bondowoso',
            'pujer' => 'Bondowoso', 'sempol' => 'Bondowoso', 'sukosari' => 'Bondowoso',
            'sumberwringin' => 'Bondowoso', 'tamanan' => 'Bondowoso', 'tapen' => 'Bondowoso',
            'tegalampel' => 'Bondowoso', 'tenggarang' => 'Bondowoso',
            'tlogosari' => 'Bondowoso', 'wringin' => 'Bondowoso',
            'batu' => 'Batu', 'bumiaji' => 'Batu', 'junrejo' => 'Batu',
        ];
    }

    public static function desaMap(): array
    {
        return include __DIR__ . '/../Data/DesaData.php';
    }

    public function guessKabupaten(string $routeName): string
    {
        $lower = strtolower($routeName);

        foreach (self::kabupatenKeywords() as $kw => $kab) {
            if (str_contains($lower, $kw) && preg_match('/\b' . preg_quote($kw, '/') . '\b/u', $lower)) return $kab;
        }

        foreach (self::kecamatanMap() as $kec => $kab) {
            if (str_contains($lower, $kec) && preg_match('/\b' . preg_quote($kec, '/') . '\b/', $lower)) return $kab;
        }

        $desa = self::desaMap();
        foreach ($desa as $name => $kab) {
            if (str_contains($lower, $name) && preg_match('/\b' . preg_quote($name, '/') . '\b/', $lower)) return $kab;
        }

        $ml = (new TujuanMlService())->predictKabupaten($routeName);
        return $ml['kabupaten'] ?? 'Lainnya';
    }

    public function guessWaktu(string $routeName): string
    {
        return str_contains(strtolower($routeName), 'malam') ? 'malam' : 'pagi';
    }

    private function calculateDt(Sopir $sopir, $date, string $kabupaten, string $waktu): int
    {
        $dtValue = Cache::get('dt_nominal', config('dt.value', 330000));
        $kabNorm = strtolower(trim($kabupaten));
        $specialKabs = array_map('strtolower', config('dt.single_dt_regencies'));
        if (in_array($kabNorm, $specialKabs)) {
            $existing = Ritase::where('kode_sopir', $sopir->kode_sopir)->where('tanggal', $date)->where('kabupaten', $kabupaten)->where('waktu', $waktu)->where('status', '!=', 'gagal_produksi')->first();
            if ($existing) $dtValue = 0;
        }
        return $dtValue;
    }
}
