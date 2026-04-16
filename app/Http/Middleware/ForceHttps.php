<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceHttps
{
    /**
     * Redirect HTTP to HTTPS when SSL enforcement is enabled (live).
     */
    public function handle(Request $request, Closure $next)
    {
        if (! config('app.force_https')) {
            return $next($request);
        }

        if ($request->secure()) {
            return $next($request);
        }

        return redirect()->secure($request->getRequestUri(), 301);
    }
}
