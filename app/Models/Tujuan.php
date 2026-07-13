<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tujuan extends Model
{
    use HasFactory;

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
                $lastTujuan = static::orderBy('id', 'desc')->first();
                $newNumber = $lastTujuan ? (int) substr($lastTujuan->kode_tujuan, 4) + 1 : 1;
                $tujuan->kode_tujuan = 'TUJ-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    // ✅ RELATIONSHIP: Tujuan memiliki banyak Ritase
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
}
