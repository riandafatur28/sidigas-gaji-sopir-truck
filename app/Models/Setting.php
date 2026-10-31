<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Penyimpanan setting bisnis yang harus tahan terhadap cache:clear.
 * Database adalah sumber kebenaran; cache hanya read-through.
 * Nilai disimpan sebagai JSON agar int/bool/array bisa round-trip.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $cacheKey = 'settings:'.$key;
        $hit = Cache::get($cacheKey);
        if (is_array($hit) && array_key_exists('v', $hit)) {
            return $hit['v'];
        }

        try {
            $row = static::query()->where('key', $key)->first();
        } catch (Throwable) {
            // Tabel belum ada (deploy belum migrate): pakai default.
            return $default;
        }

        if (!$row) {
            return $default;
        }

        $value = self::decode($row->value, $default);
        Cache::forever($cacheKey, ['v' => $value]);

        return $value;
    }

    public static function setValue(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
        Cache::forget('settings:'.$key);
    }

    private static function decode(mixed $raw, mixed $default): mixed
    {
        if (!is_string($raw)) {
            return $raw;
        }
        $decoded = json_decode($raw, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
    }
}
