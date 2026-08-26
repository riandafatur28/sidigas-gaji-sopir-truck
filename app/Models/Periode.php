<?php

namespace App\Models;

use App\Traits\HasUniqueKode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Periode extends Model
{
    use HasFactory, HasUniqueKode;

    protected $table = 'periodes';

    protected $fillable = [
        'kode_periode',
        'nama_periode',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($periode) {
            if (empty($periode->kode_periode)) {
                $periode->kode_periode = $periode->generateUniqueKode('PER');
            }
        });
    }

    // RELATIONSHIP: Periode has many Ritase records
    public function ritase()
    {
        return $this->hasMany(Ritase::class, 'periode_id');
    }

    // RELATIONSHIP: Periode has many Penggajian records
    public function gaji()
    {
        return $this->hasMany(Penggajian::class, 'periode_id');
    }

    // SCOPE: Only active periodes
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    // Sync status based on current date — call on every request that needs 'active periode'
    public static function syncActiveStatus(): ?self
    {
        $today = now()->startOfDay();

        $currentIds = static::where('tanggal_mulai', '<=', $today)
            ->where('tanggal_selesai', '>=', $today)
            ->pluck('id');

        if ($currentIds->isEmpty()) {
            // No period spans today → deactivate all
            static::where('status', 'aktif')->update(['status' => 'selesai']);
            return null;
        }

        // If multiple spans today (shouldn't happen), take the last one
        $targetId = $currentIds->sort()->last();

        // Deactivate others
        static::where('id', '!=', $targetId)
            ->where('status', 'aktif')
            ->update(['status' => 'selesai']);

        // Activate target
        static::where('id', $targetId)
            ->where('status', '!=', 'aktif')
            ->update(['status' => 'aktif']);

        return static::find($targetId);
    }
}
