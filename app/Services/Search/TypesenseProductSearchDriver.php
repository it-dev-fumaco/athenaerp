<?php

namespace App\Services\Search;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Typesense\Client;

class TypesenseProductSearchDriver
{
    private const QUERY_BY = 'description,name,item_name,item_group,lvl1,lvl2,lvl3,lvl4,lvl5,item_classification,stock_uom,supplier_part_nos';

    public function __construct(
        private Client $client,
        private SqlProductSearchDriver $sqlDriver
    ) {}

    public function search(Request $request): LengthAwarePaginator
    {
        if ($request->assigned_items) {
            $stores = $this->sqlDriver->consignmentStoresForUser();
            if ($stores === []) {
                return $this->emptyPage($request);
            }
        }

        if ($request->assigned_to_me) {
            $ids = $this->sqlDriver->assignedToMeItemCodes($request);
            if ($ids === []) {
                return $this->emptyPage($request);
            }
        }

        $filterBy = $this->buildFilterBy($request);
        $q = trim((string) $request->searchString);
        if ($q === '') {
            $q = '*';
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;

        $params = [
            'q' => $q,
            'query_by' => self::QUERY_BY,
            'sort_by' => 'modified:desc',
            'page' => $page,
            'per_page' => $perPage,
            'num_typos' => '2',
        ];
        if ($filterBy !== '') {
            $params['filter_by'] = $filterBy;
        }

        $collection = (string) config('typesense.collection');

        $result = $this->client->collections[$collection]->documents->search($params);

        $found = (int) ($result['found'] ?? 0);
        $hits = $result['hits'] ?? [];
        $orderedIds = [];
        foreach ($hits as $hit) {
            $doc = $hit['document'] ?? [];
            if (isset($doc['id'])) {
                $orderedIds[] = (string) $doc['id'];
            }
        }

        return $this->sqlDriver->paginateFromOrderedItemCodes($request, $orderedIds, $found, $perPage, $page);
    }

    public function suggest(Request $request, int $perPage = 8): LengthAwarePaginator
    {
        $searchString = $request->input('search_string') ?? $request->input('searchString');
        $q = trim((string) $searchString);
        if ($q === '') {
            $q = '*';
        }

        $page = max(1, (int) $request->input('page', 1));

        $params = [
            'q' => $q,
            'query_by' => self::QUERY_BY,
            'sort_by' => 'modified:desc',
            'page' => $page,
            'per_page' => $perPage,
            'num_typos' => '2',
        ];

        $collection = (string) config('typesense.collection');

        $result = $this->client->collections[$collection]->documents->search($params);

        $found = (int) ($result['found'] ?? 0);
        $hits = $result['hits'] ?? [];
        $orderedIds = [];
        foreach ($hits as $hit) {
            $doc = $hit['document'] ?? [];
            if (isset($doc['id'])) {
                $orderedIds[] = (string) $doc['id'];
            }
        }

        if ($orderedIds === []) {
            return new ConcreteLengthAwarePaginator(
                collect(),
                $found,
                $perPage,
                $page,
                [
                    'path' => Paginator::resolveCurrentPath(),
                    'pageName' => 'page',
                    'query' => $request->query(),
                ]
            );
        }

        $rows = \App\Models\Item::query()
            ->whereIn('tabItem.name', $orderedIds)
            ->select('tabItem.name', 'tabItem.description', 'tabItem.item_image_path')
            ->get()
            ->keyBy('name');

        $sorted = collect($orderedIds)
            ->map(fn (string $id) => $rows->get($id))
            ->filter()
            ->values();

        return new ConcreteLengthAwarePaginator(
            $sorted,
            $found,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
                'query' => $request->query(),
            ]
        );
    }

    private function emptyPage(Request $request): LengthAwarePaginator
    {
        return new ConcreteLengthAwarePaginator(
            collect(),
            0,
            20,
            max(1, (int) $request->input('page', 1)),
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
                'query' => $request->query(),
            ]
        );
    }

    private function buildFilterBy(Request $request): string
    {
        $parts = [];

        $checkQty = 1;
        if ($request->has('check_qty')) {
            $checkQty = $request->check_qty == 'on' ? 1 : 0;
        }
        $isPromodiser = Auth::user()->user_group == 'Promodiser' ? 1 : 0;

        if ($checkQty && ! $isPromodiser) {
            $parts[] = 'has_stock_warehouse:=true';
        }

        if ($request->classification) {
            $parts[] = 'item_classification:='.$this->escapeFilterValue((string) $request->classification);
        }

        if ($request->brand) {
            $parts[] = 'brand:='.$this->escapeFilterValue((string) $request->brand);
        }

        if ($request->group) {
            $g = (string) $request->group;
            $groupParts = [
                'item_group:='.$this->escapeFilterValue($g),
                'lvl1:='.$this->escapeFilterValue($g),
                'lvl2:='.$this->escapeFilterValue($g),
                'lvl3:='.$this->escapeFilterValue($g),
                'lvl4:='.$this->escapeFilterValue($g),
                'lvl5:='.$this->escapeFilterValue($g),
            ];
            $parts[] = '('.implode(' || ', $groupParts).')';
        }

        if ($request->wh) {
            $parts[] = 'warehouse_codes:'.$this->escapeFilterValue((string) $request->wh);
        }

        if ($request->assigned_items) {
            $stores = $this->sqlDriver->consignmentStoresForUser();
            $whOr = [];
            foreach ($stores as $store) {
                $whOr[] = 'warehouse_codes:'.$this->escapeFilterValue($store);
            }
            if ($whOr !== []) {
                $parts[] = '('.implode(' || ', $whOr).')';
            }
        }

        if ($request->assigned_to_me) {
            $ids = $this->sqlDriver->assignedToMeItemCodes($request);
            if ($ids !== []) {
                $escaped = array_map(fn (string $id) => $this->escapeFilterValue($id), $ids);
                $parts[] = 'id: ['.implode(',', $escaped).']';
            }
        }

        return implode(' && ', array_filter($parts));
    }

    private function escapeFilterValue(string $value): string
    {
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace('`', '\`', $value);

        return '`'.$value.'`';
    }
}
