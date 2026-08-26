<?php

namespace App\Observers;

use App\Models\Periode;
use App\Models\Sopir;
use App\Models\Tujuan;

class PeriodeObserver
{
    /**
     * Handle the Periode "created" event.
     */
    public function created(Periode $periode): void
    {
        Periode::syncActiveStatus();
    }

    /**
     * Handle the Periode "updated" event.
     */
    public function updated(Periode $periode): void
    {
        Periode::syncActiveStatus();
    }

    /**
     * Handle the Periode "deleted" event.
     */
    public function deleted(Periode $periode): void
    {
        Periode::syncActiveStatus();
    }
}
