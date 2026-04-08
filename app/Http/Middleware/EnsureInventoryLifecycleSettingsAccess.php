<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInventoryLifecycleSettingsAccess
{
    /** @var list<string> */
    public const ALLOWED_USER_GROUPS = ['Director', 'Inventory Manager'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->user_group ?? '', self::ALLOWED_USER_GROUPS, true)) {
            abort(403);
        }

        return $next($request);
    }
}
