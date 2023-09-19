<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use DB;

class CheckConnectionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            DB::getPdo();
            return $next($request);
        } catch (\Throwable $th) {
            if($request->ajax()){
                return response()->json([
                    'success' => 0,
                    'status' => 0,
                    'message' => 'No Connection'
                ]);
            }

            return redirect()->back()->with('error', 'No Connection');
        }
    }
}
