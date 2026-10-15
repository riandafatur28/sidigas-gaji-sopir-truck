<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Periode;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    public function index(Request $request)
    {
        Periode::syncActiveStatus();
        Sopir::syncActiveStatus();
        Tujuan::syncActiveStatus();
        return view('dashboard.index', $this->dashboardService->getIndexData($request));
    }
}
