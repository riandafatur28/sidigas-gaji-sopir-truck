<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Parse raw text message into structured ritase data.
 * Handles date extraction, route detection, and driver line parsing.
 */
class RitaseTextParser
{
    public function parse(string $text): array
    {
        $result = ['date' => null, 'packages' => [], 'header_kode_tujuan' => null];

        $lines = preg_split('/\r\n|\n|\r/', $text);
        $lines = array_map('trim', array_filter($lines, fn($l) => $l !== ''));
        $lines = array_values($lines);

        if (empty($lines)) return $result;

        [$date, $dateLineIdx] = $this->extractDate($lines);
        if ($date) $result['date'] = $date;

        $this->extractHeaderRoute($lines, $dateLineIdx, $result);

        $this->parseRouteAndDriverLines($lines, $result);

        $this->mergePackages($result);
        // Lembur is a marker on drivers of an already-parsed route, never a
        // new route. Resolve it after merging so "Paket candirejo" and
        // "paket Candirejo" match the same base package.
        $this->resolveLemburSections($result);
        $this->postProcessBongkar($result);
        $this->postProcessGagal($result);

        return $result;
    }

    private function extractDate(array $lines): array
    {
        $allText = implode(' ', $lines);
        $dayHint = $this->extractDayHint($allText);

        foreach ($lines as $idx => $line) {
            $d = $this->parseDateLine($line, $dayHint);
            if ($d) return [$d, $idx];

            if (preg_match_all('/(?<!\d)(\d{1,2})\s+(\d{1,2})\s+(\d{2,4})(?!\d)/', $line, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                    $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                    $year = $this->resolveTwoDigitYear($m[3], $day, $month, $dayHint);
                    if ($year && checkdate((int)$month, (int)$day, (int)$year)) {
                        return ["{$year}-{$month}-{$day}", $idx];
                    }
                }
            }
        }
        return [null, -1];
    }

    private function parseDateLine(string $line, ?string $dayHint = null): ?string
    {
        if (preg_match('/^(\d{1,2})\s+(\d{1,2})\s+(\d{2,4})/', $line, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $year = $this->resolveTwoDigitYear($m[3], $day, $month, $dayHint);
            if ($year && checkdate((int)$month, (int)$day, (int)$year)) return "{$year}-{$month}-{$day}";
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $line, $m)) {
            if (checkdate((int)$m[2], (int)$m[3], (int)$m[1])) return $line;
        }
        return null;
    }

    private function extractHeaderRoute(array $lines, int $dateLineIdx, array &$result): void
    {
        if ($dateLineIdx <= 0) return;
        $headerLine = $lines[0];
        if (str_starts_with(strtolower($headerLine), 'bbm')) return;

        $matcher = new RitaseFuzzyMatcher();
        $headerMatches = $matcher->matchRoutes([$headerLine]);
        if (!empty($headerMatches) && $headerMatches[0]['matched']) {
            $result['header_kode_tujuan'] = $headerMatches[0]['tujuan']->kode_tujuan;
        }
        if (str_contains(strtolower($headerLine), 'malam')) {
            $result['header_waktu'] = 'malam';
        }
    }

    private function parseRouteAndDriverLines(array $lines, array &$result): void
    {
        $currentPackage = null;
        // A lembur section is NOT a package. It is collected separately and
        // resolved onto its base package by resolveLemburSections().
        $currentLembur = null;
        $result['lembur_sections'] = [];

        $flushPackage = function () use (&$currentPackage, &$result) {
            if ($currentPackage !== null) $result['packages'][] = $currentPackage;
            $currentPackage = null;
        };
        $flushLembur = function () use (&$currentLembur, &$result) {
            if ($currentLembur !== null) $result['lembur_sections'][] = $currentLembur;
            $currentLembur = null;
        };

        for ($i = 1; $i < count($lines); $i++) {
            $line = $lines[$i];

            if (str_starts_with(strtolower($line), 'bbm') || preg_match('/^\d+\s+sopir$/i', $line)) continue;

            if ($this->isLemburLine($line)) {
                $flushPackage();
                $flushLembur();
                $currentLembur = ['route_name' => $this->parseLemburRoute($line), 'drivers' => []];
                continue;
            }

            if (!$this->looksLikeDriverName($line)) {
                $flushPackage();
                $flushLembur();
                $currentPackage = $this->parseRouteLine($line);
                continue;
            }

            if (preg_match('/^\d+\.(.*)$/', $line, $m)) {
                $driverName = $this->cleanDriverName(trim($m[1]));
                if (empty($driverName)) continue;
                $lower = strtolower($driverName);
                if ($currentLembur !== null) {
                    if (!in_array($lower, array_map('strtolower', $currentLembur['drivers']), true)) {
                        $currentLembur['drivers'][] = $driverName;
                    }
                    continue;
                }
                if ($currentPackage === null) {
                    $currentPackage = ['route_name' => 'Unknown Route', 'drivers' => []];
                }
                if (!in_array($lower, array_map('strtolower', $currentPackage['drivers']), true)) {
                    $currentPackage['drivers'][] = $driverName;
                }
            }
        }

        $flushPackage();
        $flushLembur();
    }

    private function isLemburLine(string $line): bool
    {
        return (bool) preg_match('/^lembur\b/i', trim($line));
    }

    private function parseLemburRoute(string $line): string
    {
        $route = trim((string) preg_replace('/^lembur\s*:?\s*/i', '', trim($line)));
        $route = trim((string) preg_replace('/^Paket\s*\d+\s*:\s*/i', '', $route));
        return $route;
    }

    /**
     * Normalised route key for matching a lembur section to its base package
     * and for merging duplicate packages: case-insensitive, whitespace
     * collapsed, leading route keywords stripped.
     */
    private function normalizeRouteKey(string $route): string
    {
        $key = strtolower(trim((string) preg_replace('/\s+/u', ' ', $route)));
        $keywords = ['paket cmm', 'paket', 'patching', 'overlay', 'cmm', 'kormuling', 'rekon', 'bongkar'];
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($keywords as $kw) {
                if (str_starts_with($key, $kw . ' ')) {
                    $key = trim(substr($key, strlen($kw) + 1));
                    $changed = true;
                } elseif ($key === $kw) {
                    $key = '';
                    $changed = true;
                }
            }
        }
        return $key;
    }

    private function resolveLemburSections(array &$result): void
    {
        $unmatched = [];
        foreach ($result['lembur_sections'] ?? [] as $section) {
            $targetKey = $this->normalizeRouteKey($section['route_name']);
            $foundIdx = null;
            if ($targetKey === '') {
                // "lembur" with no route: it marks the previous package.
                if (!empty($result['packages'])) $foundIdx = count($result['packages']) - 1;
            } else {
                foreach ($result['packages'] as $idx => $pkg) {
                    if ($this->normalizeRouteKey($pkg['route_name']) === $targetKey) {
                        $foundIdx = $idx;
                        break;
                    }
                }
            }
            if ($foundIdx === null) {
                $unmatched[] = $section;
                continue;
            }
            $existing = $result['packages'][$foundIdx]['lembur_drivers'] ?? [];
            foreach ($section['drivers'] as $d) {
                if (!in_array(strtolower($d), array_map('strtolower', $existing), true)) {
                    $existing[] = $d;
                }
            }
            $result['packages'][$foundIdx]['lembur_drivers'] = $existing;
            $result['packages'][$foundIdx]['has_lembur'] = true;
        }
        $result['lembur_unmatched'] = $unmatched;
        unset($result['lembur_sections']);
    }

    private function parseRouteLine(string $line): array
    {
        $routeKeywords = ['patching', 'paket', 'overlay', 'cmm', 'kormuling', 'rekon', 'bongkar'];
        $implicitDrivers = [];
        $isRitKe2 = false;
        $routeName = preg_replace('/^Paket\s*\d+\s*:\s*/i', '', trim($line));

        if (preg_match('/^rit\s+ke\s*2|^rit\s+kedua/i', $routeName, $rm)) {
            $isRitKe2 = true;
            $routeName = trim(substr($routeName, strlen($rm[0])));
        }

        $lowerLine = strtolower($routeName);
        $startsWithKw = false;
        foreach ($routeKeywords as $kw) {
            if (str_starts_with($lowerLine, $kw.' ') || $lowerLine === $kw) { $startsWithKw = true; break; }
        }

        if (!$startsWithKw) {
            foreach ($routeKeywords as $kw) {
                $pos = strpos($lowerLine, ' '.$kw.' ');
                if ($pos !== false) { $implicitDrivers = [trim(substr($line, 0, $pos + 1))]; $routeName = trim(substr($line, $pos + 1)); break; }
                if (str_ends_with($lowerLine, ' '.$kw)) { $implicitDrivers = [trim(substr($line, 0, strlen($line) - strlen($kw)))]; $routeName = trim(substr($line, strlen($line) - strlen($kw))); break; }
            }
        }

        return ['route_name' => $routeName, 'drivers' => $implicitDrivers, 'is_rit_ke_2' => $isRitKe2];
    }

    private function mergePackages(array &$result): void
    {
        $merged = [];
        foreach ($result['packages'] as $pkg) {
            // Normalised key: "Paket candirejo nganjuk" and
            // "paket Candirejo nganjuk" are the same route. A rit-ke-2
            // package is kept separate, it must not be merged into the
            // first rit or its second-rit flag would be lost.
            $key = $this->normalizeRouteKey($pkg['route_name']) . '|' . (!empty($pkg['is_rit_ke_2']) ? 'rit2' : 'normal');
            if (!isset($merged[$key])) { $merged[$key] = $pkg; continue; }
            foreach ($pkg['drivers'] as $d) {
                if (!in_array(strtolower($d), array_map('strtolower', $merged[$key]['drivers']))) {
                    $merged[$key]['drivers'][] = $d;
                }
            }
            if (!empty($pkg['is_rit_ke_2'])) $merged[$key]['is_rit_ke_2'] = true;
        }
        $result['packages'] = array_values($merged);
    }

    private function postProcessBongkar(array &$result): void
    {
        $lastNonBongkarPkg = null;
        $lastNonBongkarIdx = -1;
        foreach ($result['packages'] as $idx => $pkg) {
            $isBongkar = str_contains(strtolower($pkg['route_name']), 'bongkar');
            if ($isBongkar && $lastNonBongkarPkg !== null) {
                $result['packages'][$idx]['is_bongkar'] = true;
                $result['packages'][$idx]['is_rit_ke_2'] = true;
                $result['packages'][$idx]['bongkar_source_idx'] = $lastNonBongkarIdx;
                $result['packages'][$idx]['bongkar_source_route'] = $lastNonBongkarPkg['route_name'];
                if (preg_match('/\bke\s+(.+)$/i', $pkg['route_name'], $m)) {
                    $result['packages'][$idx]['route_name'] = trim($m[1]);
                }
            }
            if (!$isBongkar) { $lastNonBongkarPkg = $pkg; $lastNonBongkarIdx = $idx; }
        }
    }

    private function postProcessGagal(array &$result): void
    {
        $gagalIdx = null;
        foreach ($result['packages'] as $idx => $pkg) {
            if (str_contains(strtolower($pkg['route_name']), 'gagal')) { $gagalIdx = $idx; }
            elseif ($gagalIdx !== null && empty($pkg['drivers'])) {
                $result['packages'][$gagalIdx]['gagal_route'] = $pkg['route_name'];
                unset($result['packages'][$idx]);
                $gagalIdx = null;
            } else { $gagalIdx = null; }
        }
        $result['packages'] = array_values($result['packages']);
    }

    public function cleanDriverName(string $name): string
    {
        $name = str_replace(['√', '✔', '✓', '🙏', '🙌'], '', $name);
        return trim(preg_replace('/\s+/u', ' ', $name));
    }

    private function extractDayHint(string $text): ?string
    {
        $dayMap = [
            'senin' => 1, 'sen' => 1,
            'selasa' => 2, 'sel' => 2,
            'rabu' => 3, 'rab' => 3,
            'kamis' => 4, 'kam' => 4,
            'jumat' => 5, 'jum' => 5, 'jum at' => 5, 'jmat' => 5,
            'sabtu' => 6, 'sab' => 6,
            'minggu' => 0, 'min' => 0,
        ];
        $lower = strtolower($text);
        foreach ($dayMap as $key => $num) {
            if (str_contains($lower, $key)) return (string) $num;
        }
        return null;
    }

    private function resolveTwoDigitYear(string $yearStr, string $day, string $month, ?string $dayHint): ?string
    {
        if (strlen($yearStr) !== 2) return $yearStr;

        $year20 = (int) ('20' . $yearStr);
        $year19 = (int) ('19' . $yearStr);
        $currentYear = (int) date('Y');

        if ($dayHint !== null) {
            $dow20 = date('w', mktime(0, 0, 0, (int)$month, (int)$day, $year20));
            $dow19 = date('w', mktime(0, 0, 0, (int)$month, (int)$day, $year19));
            $match20 = (string) $dow20 === (string) $dayHint;
            $match19 = (string) $dow19 === (string) $dayHint;
            $diff20 = abs($year20 - $currentYear);
            $diff19 = abs($year19 - $currentYear);

            // If both match, pick closer to today
            if ($match20 && $match19) {
                return $diff20 <= $diff19 ? (string) $year20 : (string) $year19;
            }
            // Only 20 matches and within reasonable window (<=50y) -> use it
            if ($match20 && $diff20 <= 50) return (string) $year20;
            if ($match19 && $diff19 <= 50) return (string) $year19;
            // If matched century is far (>50y) but the other is close, prefer close
            // (handles typo hari like "sabtu" vs "jumat" -> 1925 match but far, so keep 2025)
            if ($match20 || $match19) {
                // Day hint likely typo if close candidate doesn't match but far does
                // Prefer the close year anyway
                if ($diff20 <= 50) return (string) $year20;
                if ($diff19 <= 50) return (string) $year19;
            }
        }

        return (string) $year20;
    }

    public function looksLikeDriverName(string $line): bool
    {
        return (bool) preg_match('/^\d+\./', $line);
    }
}
