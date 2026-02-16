<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;
use Illuminate\Support\Facades\DB;

class LoadSubmittedPulloutsPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $submittedPullouts = DB::table('tabConsignment Stock Entry as cste')
            ->join('tabStock Entry as ste', 'ste.name', 'cste.references')
            ->where('cste.status', 'Pending')
            ->whereIn('cste.purpose', ['Pull Out', 'Store Transfer'])
            ->where('ste.docstatus', 1)
            ->select('cste.name', 'cste.source_warehouse')
            ->get();

        $passable->csteNames = collect($submittedPullouts)->pluck('name')->values()->all();
        $passable->submittedPullouts = $submittedPullouts;

        return $next($passable);
    }
}
