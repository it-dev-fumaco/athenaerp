<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInformationTechnologyAccess
{
    public const DEPARTMENT = 'Information Technology';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ($user->department ?? '') !== self::DEPARTMENT) {
            abort(403);
        }

        return $next($request);
    }
}
