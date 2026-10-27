<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSettingsAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->canManageSettings()) {
            abort(403, 'Hanya superadmin atau mitra yang dapat mengakses halaman ini.');
        }

        return $next($request);
    }
}
