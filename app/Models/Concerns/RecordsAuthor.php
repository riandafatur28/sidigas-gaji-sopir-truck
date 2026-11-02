<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Mengisi created_by dari user yang sedang login saat baris dibuat.
 *
 * Nullable: data lama & proses non-login (console/queue/parser) tetap null,
 * jadi tidak ada perilaku lama yang berubah.
 */
trait RecordsAuthor
{
    public static function bootRecordsAuthor(): void
    {
        static::creating(function (Model $model): void {
            if ($model->created_by === null && Auth::hasUser()) {
                $model->created_by = Auth::id();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
