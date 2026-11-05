<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RecordsAuthor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ValidasiBukti extends Model
{
    use HasFactory, RecordsAuthor, SoftDeletes;

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
        'kode_ritase',
        'created_by',
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

    // RELATIONSHIP: bukti validasi menjadi dasar pembuatan satu ritase (kode_ritase)
    public function ritase()
    {
        return $this->belongsTo(Ritase::class, 'kode_ritase', 'kode_ritase');
    }
}
