<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;
use Illuminate\Support\Facades\DB;

class ExpireOldReservationsPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        DB::table('tabStock Reservation')
            ->whereIn('status', ['Active', 'Partially Issued'])
            ->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])
            ->where('valid_until', '<', now())
            ->update(['status' => 'Expired']);

        return $next($passable);
    }
}
