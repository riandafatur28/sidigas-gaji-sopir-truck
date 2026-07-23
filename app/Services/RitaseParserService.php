<?php

namespace App\Services;

use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Models\Periode;
use Illuminate\Support\Str;

class RitaseParserService
{
    /**
     * Parse raw text message into structured ritase data.
     * Format: "22 07 26 rabu\n" then route lines and "N. Nama" driver lines.
     */
    public function parse(string $text): array
    {
        $result = [
            'date' => null,
            'packages' => [],
            'header_kode_tujuan' => null,
        ];

        $lines = preg_split('/\r\n|\n|\r/', $text);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines, fn($l) => $l !== '');
        $lines = array_values($lines);

        if (empty($lines)) {
            return $result;
        }

        // Scan ALL lines for first date pattern (header lines may come before date)
        $date = null;
        $dateLineIdx = -1;
        foreach ($lines as $idx => $line) {
            $d = $this->parseDate($line);
            if ($d) {
                $date = $d;
                $dateLineIdx = $idx;
                break;
            }
            // Also try finding date embedded in the line (e.g. "BBM: ... 29 06 26 senin")
            // Use negative lookaround to avoid matching inside prices/times
            if (preg_match_all('/(?<!\d)(\d{1,2})\s+(\d{1,2})\s+(\d{2,4})(?!\d)/', $line, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                    $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                    $year = $m[3];
                    if (strlen($year) === 2) { $year = '20'.$year; }
                    if (checkdate((int)$month, (int)$day, (int)$year)) {
                        $date = "{$year}-{$month}-{$day}";
                        $dateLineIdx = $idx;
                        break 2;
                    }
                }
            }
        }

        if ($date) {
            $result['date'] = $date;
        }

        // If first line is NOT a date, try to match it as a header route
        // (e.g. "malam Kertosono" before the date/BBM line)
        if ($dateLineIdx > 0) {
            $headerLine = $lines[0];
            // Skip if it's a BBM/upah header line
            if (!str_starts_with(strtolower($headerLine), 'bbm')) {
                $headerMatches = $this->matchRoutes([$headerLine]);
                if (!empty($headerMatches) && $headerMatches[0]['matched']) {
                    $result['header_kode_tujuan'] = $headerMatches[0]['tujuan']->kode_tujuan;
                }
                // Detect shift from header line (e.g. "malam Kertosono" -> malam)
                if (str_contains(strtolower($headerLine), 'malam')) {
                    $result['header_waktu'] = 'malam';
                }
            }
        }

        // Parse remaining lines for routes and drivers
        $currentPackage = null;
        $batchStartIdx = 0; // index in result[packages] where current driver-batch started
        $seenDrivers = false; // true once any numbered line was added in current batch

        for ($i = 1; $i < count($lines); $i++) {
            $line = $lines[$i];

            // Skip BBM/upah/kompensasi header lines (meta info, not a route)
            if (str_starts_with(strtolower($line), 'bbm')) {
                continue;
            }

            // Detect route line (contains keywords or not a numbered line)
            if (!$this->looksLikeDriverName($line)) {
                // Push previous package
                if ($currentPackage !== null) {
                    $result['packages'][] = $currentPackage;
                }

                // Route type keywords — mark where route description starts
                // Format: [sopir names] [keyword] [route details]
                $routeKeywords = ['patching', 'paket', 'overlay', 'cmm'];

                $implicitDrivers = [];
                $routeName = $line;

                $lowerLine = strtolower($line);

                // Check if line starts with a keyword → pure route, no sopir
                $startsWithKw = false;
                foreach ($routeKeywords as $kw) {
                    if (str_starts_with($lowerLine, $kw . ' ') || $lowerLine === $kw) {
                        $startsWithKw = true;
                        break;
                    }
                }

                if (!$startsWithKw) {
                    // Check for keyword NOT at start → sopir before keyword
                    $foundPos = null;
                    $foundKw = null;
                    foreach ($routeKeywords as $kw) {
                        $pos = strpos($lowerLine, ' ' . $kw . ' ');
                        if ($pos !== false) {
                            $foundPos = $pos + 1; // position of keyword start
                            $foundKw = $kw;
                            break;
                        }
                        // Line ends with keyword (no trailing space)
                        if (str_ends_with($lowerLine, ' ' . $kw)) {
                            $foundPos = strlen($line) - strlen($kw);
                            $foundKw = $kw;
                            break;
                        }
                    }

                    if ($foundPos !== null) {
                        $prefix = trim(substr($line, 0, $foundPos));
                        $routeName = trim(substr($line, $foundPos));
                        // Seluruh prefix adalah 1 nama driver (Yuri badug → satu nama)
                        $implicitDrivers = [$prefix];
                    }
                }

                $currentPackage = [
                    'route_name' => $routeName,
                    'drivers' => $implicitDrivers,
                ];

                // If numbered drivers were seen, this is a new batch — reset start
                if ($seenDrivers) {
                    $batchStartIdx = count($result['packages']);
                    $seenDrivers = false;
                }
                continue;
            }

            // Detect driver line (numbered) — only applies to currentPackage
            if (preg_match('/^\d+\.(.*)$/', $line, $matches)) {
                if ($currentPackage === null) {
                    // No package context, create temporary package
                    $currentPackage = [
                        'route_name' => 'Unknown Route',
                        'drivers' => [],
                    ];
                }

                $driverName = trim($matches[1]);
                $driverName = $this->cleanDriverName($driverName);

                if (!empty($driverName)) {
                    $seenDrivers = true;
                    $lowerDriver = strtolower($driverName);
                    if (!in_array($lowerDriver, array_map('strtolower', $currentPackage['drivers']), true)) {
                        $currentPackage['drivers'][] = $driverName;
                    }
                }
                continue;
            }
        }

        // Push last package if exists
        if ($currentPackage !== null) {
            $result["packages"][] = $currentPackage;
        }

        // Merge packages with same route_name (gabung driver)
        $merged = [];
        foreach ($result["packages"] as $pkg) {
            $key = $pkg["route_name"];
            if (!isset($merged[$key])) {
                $merged[$key] = $pkg;
            } else {
                foreach ($pkg["drivers"] as $d) {
                    if (!in_array(strtolower($d), array_map("strtolower", $merged[$key]["drivers"]))) {
                        $merged[$key]["drivers"][] = $d;
                    }
                }
            }
        }
        $result["packages"] = array_values($merged);

        // Post-processing: associate following route with gagal produksi
        // "Gagal produksi ... 13 drivers ... kertosono malam" → gagal pakai route itu
        $gagalIdx = null;
        foreach ($result['packages'] as $idx => $pkg) {
            if (str_contains(strtolower($pkg['route_name']), 'gagal')) {
                $gagalIdx = $idx;
            } elseif ($gagalIdx !== null && empty($pkg['drivers'])) {
                // Route-only package right after gagal → assign as gagal target
                $result['packages'][$gagalIdx]['gagal_route'] = $pkg['route_name'];
                unset($result['packages'][$idx]);
                $gagalIdx = null;
            } else {
                $gagalIdx = null;
            }
        }
        $result['packages'] = array_values($result['packages']);

        return $result;
    }

    /**
     * Parse date from first line format "DD MM YY".
     */
    protected function parseDate(string $line): ?string
    {
        // Format: "22 07 26 rabu" or "22 07 2026 rabu"
        if (preg_match('/^(\d{1,2})\s+(\d{1,2})\s+(\d{2,4})/', $line, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $year = $m[3];

            if (strlen($year) === 2) {
                $year = '20' . $year;
            }

            // Validate
            if (checkdate((int)$month, (int)$day, (int)$year)) {
                return "{$year}-{$month}-{$day}";
            }
        }

        // Try ISO format "2026-07-22"
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $line, $m)) {
            if (checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
                return $line;
            }
        }

        return null;
    }

    /**
     * Check if line looks like a driver name (for parsing logic).
     */
    protected function looksLikeDriverName(string $line): bool
    {
        return preg_match('/^\d+\./', $line);
    }

    /**
     * Clean up driver name.
     */
    protected function cleanDriverName(string $name): string
    {
        $name = preg_replace('/^(mbah|pak|bu|ira)\s*/i', '', $name);
        $name = str_replace(['√', '✔', '✓', '🙏', '🙌'], '', $name);
        $name = preg_replace('/\s+/u', ' ', $name);
        return trim($name);
    }

    /**
     * Match drivers from parsed to existing sopirs with fuzzy matching.
     */
    public function matchDrivers(array $driverNames): array
    {
        $results = [];
        $allSopirs = Sopir::all(['id', 'nama', 'kode_sopir']);

        foreach ($driverNames as $driverName) {
            $bestMatch = null;
            $bestScore = 0;
            $lowerInput = strtolower(trim($driverName));
            $inputMeta = metaphone($driverName);

            foreach ($allSopirs as $sopir) {
                $score = 0;
                $sopirLower = strtolower(trim($sopir->nama));

                // 1) Exact match (case-insensitive) → 100%
                if ($sopirLower === $lowerInput) {
                    $score = 100;
                }
                // 2) Phonetic match (metaphone) → 95% — catches Toni↔Tony
                elseif ($inputMeta !== '' && $inputMeta === metaphone($sopir->nama)) {
                    $score = 95;
                }
                // 3) Substring match — "Eko" ↔ "Eko Wilangan", "Wilujeng" ↔ "Mbah Wilujeng"
                elseif (str_contains($sopirLower, $lowerInput) || str_contains($lowerInput, $sopirLower)) {
                    // Avoid matching single-letter or 2-char substrings
                    if (strlen($lowerInput) > 2 || strlen($sopirLower) > 2) {
                        $score = 90;
                    }
                }
                // 4) Jaro-Winkler fallback with strict 85% threshold + first-letter check
                else {
                    $similarity = $this->calculateStringSimilarity($driverName, $sopir->nama) * 100;
                    if ($similarity >= 85) {
                        $firstCharMatch = strtolower(substr($sopir->nama, 0, 1)) === strtolower(substr($driverName, 0, 1));
                        if ($firstCharMatch) {
                            $score = $similarity;
                        }
                    }
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $sopir;
                }
            }

            $matched = $bestScore >= 85;
            $results[] = [
                'input_name' => $driverName,
                'matched' => $matched,
                'sopir' => $bestMatch,
                'confidence' => round($matched ? $bestScore : 0, 2),
            ];
        }

        return $results;
    }

    /**
     * Match routes from parsed to existing tujuan (locations) with NER hybrid.
     * Levels: exact (100%) → word-in-tujuan (90%) → Jaro-Winkler ≥85% + first-letter (80%).
     * Strips known non-location prefixes from both input and tujuan names before comparing.
     */
    public function matchRoutes(array $routeNames): array
    {
        $results = [];
        $allTujuan = Tujuan::all(['id', 'nama', 'kode_tujuan']);

        // Known non-location prefixes to strip before matching
        $stripPrefixes = ['paket cmm', 'paket', 'patching', 'bondan', 'gabungan', 'rombongan', 'cmm'];

        // Preprocess each tujuan: strip prefixes for comparison
        $processedTujuan = [];
        foreach ($allTujuan as $tujuan) {
            $stripped = $this->stripRoutePrefixes($tujuan->nama, $stripPrefixes);
            $processedTujuan[] = [
                'model' => $tujuan,
                'stripped' => $stripped,
                'stripped_lower' => strtolower($stripped),
                'words' => explode(' ', strtolower($stripped)),
            ];
        }

        foreach ($routeNames as $routeName) {
            $bestMatch = null;
            $bestScore = 0;
            $matchType = 'none';

            // Strip known prefixes from input route
            $cleanRoute = $this->stripRoutePrefixes($routeName, $stripPrefixes);
            $cleanLower = strtolower($cleanRoute);
            $cleanWords = explode(' ', $cleanLower);

            foreach ($processedTujuan as $pt) {
                $score = 0;
                $type = '';

                // 1) Exact match (after prefix stripping) → 100%
                if ($cleanLower === $pt['stripped_lower']) {
                    $score = 100;
                    $type = 'exact';
                }
                // 2) All input words appear as contiguous substring within tujuan stripped name → 95%
                elseif (str_contains($pt['stripped_lower'], $cleanLower)) {
                    $score = 95;
                    $type = 'substring';
                }
                // 3) Every word from one side found in the other (bidirectional) → 90%
                else {
                    $inputInTujuan = !empty($cleanWords);
                    foreach ($cleanWords as $w) {
                        if (strlen($w) < 2) continue;
                        $found = false;
                        foreach ($pt['words'] as $tw) {
                            if ($tw === $w) { $found = true; break; }
                        }
                        if (!$found) { $inputInTujuan = false; break; }
                    }

                    $tujuanInInput = !empty($pt['words']);
                    foreach ($pt['words'] as $tw) {
                        if (strlen($tw) < 2) continue;
                        $found = false;
                        foreach ($cleanWords as $w) {
                            if ($w === $tw) { $found = true; break; }
                        }
                        if (!$found) { $tujuanInInput = false; break; }
                    }

                    if ($inputInTujuan || $tujuanInInput) {
                        $score = 90;
                        $type = 'bidirectional-words';
                    }
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $pt['model'];
                    $matchType = $type;
                }
            }

            // 4) Fallback: Jaro-Winkler ≥85% with first-letter match
            if ($bestScore < 85) {
                foreach ($processedTujuan as $pt) {
                    $similarity = $this->calculateStringSimilarity($cleanRoute, $pt['stripped']);
                    $normalizedScore = $similarity * 100;

                    if ($normalizedScore >= 85
                        && !empty($cleanRoute) && !empty($pt['stripped'])
                        && $cleanLower[0] === $pt['stripped_lower'][0]
                    ) {
                        if ($normalizedScore > $bestScore) {
                            $bestScore = $normalizedScore;
                            $bestMatch = $pt['model'];
                            $matchType = 'fuzzy';
                        }
                    }
                }
            }

            $results[] = [
                'input_route' => $routeName,
                'matched' => $bestScore >= 80,
                'tujuan' => $bestMatch,
                'confidence' => round($bestScore, 2),
            ];
        }

        return $results;
    }

    /**
     * Strip known non-location prefixes from a route or tujuan name (used once).
     */
    protected function stripRoutePrefixes(string $name, array $prefixes): string
    {
        $lower = strtolower($name);
        foreach ($prefixes as $prefix) {
            if (str_starts_with($lower, $prefix . ' ')) {
                $name = trim(substr($name, strlen($prefix) + 1));
                $lower = strtolower($name);
            }
        }
        return $name;
    }

    /**
     * Calculate similarity score between two strings.
     */
    protected function calculateStringSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower(substr($str1, 0, 50));
        $str2 = strtolower(substr($str2, 0, 50));

        if ($str1 === $str2) {
            return 1.0;
        }

        $jaro = $this->jaroDistance($str1, $str2);
        $jaroWinkler = $this->jaroWinklerDistance($jaro, $str1, $str2);

        return max($jaroWinkler, $jaro);
    }

    protected function jaroDistance(string $str1, string $str2): float
    {
        if ($str1 === $str2) {
            return 1.0;
        }

        $len1 = strlen($str1);
        $len2 = strlen($str2);
        $matchDistance = (int)(max($len1, $len2) / 2) - 1;

        $matches1 = [];
        $matches2 = [];
        $matchCount = 0;

        for ($i = 0; $i < $len1; $i++) {
            $start = max(0, $i - $matchDistance);
            $end = min($i + $matchDistance + 1, $len2);

            for ($j = $start; $j < $end; $j++) {
                if (isset($str2[$j]) && $str1[$i] === $str2[$j] && !in_array($j, $matches2)) {
                    $matches1[] = $i;
                    $matches2[] = $j;
                    $matchCount++;
                    break;
                }
            }
        }

        if ($matchCount == 0) {
            return 0.0;
        }

        $transpositions = 0;
        for ($i = 0; $i < $matchCount; $i++) {
            if ($matches1[$i] !== $matches2[$i]) {
                $transpositions++;
            }
        }
        $transpositions = $transpositions / 2;

        $jaro = ($matchCount / $len1 + $matchCount / $len2 + ($matchCount - $transpositions) / $matchCount) / 3;

        return $jaro;
    }

    protected function jaroWinklerDistance(float $jaro, string $str1, string $str2): float
    {
        $prefixLength = 0;
        $maxPrefix = min(4, min(strlen($str1), strlen($str2)));

        for ($i = 0; $i < $maxPrefix; $i++) {
            if ($str1[$i] === $str2[$i]) {
                $prefixLength++;
            } else {
                break;
            }
        }

        return $jaro + ($prefixLength * 0.1 * (1 - $jaro));
    }

    /**
     * Create ritase records from parsed data with correct field mapping.
     */
    public function createRitases(array $parsed, int $periodeId, array $driverMatches = [], array $routeMatches = []): array
    {
        $created = 0;
        $skipped = 0;
        $errors = [];
        $details = [];

        if (empty($parsed['date'])) {
            $errors[] = 'No date found in parsed data';
            return compact('created', 'skipped', 'errors', 'details');
        }

        $periode = Periode::find($periodeId);
        if (!$periode) {
            $errors[] = "Periode not found with ID: $periodeId";
            return compact('created', 'skipped', 'errors', 'details');
        }

        $driverMatchesMap = collect($driverMatches)->keyBy('input_name');
        $routeMatchesMap = collect($routeMatches)->keyBy('input_route');

        // Auto-create unmatched drivers & routes as new DB records
        $createdDrivers = [];
        $createdRoutes = [];

        foreach ($parsed['packages'] as $package) {
            // Auto-create unmatched drivers in this package
            foreach (($package['drivers'] ?? []) as $driverName) {
                $key = $driverName;
                if (isset($createdDrivers[$key])) {
                    continue; // already created in this batch
                }
                $dm = $driverMatchesMap[$key] ?? null;
                if ($dm && $dm['matched']) {
                    continue; // already exists in DB
                }
                // Create new sopir
                $last = $this->getLastSopir();
                $num = $last ? (int)substr($last->kode_sopir, 4) + 1 : 1;
                $kode = 'SPR-' . str_pad($num, 3, '0', STR_PAD_LEFT);
                $sopir = \App\Models\Sopir::create([
                    'kode_sopir' => $kode,
                    'nama' => $driverName,
                    'status' => 'aktif',
                ]);
                $createdDrivers[$key] = true;
                // Add to driverMatchesMap so it's used in processing
                $driverMatchesMap[$key] = [
                    'input_name' => $driverName,
                    'matched' => true,
                    'sopir' => $sopir,
                    'confidence' => 100,
                ];
            }

            // Auto-create unmatched routes (skip "gagal produksi" — itu status, bukan route)
            $routeName = $package['route_name'];
            $rKey = $routeName;
            $isGagalRp = str_contains(strtolower($routeName), 'gagal');
            if (!$isGagalRp && !isset($createdRoutes[$rKey])) {
                $rm = $routeMatchesMap[$rKey] ?? null;
                if (!$rm || !$rm['matched']) {
                    $last = $this->getLastTujuan();
                    $num = $last ? (int)substr($last->kode_tujuan, 4) + 1 : 1;
                    $kode = 'TUJ-' . str_pad($num, 3, '0', STR_PAD_LEFT);
                    $tujuan = \App\Models\Tujuan::create([
                        'kode_tujuan' => $kode,
                        'nama' => $routeName,
                        'status' => 'aktif',
                    ]);
                    $createdRoutes[$rKey] = true;
                    // Set kabupaten guess on the model for later use
                    $tujuan->setAttribute('kabupaten', $this->guessKabupaten($routeName));
                    $routeMatchesMap[$rKey] = [
                        'input_route' => $routeName,
                        'matched' => true,
                        'tujuan' => $tujuan,
                        'confidence' => 100,
                    ];
                }
            }
        }

        foreach ($parsed['packages'] as $package) {
            $routeName = $package['route_name'];
            $driverNames = $package['drivers'] ?? [];

            $isGagal = str_contains(strtolower($routeName), 'gagal');
            $routeMatch = $routeMatchesMap[$routeName] ?? null;
            $kodeTujuan = ($routeMatch && $routeMatch['matched'] && !$isGagal)
                ? $routeMatch['tujuan']->kode_tujuan
                : null;

            // Bersihin auto-created Tujuan kalo gagal produksi
            if ($isGagal) {
                $tujuanGagal = \App\Models\Tujuan::where('nama', $routeName)->first();
                if ($tujuanGagal) {
                    \App\Models\Ritase::where('kode_tujuan', $tujuanGagal->kode_tujuan)->update(['kode_tujuan' => null]);
                    $tujuanGagal->delete();
                }
            }

            $matchedSopirs = [];

            foreach ($driverNames as $driverName) {
                $driverMatch = $driverMatchesMap[$driverName] ?? null;

                if (!$driverMatch || !$driverMatch['matched']) {
                    continue;
                }

                $matchedSopirs[] = [
                    'sopir' => $driverMatch['sopir'],
                    'confidence' => $driverMatch['confidence'],
                ];
            }

            if (empty($matchedSopirs)) {
                $skipped++;
                $details[] = [
                    'route' => $routeName,
                    'status' => 'Skipped',
                    'reason' => 'No valid drivers matched',
                ];
                continue;
            }

            // Tentukan waktu & kabupaten dari route match
            $tujuan = $routeMatch ? $routeMatch['tujuan'] : null;
            $kabupaten = $tujuan->kabupaten ?? $this->guessKabupaten($routeName);
            $waktu = $this->guessWaktu($driverNames, $routeName);

            $isGagal = str_contains(strtolower($routeName), 'gagal');

            if ($isGagal) {
                // Determine target route for this gagal (kode_tujuan + waktu to set)
                // Priority: gagal_route (line after block) > route w/o 'gagal' suffix (combined line) > header (before date)
                $gagalTarget = null;
                $gagalWaktu = null;

                $gagalRouteOverride = $package['gagal_route'] ?? null;
                if ($gagalRouteOverride) {
                    $gagalMatches = $this->matchRoutes([$gagalRouteOverride]);
                    if (!empty($gagalMatches) && $gagalMatches[0]['matched']) {
                        $gagalTarget = $gagalMatches[0]['tujuan']->kode_tujuan;
                        $gagalWaktu = $this->guessWaktu([], $gagalRouteOverride);
                    }
                }

                // Fallback: strip "gagal" from route name ("Overlay malam kertosono Gagal produksi")
                if (empty($gagalTarget)) {
                    $cleanGagal = preg_replace('/\s+gagal(\s+produksi)?$/i', '', $routeName);
                    $cleanGagal = trim($cleanGagal);
                    if ($cleanGagal !== $routeName && !empty($cleanGagal)) {
                        $gm = $this->matchRoutes([$cleanGagal]);
                        if (!empty($gm) && $gm[0]['matched']) {
                            $gagalTarget = $gm[0]['tujuan']->kode_tujuan;
                            $gagalWaktu = $this->guessWaktu([], $cleanGagal);
                        }
                    }
                }

                if (empty($gagalTarget) && !empty($parsed['header_kode_tujuan'])) {
                    $gagalTarget = $parsed['header_kode_tujuan'];
                    $gagalWaktu = $parsed['header_waktu'] ?? null;
                }

                // Gagal produksi: update existing ritase for these drivers on same date
                $updated = 0;
                foreach ($matchedSopirs as $matchedSopir) {
                    $sopir = $matchedSopir['sopir'];

                    // Find valid record: try gagal's waktu first, then retry without
                    $affected = Ritase::where('periode_id', $periodeId)
                        ->where('kode_sopir', $sopir->kode_sopir)
                        ->where('tanggal', $parsed['date'])
                        ->where('waktu', $waktu)
                        ->where('status', 'valid')
                        ->latest('id')
                        ->limit(1)
                        ->update(['dt' => 0, 'status' => 'gagal_produksi']);

                    if (!$affected) {
                        // Retry without waktu (gagal route may have different shift than original)
                        $affected = Ritase::where('periode_id', $periodeId)
                            ->where('kode_sopir', $sopir->kode_sopir)
                            ->where('tanggal', $parsed['date'])
                            ->where('status', 'valid')
                            ->latest('id')
                            ->limit(1)
                            ->update(['dt' => 0, 'status' => 'gagal_produksi']);
                    }

                    if ($affected) {
                        // Update gagal record: set kode_tujuan + waktu
                        $gagalUpdate = [];
                        if ($gagalTarget) {
                            $gagalUpdate['kode_tujuan'] = $gagalTarget;
                        }
                        if ($gagalWaktu) {
                            $gagalUpdate['waktu'] = $gagalWaktu;
                        }
                        if (!empty($gagalUpdate)) {
                            Ritase::where('periode_id', $periodeId)
                                ->where('kode_sopir', $sopir->kode_sopir)
                                ->where('tanggal', $parsed['date'])
                                ->where('status', 'gagal_produksi')
                                ->latest('id')
                                ->limit(1)
                                ->update($gagalUpdate);
                        }
                        $updated++;
                        $details[] = [
                            'route' => $routeName,
                            'status' => 'Updated to gagal',
                            'sopir' => $sopir->nama,
                            'reason' => 'DT=0, status=gagal_produksi',
                        ];
                    }
                }
                if ($updated === 0) {
                    $skipped++;
                    $details[] = [
                        'route' => $routeName,
                        'status' => 'Skipped',
                        'reason' => 'No valid records to update for gagal produksi',
                    ];
                }
                continue; // skip normal ritase creation
            }

            // Create one ritase per driver
            foreach ($matchedSopirs as $matchedSopir) {
                $sopir = $matchedSopir['sopir'];

                // Cek duplicate by kode_sopir + tanggal + waktu
                $duplicate = Ritase::where('periode_id', $periodeId)
                    ->where('kode_sopir', $sopir->kode_sopir)
                    ->where('tanggal', $parsed['date'])
                    ->where('waktu', $waktu)
                    ->exists();

                if ($duplicate) {
                    $skipped++;
                    $details[] = [
                        'route' => $routeName,
                        'status' => 'Skipped',
                        'sopir' => $sopir->nama,
                        'reason' => 'Duplicate (same sopir + date + waktu)',
                    ];
                    continue;
                }

                try {
                    // DT: 330.000 default, kecuali ada rit lain utk sopir+date+kab+waktu yg sama
                    $dtValue = 330000;
                    $ritLain = Ritase::where('kode_sopir', $sopir->kode_sopir)
                        ->where('tanggal', $parsed['date'])
                        ->where('kabupaten', $kabupaten)
                        ->where('waktu', $waktu)
                        ->where('status', '!=', 'gagal_produksi')
                        ->first();
                    if ($ritLain) {
                        $dtValue = 0;
                    }

                    $ritase = new Ritase();
                    $ritase->periode_id = $periodeId;
                    $ritase->kode_sopir = $sopir->kode_sopir;
                    $ritase->kode_tujuan = $kodeTujuan;
                    $ritase->tanggal = $parsed['date'];
                    $ritase->waktu = $waktu;
                    $ritase->kabupaten = $kabupaten;
                    $ritase->dt = $dtValue;
                    $ritase->status = 'valid';
                    $ritase->catatan = "Auto-create from parser (mode: " . ($parsed['source'] ?? 'rule-based') . ")";
                    $ritase->save();

                    $created++;
                    $details[] = [
                        'route' => $routeName,
                        'status' => 'Created',
                        'sopir' => $sopir->nama,
                        'kode_sopir' => $sopir->kode_sopir,
                        'kode_tujuan' => $kodeTujuan,
                        'waktu' => $waktu,
                        'kabupaten' => $kabupaten,
                    ];
                } catch (\Exception $e) {
                    $errors[] = "Failed to create for '{$routeName}' / {$sopir->nama}: " . $e->getMessage();
                    $skipped++;
                }
            }
        }

        return compact('created', 'skipped', 'errors', 'details');
    }

    /**
     * Guess kabupaten based on route name.
     */
    protected function guessKabupaten(string $routeName): string
    {
        $routeLower = strtolower($routeName);

        // Kecamatan → Kabupaten mapping (word-boundary check)
        $kecamatanMap = [
            // Kabupaten Nganjuk (35.18)
            'bagor' => 'Nganjuk', 'bandarkedungmulyo' => 'Nganjuk', 'baron' => 'Nganjuk',
            'berbek' => 'Nganjuk', 'gondang' => 'Nganjuk', 'jatikalen' => 'Nganjuk',
            'kertosono' => 'Nganjuk', 'lengkong' => 'Nganjuk', 'loceret' => 'Nganjuk',
            'ngetos' => 'Nganjuk', 'ngluyu' => 'Nganjuk', 'ngronggot' => 'Nganjuk',
            'pace' => 'Nganjuk', 'patianrowo' => 'Nganjuk', 'prambon' => 'Nganjuk',
            'rejoso' => 'Nganjuk', 'sawahan' => 'Nganjuk', 'sukomoro' => 'Nganjuk',
            'tanjunganom' => 'Nganjuk', 'wilangan' => 'Nganjuk',
            // Kabupaten Jombang (35.17)
            'bareng' => 'Jombang', 'diwek' => 'Jombang', 'gudo' => 'Jombang',
            'jogoroto' => 'Jombang', 'kabuh' => 'Jombang', 'kesamben' => 'Jombang',
            'kudu' => 'Jombang', 'megaluh' => 'Jombang', 'mojoagung' => 'Jombang',
            'mojowarno' => 'Jombang', 'ngoro' => 'Jombang', 'ngusikan' => 'Jombang',
            'perak' => 'Jombang', 'peterongan' => 'Jombang', 'plandaan' => 'Jombang',
            'ploso' => 'Jombang', 'sumobito' => 'Jombang', 'tembelang' => 'Jombang',
            'wonosalam' => 'Jombang',
            // Kabupaten Kediri (35.06)
            'badas' => 'Kediri', 'banyakan' => 'Kediri', 'gampengrejo' => 'Kediri',
            'grogol' => 'Kediri', 'gurah' => 'Kediri', 'kandangan' => 'Kediri',
            'kandat' => 'Kediri', 'kayen kidul' => 'Kediri', 'kepung' => 'Kediri',
            'kras' => 'Kediri', 'kunjang' => 'Kediri', 'mojo' => 'Kediri',
            'ngadiluwih' => 'Kediri', 'ngancar' => 'Kediri', 'ngasem' => 'Kediri',
            'pagu' => 'Kediri', 'papar' => 'Kediri', 'pare' => 'Kediri',
            'plemahan' => 'Kediri', 'plosoklaten' => 'Kediri', 'puncu' => 'Kediri',
            'purwoasri' => 'Kediri', 'ringinrejo' => 'Kediri', 'semen' => 'Kediri',
            'tarokan' => 'Kediri', 'wates' => 'Kediri',
            // Kota Kediri (35.71)
            'mojoroto' => 'Kota Kediri', 'pesantren' => 'Kota Kediri',
        ];

        foreach ($kecamatanMap as $kec => $kab) {
            // Word-boundary match: bareng matches "bareng" but not "pembarengan"
            if (preg_match('/\b' . preg_quote($kec, '/') . '\b/', $routeLower)) {
                return $kab;
            }
        }

        // Fallback keyword
        $keywordMap = [
            'nganjuk' => 'Nganjuk',
            'kediri' => 'Kediri',
            'jombang' => 'Jombang',
            'blitar' => 'Blitar',
            'watualang' => 'Ngawi',
            'ngawi' => 'Ngawi',
        ];

        foreach ($keywordMap as $keyword => $kab) {
            if (str_contains($routeLower, $keyword)) {
                return $kab;
            }
        }

        return 'Lainnya';
    }

    /**
     * Guess waktu from route name — 'malam' if route contains 'malam', else 'pagi'.
     */
    protected function guessWaktu(array $driverNames, string $routeName): string
    {
        return str_contains(strtolower($routeName), 'malam') ? 'malam' : 'pagi';
    }

    protected function getLastSopir(): ?\App\Models\Sopir
    {
        return \App\Models\Sopir::orderBy('id', 'desc')->first();
    }

    protected function getLastTujuan(): ?\App\Models\Tujuan
    {
        return \App\Models\Tujuan::orderBy('id', 'desc')->first();
    }
}
