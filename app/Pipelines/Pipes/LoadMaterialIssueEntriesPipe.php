<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use App\Models\StockEntry;
use Closure;

class LoadMaterialIssueEntriesPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $allowedWarehouses = $passable->allowedWarehouses;

        $passable->entries = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)
            ->where('purpose', 'Material Issue')
            ->whereIn('s_warehouse', $allowedWarehouses)
            ->whereNotIn('ste.issue_as', ['Customer Replacement', 'Sample'])
            ->select('sted.status', 'sted.validate_item_code', 'ste.sales_order_no', 'sted.parent', 'sted.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'sted.owner', 'ste.creation', 'ste.issue_as')
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'Issued') ASC")
            ->get();

        return $next($passable);
    }
}
