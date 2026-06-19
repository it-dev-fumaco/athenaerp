<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLoginActivityLogAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $allowed = config('login_activity.allowed_user_groups', []);

        if (! is_array($allowed) || $allowed === []) {
            abort(403);
        }

        if (! $user || ! in_array($user->user_group ?? '', $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}
