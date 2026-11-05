<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RecordsAuthor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Penggajian extends Model
{
    use HasFactory, RecordsAuthor, SoftDeletes;

    protected $table = 'penggajian';

        protected $fillable = [
        'kode_sopir',
        'periode_id',
        'tanggal',
        'uang_solar',
        'upah_sopir',
        'dt',
        'tol',
        'upah_lembur',
        'total',
        'kompensasi_gagal',
        'created_by',
    ];

        protected $casts = [
        'tanggal' => 'date',
        'uang_solar' => 'decimal:2',
        'upah_sopir' => 'decimal:2',
        'dt' => 'decimal:2',
        'tol' => 'decimal:2',
        'upah_lembur' => 'decimal:2',
        'total' => 'decimal:2',
        'kompensasi_gagal' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Mirror FK `penggajian_details.penggajian_id` -> onDelete('cascade').
        // UI menjanjikan "hapus data gaji beserta rinciannya", jadi rincian
        // harus ikut di-soft delete agar tidak menjadi baris yatim.
        static::deleting(function (Penggajian $penggajian): void {
            if ($penggajian->isForceDeleting()) {
                return;
            }

            $penggajian->details()->delete();
        });
    }

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
