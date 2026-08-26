<?php

namespace App\Models;

use App\Traits\HasUniqueKode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ritase extends Model
{
    use HasFactory, HasUniqueKode;

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
        'is_lembur',
        'upah_lembur',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'dt' => 'decimal:2',
        'upah_sopir' => 'decimal:2',
        'nominal_kompensasi' => 'decimal:2',
        'is_lembur' => 'boolean',
        'upah_lembur' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ritase) {
            if (empty($ritase->kode_ritase)) {
                $ritase->kode_ritase = $ritase->generateUniqueKode('RIT');
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
