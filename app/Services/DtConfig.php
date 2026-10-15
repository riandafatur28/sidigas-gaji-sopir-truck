<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Single access point for DT (uang transport) rules.
 *
 * Values are admin-configurable via Pengaturan and stored in cache,
 * with config/dt.php as fallback default.
 */
class DtConfig
{
    public static function nominal(): int
    {
        return (int) Cache::get('dt_nominal', config('dt.value', 330000));
    }

    /**
     * @return string[]
     */
    public static function singleRegencies(): array
    {
        $stored = Cache::get('dt_single_regencies');

        if (is_array($stored)) {
            return array_values($stored);
        }

        return config('dt.single_dt_regencies', []);
    }

    /**
     * @return string[]
     */
    public static function allKabupaten(): array
    {
        $names = array_values(RitaseCreator::kabupatenKeywords());

        foreach (self::singleRegencies() as $kab) {
            $names[] = $kab;
        }

        $names = array_unique(array_map(fn($n) => trim((string) $n, " \t\n\r\0\x0B"), $names));
        $names = array_filter($names, fn($n) => $n !== '' && strtolower($n) !== 'lainnya');
        sort($names);

        return array_values($names);
    }
}
