<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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

            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = $modelClass::query();

            // Baris yang sudah di-soft-delete tetap menempati indeks UNIQUE,
            // jadi nomornya harus ikut diperhitungkan.
            if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
                $query->withTrashed();
            }

            // Urutkan numerik (bukan leksikal) agar SPR-1000 > SPR-999.
            $last = $query->orderByRaw("LENGTH({$column}) DESC, {$column} DESC")
                ->lockForUpdate()
                ->first();

            $newNumber = 1;
            if ($last) {
                $value = (string) $last->$column;
                if (preg_match('/(\d+)$/', $value, $matches) === 1) {
                    $newNumber = ((int) $matches[1]) + 1;
                }
            }

            return $prefix . '-' . str_pad((string) $newNumber, $padLength, '0', STR_PAD_LEFT);
        });
    }
}
