<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Arr;

class FormatMaterialIssueResponsePipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $list = [];
        foreach ($passable->entries as $d) {
            $customer = Arr::get($passable->soCustomers, $d->sales_order_no, null);
            $partNos = Arr::get($passable->partNosQuery, $d->item_code, '');
            $owner = Arr::get($passable->ownerFullNames, $d->owner, '--');
            $key = "{$d->item_code}-{$d->s_warehouse}";

            $list[] = [
                'customer' => $customer,
                'item_code' => $d->item_code,
                'description' => $d->description,
                's_warehouse' => $d->s_warehouse,
                't_warehouse' => $d->t_warehouse,
                'actual_qty' => $passable->actualQtyMap[$key] ?? 0,
                'uom' => $d->uom,
                'name' => $d->name,
                'owner' => $owner,
                'parent' => $d->parent,
                'part_nos' => $partNos,
                'qty' => $d->qty,
                'validate_item_code' => $d->validate_item_code,
                'status' => $d->status,
                'balance' => $passable->availableQtyMap[$key] ?? 0,
                'sales_order_no' => $d->sales_order_no,
                'issue_as' => $d->issue_as,
                'parent_warehouse' => Arr::get($passable->parentWarehouses, $d->s_warehouse, null),
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i:A'),
            ];
        }

        $passable->records = $list;

        return $next($passable);
    }
}
