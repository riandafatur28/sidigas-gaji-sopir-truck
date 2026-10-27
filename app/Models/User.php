<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
}
