<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Services\DashboardService;

class RitaseObserver
{
    /**
     * Handle the Ritase "created" event.
     * Auto-activate sopir and tujuan when a new ritase is added.
     */
    public function created(Ritase $ritase): void
    {
        Sopir::where('kode_sopir', $ritase->kode_sopir)
            ->where('status', '!=', 'aktif')
            ->update(['status' => 'aktif']);

        if ($ritase->kode_tujuan) {
            Tujuan::where('kode_tujuan', $ritase->kode_tujuan)
                ->where('status', '!=', 'aktif')
                ->update(['status' => 'aktif']);
        }

        DashboardService::bumpVersion();
    }

    /**
     * Handle the Ritase "updated" event.
     * Auto-activate sopir and tujuan when a ritase is updated.
     */
    public function updated(Ritase $ritase): void
    {
        Sopir::where('kode_sopir', $ritase->kode_sopir)
            ->where('status', '!=', 'aktif')
            ->update(['status' => 'aktif']);

        if ($ritase->kode_tujuan) {
            Tujuan::where('kode_tujuan', $ritase->kode_tujuan)
                ->where('status', '!=', 'aktif')
                ->update(['status' => 'aktif']);
        }

        DashboardService::bumpVersion();
    }

    /**
     * Handle the Ritase "deleted" event.
     */
    public function deleted(Ritase $ritase): void
    {
        DashboardService::bumpVersion();
    }
}
