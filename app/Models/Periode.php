<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Periode extends Model
{
    use HasFactory;

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
                $lastPeriode = static::orderBy('id', 'desc')->first();
                $newNumber = $lastPeriode ? (int) substr($lastPeriode->kode_periode, 4) + 1 : 1;
                $periode->kode_periode = 'PER-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    // ✅ RELATIONSHIP: Periode memiliki banyak Ritase (nama: ritase - singular)
    public function ritase()
    {
        return $this->hasMany(Ritase::class, 'periode_id');
    }

    // ✅ RELATIONSHIP: Periode memiliki banyak Gaji
    public function gaji()
    {
        return $this->hasMany(Penggajian::class, 'periode_id');
    }

    // ✅ SCOPE: Hanya periode aktif
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    // ✅ Sync status based on current date — panggil tiap request yg butuh 'periode aktif'
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
