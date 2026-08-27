<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUniqueKode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Periode extends Model
{
    use HasFactory, HasUniqueKode;

    protected $table = 'periodes';

    protected $fillable = [
        'kode_periode',
        'nama_periode',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Periode $periode): void {
            if (empty($periode->kode_periode)) {
                $periode->kode_periode = $periode->generateUniqueKode('PER');
            }
        });
    }

    public function ritase(): HasMany
    {
        return $this->hasMany(Ritase::class, 'periode_id');
    }

    public function gaji(): HasMany
    {
        return $this->hasMany(Penggajian::class, 'periode_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Sync status based on current date.
     */
    public static function syncActiveStatus(): ?self
    {
        $today = now()->startOfDay();

        $currentIds = static::where('tanggal_mulai', '<=', $today)
            ->where('tanggal_selesai', '>=', $today)
            ->pluck('id');

        if ($currentIds->isEmpty()) {
            static::where('status', 'aktif')->update(['status' => 'selesai']);
            return null;
        }

        $targetId = $currentIds->sort()->last();

        static::where('id', '!=', $targetId)
            ->where('status', 'aktif')
            ->update(['status' => 'selesai']);

        static::where('id', $targetId)
            ->where('status', '!=', 'aktif')
            ->update(['status' => 'aktif']);

        return static::find($targetId);
    }
}
