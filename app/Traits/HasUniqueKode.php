<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Trait for generating unique kode with race condition protection.
 *
 * Usage: use HasUniqueKode in model, then call $this->generateUniqueKode()
 * in the boot() creating event.
 */
trait HasUniqueKode
{
    /**
     * Generate unique kode with SELECT FOR UPDATE to prevent race conditions.
     *
     * @param string $prefix  Kode prefix (e.g. 'SPR', 'RIT', 'PER', 'TUJ')
     * @param string $column  Database column name
     * @param int    $padLength  Zero-pad length (default: 3)
     * @return string  Generated kode
     */
    public function generateUniqueKode(string $prefix, string $column = '', int $padLength = 3): string
    {
        if ($column === '') {
            // Infer column from prefix
            $columnMap = [
                'SPR' => 'kode_sopir',
                'RIT' => 'kode_ritase',
                'PER' => 'kode_periode',
                'TUJ' => 'kode_tujuan',
            ];
            $column = $columnMap[$prefix] ?? 'kode';
        }

        /** @var Model $this */
        return DB::transaction(function () use ($prefix, $column, $padLength) {
            $modelClass = get_class($this);
            /** @var Model $last */
            $last = $modelClass::query()->orderBy('id', 'desc')->lockForUpdate()->first();

            if ($last) {
                $lastNumber = (int) substr($last->$column, strlen($prefix) + 1);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            return $prefix . '-' . str_pad((string) $newNumber, $padLength, '0', STR_PAD_LEFT);
        });
    }
}
