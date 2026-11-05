<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RecordsAuthor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PenggajianDetail extends Model
{
    use HasFactory, RecordsAuthor, SoftDeletes;

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
        'tol_per_rit',
        'total_tol',
        'subtotal',
        'created_by',
    ];

        protected $casts = [
        'jumlah_rit' => 'integer',
        'solar_per_rit' => 'decimal:2',
        'upah_per_rit' => 'decimal:2',
        'total_solar' => 'decimal:2',
        'total_upah' => 'decimal:2',
        'sewa_dt' => 'decimal:2',
        'tol_per_rit' => 'decimal:2',
        'total_tol' => 'decimal:2',
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
