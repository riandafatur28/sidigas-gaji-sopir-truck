<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penggajian extends Model
{
    use HasFactory;

    protected $table = 'penggajian';

    protected $fillable = [
        'kode_sopir',
        'periode_id',
        'tanggal',
        'uang_solar',
        'upah_sopir',
        'dt',
        'total',
        'kompensasi_gagal',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'uang_solar' => 'decimal:2',
        'upah_sopir' => 'decimal:2',
        'dt' => 'decimal:2',
        'total' => 'decimal:2',
        'kompensasi_gagal' => 'decimal:2',
    ];

    public function periode()
    {
        return $this->belongsTo(Periode::class);
    }

    public function sopir()
    {
        return $this->belongsTo(Sopir::class, 'kode_sopir', 'kode_sopir');
    }

    public function details()
    {
        return $this->hasMany(PenggajianDetail::class);
    }
}
