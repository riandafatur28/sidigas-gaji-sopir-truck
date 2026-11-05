<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RecordsAuthor;
use App\Traits\HasUniqueKode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ritase extends Model
{
    use HasFactory, HasUniqueKode, RecordsAuthor, SoftDeletes;

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
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'dt' => 'decimal:2',
        'upah_sopir' => 'decimal:2',
        'nominal_kompensasi' => 'decimal:2',
        'is_lembur' => 'boolean',
        'upah_lembur' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Ritase $ritase): void {
            if (empty($ritase->kode_ritase)) {
                $ritase->kode_ritase = $ritase->generateUniqueKode('RIT');
            }
        });
    }

    protected static function booted(): void
    {
        // Mirror FK `validasi_bukti.kode_ritase` -> nullOnDelete() pada DB.
        // Karena baris tidak lagi benar-benar dihapus, tautan harus dilepas manual
        // supaya bukti validasi tidak menunjuk ke ritase yang sudah dibuang.
        static::deleting(function (Ritase $ritase): void {
            if ($ritase->isForceDeleting() || empty($ritase->kode_ritase)) {
                return;
            }

            ValidasiBukti::where('kode_ritase', $ritase->kode_ritase)
                ->update(['kode_ritase' => null]);
        });
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class, 'periode_id');
    }

    public function sopir(): BelongsTo
    {
        return $this->belongsTo(Sopir::class, 'kode_sopir', 'kode_sopir');
    }

    public function tujuan(): BelongsTo
    {
        return $this->belongsTo(Tujuan::class, 'kode_tujuan', 'kode_tujuan');
    }

    // RELATIONSHIP: satu ritase bisa dirujuk oleh baris bukti validasi (kode_ritase)
    public function validasiBuktis(): HasMany
    {
        return $this->hasMany(ValidasiBukti::class, 'kode_ritase', 'kode_ritase');
    }
}
