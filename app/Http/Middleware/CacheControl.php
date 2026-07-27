<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CacheControl
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // AJAX/API: no cache
        if ($request->expectsJson() || $request->ajax()) {
            $response->header('Cache-Control', 'no-cache, private');
            return $response;
        }

        // HTML: no-cache to prevent stale browser cache
        $response->header('Cache-Control', 'no-cache, private');

        return $response;
    }
}
