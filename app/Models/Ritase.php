<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUniqueKode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Ritase $ritase): void {
            if (empty($ritase->kode_ritase)) {
                $ritase->kode_ritase = $ritase->generateUniqueKode('RIT');
            }
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
}
