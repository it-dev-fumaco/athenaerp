<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;
use Illuminate\Support\Facades\DB;

class MarkIssuedReservationsPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        DB::table('tabStock Reservation')
            ->whereNotIn('status', ['Cancelled', 'Expired', 'Issued'])
            ->where('consumed_qty', '>', 0)
            ->whereRaw('consumed_qty >= reserve_qty')
            ->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])
            ->update(['status' => 'Issued']);

        return $next($passable);
    }
}
