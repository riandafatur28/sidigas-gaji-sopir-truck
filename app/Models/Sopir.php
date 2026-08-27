<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUniqueKode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Sopir extends Model
{
    use HasFactory, HasUniqueKode;

    protected $table = 'sopirs';

    protected $fillable = [
        'kode_sopir',
        'nama',
        'status',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Sopir $sopir): void {
            if (empty($sopir->kode_sopir)) {
                $sopir->kode_sopir = $sopir->generateUniqueKode('SPR');
            }
        });
    }

    public function ritase(): HasMany
    {
        return $this->hasMany(Ritase::class, 'kode_sopir', 'kode_sopir');
    }

    public function penggajian(): HasMany
    {
        return $this->hasMany(Penggajian::class, 'kode_sopir', 'kode_sopir');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function scopeNonaktif(Builder $query): Builder
    {
        return $query->where('status', 'nonaktif');
    }

    /**
     * Sync status: sopir with ritase in active periode -> active, others -> inactive.
     */
    public static function syncActiveStatus(): void
    {
        $activePeriode = Periode::where('status', 'aktif')->first();

        if (!$activePeriode) {
            static::where('status', 'aktif')->update(['status' => 'nonaktif']);
            return;
        }

        $activeSopirs = Ritase::where('periode_id', $activePeriode->id)
            ->whereNotNull('kode_sopir')
            ->distinct()
            ->pluck('kode_sopir');

        if ($activeSopirs->isNotEmpty()) {
            static::whereIn('kode_sopir', $activeSopirs)
                ->where('status', '!=', 'aktif')
                ->update(['status' => 'aktif']);
        }

        static::whereNotIn('kode_sopir', $activeSopirs)
            ->where('status', 'aktif')
            ->update(['status' => 'nonaktif']);
    }
}
