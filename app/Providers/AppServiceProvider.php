<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Periode;
use App\Models\Ritase;
use App\Observers\PeriodeObserver;
use App\Observers\RitaseObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Periode::observe(PeriodeObserver::class);
        Ritase::observe(RitaseObserver::class);
    }
}
