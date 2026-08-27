<?php

namespace App\Services;

use App\Models\Sopir;
use App\Models\Tujuan;

/**
 * Fuzzy matching for drivers and routes using string similarity algorithms.
 * Uses Jaro-Winkler distance, metaphone, and word-level matching.
 */
class RitaseFuzzyMatcher
{
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

                if ($sopirLower === $lowerInput) {
                    $score = 100;
                } elseif ($inputMeta !== '' && $inputMeta === metaphone($sopir->nama)) {
                    $score = 95;
                } elseif (str_contains($sopirLower, $lowerInput)) {
                    if (strlen($lowerInput) > 2 || strlen($sopirLower) > 2) {
                        $score = 90;
                    }
                } else {
                    $isSubstringRelation = str_contains($sopirLower, $lowerInput) || str_contains($lowerInput, $sopirLower);
                    if (!$isSubstringRelation) {
                        $similarity = $this->calculateStringSimilarity($driverName, $sopir->nama) * 100;
                        if ($similarity >= 85) {
                            $firstCharMatch = strtolower(substr($sopir->nama, 0, 1)) === strtolower(substr($driverName, 0, 1));
                            if ($firstCharMatch) {
                                $score = $similarity;
                            }
                        }
                    }
                }

                if ($score > $bestScore) { $bestScore = $score; $bestMatch = $sopir; }
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

    public function matchRoutes(array $routeNames): array
    {
        $results = [];
        $routeNames = array_map(fn($name) => preg_replace('/^Paket\s*\d+\s*:\s*/i', '', $name), $routeNames);
        $allTujuan = Tujuan::all(['id', 'nama', 'kode_tujuan']);

        $stripPrefixes = ['paket cmm', 'paket', 'patching', 'bondan', 'gabungan', 'rombongan', 'cmm', 'kormuling', 'rekon', 'overlay'];
        $processedTujuan = [];
        foreach ($allTujuan as $tujuan) {
            $stripped = $this->stripRoutePrefixes($tujuan->nama, $stripPrefixes);
            $processedTujuan[] = [
                'model' => $tujuan, 'stripped' => $stripped,
                'stripped_lower' => strtolower($stripped),
                'words' => explode(' ', strtolower($stripped)),
            ];
        }

        foreach ($routeNames as $routeName) {
            $bestMatch = null;
            $bestScore = 0;

            $cleanRoute = $this->stripRoutePrefixes($routeName, $stripPrefixes);
            $cleanLower = strtolower($cleanRoute);
            $cleanWords = explode(' ', $cleanLower);

            foreach ($processedTujuan as $pt) {
                $score = 0;

                if ($cleanLower === $pt['stripped_lower']) {
                    $score = 100;
                } elseif (str_contains($pt['stripped_lower'], $cleanLower) && mb_strlen($cleanRoute) >= mb_strlen($pt['stripped']) * 0.6) {
                    $score = 95;
                } elseif (str_contains($cleanLower, $pt['stripped_lower']) && mb_strlen($pt['stripped']) >= mb_strlen($cleanRoute) * 0.6) {
                    $score = 95;
                } else {
                    $inputInTujuan = !empty($cleanWords);
                    $inputMatchCount = 0;
                    foreach ($cleanWords as $w) {
                        if (strlen($w) < 2) continue;
                        $found = false;
                        foreach ($pt['words'] as $tw) { if ($tw === $w) { $found = true; break; } }
                        if (!$found) { $inputInTujuan = false; break; }
                        $inputMatchCount++;
                    }

                    $tujuanInInput = !empty($pt['words']);
                    $tujuanMatchCount = 0;
                    foreach ($pt['words'] as $tw) {
                        if (strlen($tw) < 2) continue;
                        $found = false;
                        foreach ($cleanWords as $w) { if ($w === $tw) { $found = true; break; } }
                        if (!$found) { $tujuanInInput = false; break; }
                        $tujuanMatchCount++;
                    }

                    if ($inputInTujuan && $inputMatchCount >= 2 && $tujuanMatchCount >= 1) { $score = 90; }
                    elseif ($tujuanInInput && $tujuanMatchCount >= 2 && $inputMatchCount >= 1) { $score = 90; }
                }

                if ($score > $bestScore) { $bestScore = $score; $bestMatch = $pt['model']; }
            }

            if ($bestScore < 85) {
                foreach ($processedTujuan as $pt) {
                    $similarity = $this->calculateStringSimilarity($cleanRoute, $pt['stripped']) * 100;
                    $sharedWords = array_intersect($cleanWords, $pt['words']);
                    if ($similarity >= 90 && !empty($cleanRoute) && !empty($pt['stripped'])
                        && $cleanLower[0] === $pt['stripped_lower'][0] && count($sharedWords) >= 1) {
                        if ($similarity > $bestScore) { $bestScore = $similarity; $bestMatch = $pt['model']; }
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

    public function calculateStringSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower(substr($str1, 0, 50));
        $str2 = strtolower(substr($str2, 0, 50));
        if ($str1 === $str2) return 1.0;
        $jaro = $this->jaroDistance($str1, $str2);
        return max($this->jaroWinklerDistance($jaro, $str1, $str2), $jaro);
    }

    private function jaroDistance(string $str1, string $str2): float
    {
        if ($str1 === $str2) return 1.0;
        $len1 = strlen($str1); $len2 = strlen($str2);
        $matchDistance = (int)(max($len1, $len2) / 2) - 1;
        $matches1 = $matches2 = [];
        $matchCount = 0;

        for ($i = 0; $i < $len1; $i++) {
            $start = max(0, $i - $matchDistance);
            $end = min($i + $matchDistance + 1, $len2);
            for ($j = $start; $j < $end; $j++) {
                if (isset($str2[$j]) && $str1[$i] === $str2[$j] && !in_array($j, $matches2)) {
                    $matches1[] = $i; $matches2[] = $j; $matchCount++; break;
                }
            }
        }
        if ($matchCount == 0) return 0.0;

        $transpositions = 0;
        for ($i = 0; $i < $matchCount; $i++) {
            if ($matches1[$i] !== $matches2[$i]) $transpositions++;
        }

        return ($matchCount / $len1 + $matchCount / $len2 + ($matchCount - $transpositions / 2) / $matchCount) / 3;
    }

    private function jaroWinklerDistance(float $jaro, string $str1, string $str2): float
    {
        $prefixLength = 0;
        $maxPrefix = min(4, min(strlen($str1), strlen($str2)));
        for ($i = 0; $i < $maxPrefix; $i++) {
            if ($str1[$i] === $str2[$i]) $prefixLength++; else break;
        }
        return $jaro + ($prefixLength * 0.1 * (1 - $jaro));
    }

    private function stripRoutePrefixes(string $name, array $prefixes): string
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
}
