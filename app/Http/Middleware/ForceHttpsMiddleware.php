<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceHttpsMiddleware
{
    /**
     * Redirect HTTP requests to HTTPS when FORCE_HTTPS=true.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! env('FORCE_HTTPS', false) || $request->secure()) {
            return $next($request);
        }

        $target = $request->getUri();
        $target = preg_replace('/^http:/i', 'https:', $target, 1);

        return redirect()->to($target, 301);
    }
}

