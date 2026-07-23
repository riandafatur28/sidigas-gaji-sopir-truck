<?php

namespace App\Services;

use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Models\Periode;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class RitaseParserService
{
    /**
     * Parse raw text message into structured ritase data using LLM.
     */
    public function parse(string $text): array
    {
        $prompt = $this->buildPrompt($text);
        
        // In production, call actual LLM API here (OpenAI, Ollama, etc.)
        // For now, use rule-based parser as fallback
        return $this->ruleBasedParse($text);
    }

    /**
     * Build LLM prompt for parsing.
     */
    protected function buildPrompt(string $text): string
    {
        return <<<PROMPT
Parse this Indonesian dump truck driver schedule text into JSON.

Text:
{$text}

Extract:
1. Date (format: Y-m-d)
2. Array of packages/routes, each with:
   - route_name (raw text)
   - drivers (array of driver names)

Rules:
- Date line: "DD MM YY hari" (e.g., "22 07 26 rabu" = 2022-07-26)
- Package lines contain location keywords: "paket", "bondan", "patching", "kota", "kabupaten"
- Driver lines are numbered: "1. Name", "2. Name"
- Driver names may have typos, nicknames, or suffixes (e.g., "Mbah POR", "Eka bence")
- Return ONLY valid JSON

Example output:
{
  "date": "2022-07-26",
  "packages": [
    {"route_name": "Bondan patching pare kota", "drivers": ["Riki", "Kola", "Firsa", "Wahyu", "Ginem", "Mbah POR", "Didik", "Yuri", "Agung"]},
    {"route_name": "Paket cmm blitar kota", "drivers": []},
    {"route_name": "Paket watualang ngawi", "drivers": ["Gun", "Anjar", "Wilujeng", "Yanto", "Soim", "Kuwat", "Toni", "Aripin", "Avit", "Radib", "Topik", "Narji", "Eka bence", "Prapto", "Berok", "Manto", "Eko Wilangan", "Torik", "Adib", "Wakub"]}
  ]
}
PROMPT;
    }

    /**
     * Rule-based parser (fallback when LLM unavailable).
     */
    public function ruleBasedParse(string $text): array
    {
        $lines = array_map('trim', explode("\n", $text));
        $lines = array_filter($lines, fn($l) => $l !== '');

        $result = [
            'date' => null,
            'packages' => [],
        ];

        $currentPackage = null;

        foreach ($lines as $line) {
            // Detect date line: "22 07 26 rabu" or similar
            if (preg_match('/^(\d{2})\s+(\d{2})\s+(\d{2})\s+\w+/i', $line, $matches)) {
                $year = '20' . $matches[1];
                $month = $matches[2];
                $day = $matches[3];
                $result['date'] = "{$year}-{$month}-{$day}";
                continue;
            }

            // Detect package/route line (contains location keywords, not numbered)
            if (preg_match('/^(paket|bondan|patching|kota|kabupaten|rute|route)/i', $line) 
                || (strlen($line) > 5 && !preg_match('/^\d+\./', $line) && !$this->looksLikeDriverName($line))) {
                
                if ($currentPackage !== null) {
                    $result['packages'][] = $currentPackage;
                }
                
                $currentPackage = [
                    'route_name' => $line,
                    'drivers' => [],
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
     * Check if line looks like a driver name (for parsing logic).
     */
    protected function looksLikeDriverName(string $line): bool
    {
        // Stricter heuristic: numbered lines are always driver lines
        return preg_match('/^\d+\./', $line);
    }

    /**
     * Clean up driver name.
     */
    protected function cleanDriverName(string $name): string
    {
        // Remove titles, punctuation, extra spaces
        $name = preg_replace('/^(mbah|pak|bu|ira)\s*/i', '', $name);
        $name = preg_replace('/\s+/u', ' ', $name); // Normalisasi spasi
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
     */
    public function matchRoutes(array $routeNames): array
    {
        $results = [];

        foreach ($routeNames as $routeName) {
            $bestMatch = null;
            $bestScore = 0;

            // Get all tujuan untuk comparison
            $allTujuan = Tujuan::all(['id', 'nama', 'kode_tujuan']);

            foreach ($allTujuan as $tujuan) {
                $similarity = $this->calculateStringSimilarity($routeName, $tujuan->nama);
                $normalizedScore = $similarity * 100;
                
                if ($normalizedScore > $bestScore) {
                    $bestScore = $normalizedScore;
                    $bestMatch = $tujuan;
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
        // Truncate long strings
        $str1 = strtolower(substr($str1, 0, 50));
        $str2 = strtolower(substr($str2, 0, 50));
        
        // Jika sama persis, langsung return 1.0
        if ($str1 === $str2) {
            return 1.0;
        }

        // Hitung Jaro-Winkler similarity
        $jaro = $this->jaroDistance($str1, $str2);
        $jaroWinkler = $this->jaroWinklerDistance($jaro, $str1, $str2);

        return max($jaroWinkler, $jaro);
    }

    /**
     * Calculate Jaro distance.
     */
    protected function jaroDistance(string $str1, string $str2): float
    {
        if ($str1 === $str2) {
            return 1.0;
        }

        $len1 = strlen($str1);
        $len2 = strlen($str2);
        $matchDistance = (int)max($len1, $len2) / 2 - 1;

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

    /**
     * Calculate Jaro-Winkler distance.
     */
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
     * Create ritase records from parsed data.
     */
    public function createRitases(array $parsed, int $periodeId, array $driverMatches = [], array $routeMatches = []): array
    {
        $created = 0;
        $skipped = 0;
        $errors = [];
        $details = [];

        if (empty($parsed['date'])) {
            $errors[] = 'No date found in parsed data';
            return [
                'created' => 0,
                'skipped' => 0,
                'errors' => $errors,
                'details' => [],
            ];
        }

        $periode = Periode::find($periodeId);
        if (!$periode) {
            $errors[] = "Periode not found with ID: $periodeId";
            return [
                'created' => 0,
                'skipped' => 0,
                'errors' => $errors,
                'details' => [],
            ];
        }

        $driverMatchesMap = collect($driverMatches)->keyBy('input_name');
        $routeMatchesMap = collect($routeMatches)->keyBy('input_route');

        foreach ($parsed['packages'] as $package) {
            $routeName = $package['route_name'];
            $driverNames = $package['drivers'] ?? [];

            // Match the route
            $routeMatch = $routeMatchesMap[$routeName] ?? null;

            // Find all matched sopirs for this package's drivers
            $matchedSopirs = [];
            $packageErrors = [];

            foreach ($driverNames as $driverName) {
                $driverMatch = $driverMatchesMap[$driverName] ?? null;

                if (!$driverMatch || !$driverMatch['matched']) {
                    $packageErrors[] = "Driver '{$driverName}' not found in database";
                    continue;
                }

                $matchedSopirs[] = [
                    'sopir' => $driverMatch['sopir'],
                    'confidence' => $driverMatch['confidence'],
                ];
            }

            // Skip packages with more than 10 drivers (safety measure)
            if (count($matchedSopirs) > 10) {
                $skipped++;
                $details[] = [
                    'route' => $routeName,
                    'status' => 'Skipped',
                    'reason' => 'Too many drivers',
                ];
                continue;
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

            // Check for duplicate ritase
            $duplicate = Ritase::where('periode_id', $periodeId)
                ->where('sopir_id', $matchedSopirs[0]['sopir']->id)
                ->where('tanggal', $parsed['date'])
                ->exists();

            if ($duplicate) {
                $skipped++;
                $details[] = [
                    'route' => $routeName,
                    'status' => 'Skipped',
                    'reason' => 'Duplicate ritase',
                ];
                continue;
            }

            try {
                $ritase = new Ritase();
                $ritase->periode_id = $periodeId;
                $ritase->sopir_id = $matchedSopirs[0]['sopir']->id;
                $ritase->tanggal = $parsed['date'];
                $ritase->save();

                $created++;

                $details[] = [
                    'route' => $routeName,
                    'status' => 'Created',
                    'drivers' => array_column($matchedSopirs, 'sopir', 'sopir.id'),
                ];

            } catch (\Exception $e) {
                $errors[] = "Failed to create ritase for route '{$routeName}': " . $e->getMessage();
                $skipped++;
                $details[] = [
                    'route' => $routeName,
                    'status' => 'Error',
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
            'details' => $details,
        ];
    }
}
?>