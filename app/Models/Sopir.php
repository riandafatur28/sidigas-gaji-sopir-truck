<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sopir extends Model
{
    use HasFactory;

    protected $table = 'sopirs';

    protected $fillable = [
        'kode_sopir',
        'nama',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sopir) {
            if (empty($sopir->kode_sopir)) {
                $lastSopir = static::orderBy('id', 'desc')->first();

                if ($lastSopir) {
                    $lastNumber = (int) substr($lastSopir->kode_sopir, 4);
                    $newNumber = $lastNumber + 1;
                } else {
                    $newNumber = 1;
                }

                $sopir->kode_sopir = 'SPR-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function ritase()
    {
        return $this->hasMany(Ritase::class, 'kode_sopir', 'kode_sopir');
    }

    public function penggajian()
    {
        return $this->hasMany(Penggajian::class, 'kode_sopir', 'kode_sopir');
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeNonaktif($query)
    {
        return $query->where('status', 'nonaktif');
    }

    /**
     * Sync status: sopir dgn ritase di periode aktif → aktif, sisanya → nonaktif.
     */
    public static function syncActiveStatus(): void
    {
        $activePeriode = Periode::where('status', 'aktif')->first();
        if (!$activePeriode) return;

        $activeSopirs = Ritase::where('periode_id', $activePeriode->id)
            ->whereNotNull('kode_sopir')
            ->distinct()
            ->pluck('kode_sopir');

        static::whereIn('kode_sopir', $activeSopirs)
            ->where('status', '!=', 'aktif')
            ->update(['status' => 'aktif']);

    }
}
