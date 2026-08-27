<?php

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
        $this->postProcessBongkar($result);
        $this->postProcessGagal($result);

        return $result;
    }

    private function extractDate(array $lines): array
    {
        foreach ($lines as $idx => $line) {
            $d = $this->parseDateLine($line);
            if ($d) return [$d, $idx];

            if (preg_match_all('/(?<!\d)(\d{1,2})\s+(\d{1,2})\s+(\d{2,4})(?!\d)/', $line, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                    $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                    $year = strlen($m[3]) === 2 ? '20'.$m[3] : $m[3];
                    if (checkdate((int)$month, (int)$day, (int)$year)) {
                        return ["{$year}-{$month}-{$day}", $idx];
                    }
                }
            }
        }
        return [null, -1];
    }

    private function parseDateLine(string $line): ?string
    {
        if (preg_match('/^(\d{1,2})\s+(\d{1,2})\s+(\d{2,4})/', $line, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $year = strlen($m[3]) === 2 ? '20'.$m[3] : $m[3];
            if (checkdate((int)$month, (int)$day, (int)$year)) return "{$year}-{$month}-{$day}";
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

        for ($i = 1; $i < count($lines); $i++) {
            $line = $lines[$i];

            if (str_starts_with(strtolower($line), 'bbm') || preg_match('/^\d+\s+sopir$/i', $line)) continue;

            if (!$this->looksLikeDriverName($line)) {
                if ($currentPackage !== null) $result['packages'][] = $currentPackage;
                $currentPackage = $this->parseRouteLine($line);
                continue;
            }

            if (preg_match('/^\d+\.(.*)$/', $line, $m)) {
                if ($currentPackage === null) {
                    $currentPackage = ['route_name' => 'Unknown Route', 'drivers' => []];
                }
                $driverName = $this->cleanDriverName(trim($m[1]));
                if (!empty($driverName)) {
                    $lower = strtolower($driverName);
                    if (!in_array($lower, array_map('strtolower', $currentPackage['drivers']), true)) {
                        $currentPackage['drivers'][] = $driverName;
                    }
                }
            }
        }

        if ($currentPackage !== null) $result['packages'][] = $currentPackage;
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
            $key = $pkg['route_name'];
            if (!isset($merged[$key])) { $merged[$key] = $pkg; continue; }
            foreach ($pkg['drivers'] as $d) {
                if (!in_array(strtolower($d), array_map('strtolower', $merged[$key]['drivers']))) {
                    $merged[$key]['drivers'][] = $d;
                }
            }
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
        $name = preg_replace('/^(mbah|pak|bu|ira)\s*/i', '', $name);
        $name = str_replace(['√', '✔', '✓', '🙏', '🙌'], '', $name);
        return trim(preg_replace('/\s+/u', ' ', $name));
    }

    public function looksLikeDriverName(string $line): bool
    {
        return preg_match('/^\d+\./', $line);
    }
}
