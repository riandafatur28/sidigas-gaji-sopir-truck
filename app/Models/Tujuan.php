<?php

namespace App\Models;

use App\Traits\HasUniqueKode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tujuan extends Model
{
    use HasFactory, HasUniqueKode;

    protected $table = 'tujuans';

    protected $fillable = [
        'kode_tujuan',
        'nama',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tujuan) {
            if (empty($tujuan->kode_tujuan)) {
                $tujuan->kode_tujuan = $tujuan->generateUniqueKode('TUJ');
            }
        });
    }

    // RELATIONSHIP: Tujuan has many Ritase records
    public function ritase()
    {
        return $this->hasMany(Ritase::class, 'kode_tujuan', 'kode_tujuan');
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
     * Sync status: tujuan with ritase in active periode -> active, others -> inactive.
     * If no active periode -> all inactive.
     */
    public static function syncActiveStatus(): void
    {
        $activePeriode = Periode::where('status', 'aktif')->first();

        if (!$activePeriode) {
            static::where('status', 'aktif')->update(['status' => 'nonaktif']);
            return;
        }

        $activeTujuans = Ritase::where('periode_id', $activePeriode->id)
            ->whereNotNull('kode_tujuan')
            ->distinct()
            ->pluck('kode_tujuan');

        if ($activeTujuans->isNotEmpty()) {
            static::whereIn('kode_tujuan', $activeTujuans)
                ->where('status', '!=', 'aktif')
                ->update(['status' => 'aktif']);
        }

        static::whereNotIn('kode_tujuan', $activeTujuans)
            ->where('status', 'aktif')
            ->update(['status' => 'nonaktif']);
    }
}
