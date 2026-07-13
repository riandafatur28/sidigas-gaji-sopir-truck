<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenggajianDetail extends Model
{
    use HasFactory;

    protected $table = 'penggajian_details';

    protected $fillable = [
        'penggajian_id',
        'kode_tujuan',
        'jumlah_rit',
        'solar_per_rit',
        'upah_per_rit',
        'total_solar',
        'total_upah',
        'sewa_dt',
        'subtotal',
    ];

    protected $casts = [
        'jumlah_rit' => 'integer',
        'solar_per_rit' => 'decimal:2',
        'upah_per_rit' => 'decimal:2',
        'total_solar' => 'decimal:2',
        'total_upah' => 'decimal:2',
        'sewa_dt' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function penggajian()
    {
        return $this->belongsTo(Penggajian::class);
    }

    public function tujuan()
    {
        return $this->belongsTo(Tujuan::class, 'kode_tujuan', 'kode_tujuan');
    }
}
