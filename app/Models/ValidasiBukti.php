<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValidasiBukti extends Model
{
    use HasFactory;

    protected $table = 'validasi_bukti';

    protected $fillable = [
        'kode_sopir',
        'nama_sopir',
        'sopir_baru',
        'kode_tujuan',
        'nama_tujuan',
        'tujuan_baru',
        'foto',
        'latitude',
        'longitude',
        'lokasi',
        'waktu_foto',
        'tanggal',
        'periode_id',
        'catatan',
        'status',
        'catatan_mitra',
    ];

    protected $casts = [
        'waktu_foto' => 'datetime',
        'tanggal' => 'date',
        'sopir_baru' => 'boolean',
        'tujuan_baru' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function sopir()
    {
        return $this->belongsTo(Sopir::class, 'kode_sopir', 'kode_sopir');
    }

    public function tujuan()
    {
        return $this->belongsTo(Tujuan::class, 'kode_tujuan', 'kode_tujuan');
    }

    public function periode()
    {
        return $this->belongsTo(Periode::class, 'periode_id');
    }
}
