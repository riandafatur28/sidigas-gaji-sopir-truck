<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ritase extends Model
{
    use HasFactory;

    protected $table = 'ritases';

    protected $fillable = [
        'kode_ritase',
        'periode_id',
        'kode_sopir',
        'kode_tujuan',
        'tanggal',
        'waktu',
        'kabupaten',
        'status',
        'dt',
        'upah_sopir',
        'nominal_kompensasi',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'dt' => 'decimal:2',
        'upah_sopir' => 'decimal:2',
        'nominal_kompensasi' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ritase) {
            if (empty($ritase->kode_ritase)) {
                $lastRitase = static::orderBy('id', 'desc')->first();

                if ($lastRitase) {
                    $lastNumber = (int) substr($lastRitase->kode_ritase, 4);
                    $newNumber = $lastNumber + 1;
                } else {
                    $newNumber = 1;
                }

                $ritase->kode_ritase = 'RIT-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function periode()
    {
        return $this->belongsTo(Periode::class, 'periode_id');
    }

    public function sopir()
    {
        return $this->belongsTo(Sopir::class, 'kode_sopir', 'kode_sopir');
    }

    public function tujuan()
    {
        return $this->belongsTo(Tujuan::class, 'kode_tujuan', 'kode_tujuan');
    }
}
