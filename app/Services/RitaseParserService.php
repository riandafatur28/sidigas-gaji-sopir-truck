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
        ];

        $lines = preg_split('/\r\n|\n|\r/', $text);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines, fn($l) => $l !== '');
        $lines = array_values($lines);

        if (empty($lines)) {
            return $result;
        }

        // First line: date format "DD MM YY day" or "DD MM YYYY"
        $dateLine = $lines[0];
        $date = $this->parseDate($dateLine);

        if ($date) {
            $result['date'] = $date;
        }

        // Load sopir names for detecting implicit drivers in route lines
        $sopirNames = Sopir::where('status', 'aktif')->pluck('nama')->map(fn($n) => strtolower($n))->toArray();

        // Parse remaining lines for routes and drivers
        $currentPackage = null;

        for ($i = 1; $i < count($lines); $i++) {
            $line = $lines[$i];

            // Detect route line (contains keywords or not a numbered line)
            if (!$this->looksLikeDriverName($line)) {
                // Push previous package
                if ($currentPackage !== null) {
                    $result['packages'][] = $currentPackage;
                }

                // Extract driver name(s) from route line prefix if first word matches a sopir
                $implicitDrivers = [];
                $words = explode(' ', $line);
                while (!empty($words) && in_array(strtolower($words[0]), $sopirNames)) {
                    $implicitDrivers[] = array_shift($words);
                }
                $cleanedRoute = implode(' ', $words);
                // If driver was extracted but no route remains, treat full line as route
                if (empty($cleanedRoute) && !empty($implicitDrivers)) {
                    // Unlikely — keep original line as route, no implicit driver
                    $implicitDrivers = [];
                    $cleanedRoute = $line;
                }

                $currentPackage = [
                    'route_name' => $cleanedRoute,
                    'drivers' => $implicitDrivers,
                ];
                continue;
            }

            // Detect driver line (numbered)
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
                    $currentPackage['drivers'][] = $driverName;
                }
                continue;
            }
        }

        // Push last package if exists
        if ($currentPackage !== null) {
            $result['packages'][] = $currentPackage;
        }

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
            $driverScore = 0;

            foreach ($allSopirs as $sopir) {
                $similarity = $this->calculateStringSimilarity($driverName, $sopir->nama);
                $normalizedScore = $similarity * 100;

                if ($normalizedScore > $bestScore) {
                    $bestScore = $normalizedScore;
                    $bestMatch = $sopir;
                }
            }

            // Apply confidence threshold (minimum 70%)
            if ($bestScore >= 70) {
                $driverScore = $bestScore;
            } elseif (strlen($driverName) <= 4) {
                // Short names get bonus
                $driverScore = $bestScore + 10;
            }

            $results[] = [
                'input_name' => $driverName,
                'matched' => $driverScore >= 70,
                'sopir' => $bestMatch,
                'confidence' => round($driverScore, 2),
            ];
        }

        return $results;
    }

    /**
     * Match routes from parsed to existing tujuan (locations) with similarity.
     * Strips known non-location prefixes, then tries shorter versions for low confidence.
     */
    public function matchRoutes(array $routeNames): array
    {
        $results = [];
        $allTujuan = Tujuan::all(['id', 'nama', 'kode_tujuan']);

        // Known non-location prefixes to strip before matching
        $stripPrefixes = ['paket cmm', 'paket', 'patching', 'bondan', 'gabungan', 'rombongan', 'cmm'];

        foreach ($routeNames as $routeName) {
            $bestMatch = null;
            $bestScore = 0;

            // Strip known non-location prefixes from route name
            $cleanRoute = $routeName;
            $lowerRoute = strtolower($cleanRoute);
            foreach ($stripPrefixes as $prefix) {
                if (str_starts_with($lowerRoute, $prefix . ' ')) {
                    $cleanRoute = trim(substr($cleanRoute, strlen($prefix) + 1));
                    $lowerRoute = strtolower($cleanRoute);
                }
            }

            // Try progressively shorter versions of the minimal route name
            $attempts = [$cleanRoute];
            $words = explode(' ', $cleanRoute);
            while (count($words) > 1) {
                array_shift($words);
                $attempts[] = implode(' ', $words);
            }

            foreach ($attempts as $attempt) {
                foreach ($allTujuan as $tujuan) {
                    $similarity = $this->calculateStringSimilarity($attempt, $tujuan->nama);
                    $normalizedScore = $similarity * 100;

                    if ($normalizedScore > $bestScore) {
                        $bestScore = $normalizedScore;
                        $bestMatch = $tujuan;
                    }
                }

                // If good match found, stop trying shorter versions
                if ($bestScore >= 80) {
                    break;
                }
            }

            $results[] = [
                'input_route' => $routeName,
                'matched' => $bestScore >= 60,
                'tujuan' => $bestMatch,
                'confidence' => round($bestScore, 2),
            ];
        }

        return $results;
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

        foreach ($parsed['packages'] as $package) {
            $routeName = $package['route_name'];
            $driverNames = $package['drivers'] ?? [];

            $routeMatch = $routeMatchesMap[$routeName] ?? null;
            $kodeTujuan = $routeMatch && $routeMatch['matched'] ? $routeMatch['tujuan']->kode_tujuan : null;

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

            // Cek duplicate by kode_sopir + tanggal
            $sopir = $matchedSopirs[0]['sopir'];
            $duplicate = Ritase::where('periode_id', $periodeId)
                ->where('kode_sopir', $sopir->kode_sopir)
                ->where('tanggal', $parsed['date'])
                ->exists();

            if ($duplicate) {
                $skipped++;
                $details[] = [
                    'route' => $routeName,
                    'status' => 'Skipped',
                    'reason' => 'Duplicate (same sopir + date)',
                ];
                continue;
            }

            try {
                // Tentukan waktu & kabupaten dari route match
                $tujuan = $routeMatch ? $routeMatch['tujuan'] : null;
                $kabupaten = $tujuan->kabupaten ?? $this->guessKabupaten($routeName);
                $waktu = $this->guessWaktu($driverNames, $parsed['date']);

                $ritase = new Ritase();
                $ritase->periode_id = $periodeId;
                $ritase->kode_sopir = $sopir->kode_sopir;
                $ritase->kode_tujuan = $kodeTujuan;
                $ritase->tanggal = $parsed['date'];
                $ritase->waktu = $waktu;
                $ritase->kabupaten = $kabupaten;
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
                $errors[] = "Failed to create for '{$routeName}': " . $e->getMessage();
                $skipped++;
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
        $kabupatenMap = [
            'nganjuk' => 'Nganjuk',
            'kediri' => 'Kediri',
            'jombang' => 'Jombang',
            'blitar' => 'Blitar',
            'pare' => 'Kediri',
            'watualang' => 'Ngawi',
            'ngawi' => 'Ngawi',
        ];

        foreach ($kabupatenMap as $keyword => $kab) {
            if (str_contains($routeLower, $keyword)) {
                return $kab;
            }
        }

        return 'Lainnya';
    }

    /**
     * Guess waktu — default pagi for all parsed records.
     * Text format has no shift indicator; user confirms all are morning trips.
     */
    protected function guessWaktu(array $driverNames, string $date): string
    {
        return 'pagi';
    }
}
