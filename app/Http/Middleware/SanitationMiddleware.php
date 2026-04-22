<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // OAuth query params (code, state) must not be passed through strip_tags — it can corrupt tokens.
        if ($request->is('auth/microsoft/*')) {
            return $next($request);
        }

        $input = $request->all();

        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });

        $request->merge($input);

        return $next($request);
    }
}
