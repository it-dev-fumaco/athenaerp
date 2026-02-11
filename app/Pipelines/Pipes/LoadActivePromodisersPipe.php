<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;
use Illuminate\Support\Facades\DB;

class LoadActivePromodisersPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        if (! ($passable->shouldSendEmail ?? true)) {
            return $next($passable);
        }

        $passable->activePromodisers = DB::table('tabWarehouse Users as wu')
            ->join('tabAssigned Consignment Warehouse as acw', 'acw.parent', 'wu.frappe_userid')
            ->join('tabWarehouse as w', 'w.warehouse_name', 'acw.warehouse_name')
            ->where('wu.enabled', 1)
            ->where('wu.user_group', 'Promodiser')
            ->where('w.disabled', 0)
            ->select('wu.full_name', 'wu.wh_user', 'acw.warehouse')
            ->get();

        return $next($passable);
    }
}
