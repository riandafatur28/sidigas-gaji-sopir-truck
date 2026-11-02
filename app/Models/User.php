<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isSuperadmin(): bool
    {
        return ($this->role ?? 'admin') === 'superadmin';
    }

    /**
     * Mitra saat ini diidentifikasi dari email (sementara). Bisa diganti
     * dengan kolom role tersendiri nantinya.
     */
    public function isMitra(): bool
    {
        return in_array(strtolower((string) $this->email), array_map('strtolower', (array) config('app.mitra_emails', [])), true);
    }

    public function canManageSettings(): bool
    {
        return $this->isSuperadmin() || $this->isMitra();
    }

    // RELATIONSHIP: user sebagai pencatat (created_by) di tabel bisnis.
    // Artinya "dibuat oleh", bukan "dimiliki oleh".
    public function sopirs(): HasMany
    {
        return $this->hasMany(Sopir::class, 'created_by');
    }

    public function tujuans(): HasMany
    {
        return $this->hasMany(Tujuan::class, 'created_by');
    }

    public function periodes(): HasMany
    {
        return $this->hasMany(Periode::class, 'created_by');
    }

    public function ritases(): HasMany
    {
        return $this->hasMany(Ritase::class, 'created_by');
    }

    public function penggajians(): HasMany
    {
        return $this->hasMany(Penggajian::class, 'created_by');
    }

    public function penggajianDetails(): HasMany
    {
        return $this->hasMany(PenggajianDetail::class, 'created_by');
    }

    public function validasiBuktis(): HasMany
    {
        return $this->hasMany(ValidasiBukti::class, 'created_by');
    }

    public function otps(): HasMany
    {
        return $this->hasMany(Otp::class);
    }
}
