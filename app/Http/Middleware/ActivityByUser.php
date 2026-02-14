<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ActivityByUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $expiresAt = now()->addMinutes(10);  // keep online for 1 min
            Cache::put('user-is-online-'.Auth::user()->name, true, $expiresAt);
            // last seen
            DB::table('tabWarehouse Users')->where('name', Auth::user()->name)->update(['last_seen' => now()->format('Y-m-d H:i:s')]);
        }

        return $next($request);
    }
}
