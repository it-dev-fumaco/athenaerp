<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ItemOrderHistoryService
{
    private const SORT_COLUMNS = [
        'total_sales' => 'total_sales',
        'total_qty' => 'total_qty',
        'avg_selling_price' => 'avg_selling_price',
        'last_order_date' => 'last_order_date',
        'number_of_orders' => 'number_of_orders',
        'customer' => 'customer_name',
    ];

    private ?string $salesExpression = null;

    private ?string $qtyExpression = null;

    private ?bool $hasModifiedColumn = null;

    /**
     * @param  array{date_from?: string|null, date_to?: string|null, customer?: string|null, sort?: string, direction?: string, per_page?: int, page?: int}  $filters
     */
    public function paginate(string $itemCode, array $filters): LengthAwarePaginator
    {
        if (! $this->tablesExist()) {
            return new LengthAwarePaginator([], 0, 10, 1);
        }

        $perPage = min(100, max(1, (int) Arr::get($filters, 'per_page', 10)));
        $sort = $this->resolveSort($filters);

        $query = $this->groupedQuery($itemCode, $filters)
            ->orderBy($sort['column'], $sort['direction']);

        $paginator = $query->paginate($perPage);
        $this->attachLastOrderNumbers($itemCode, collect($paginator->items()), $filters);

        return $paginator;
    }

    /**
     * @param  array{date_from?: string|null, date_to?: string|null, customer?: string|null}  $filters
     * @return array{
     *     unique_customers: int,
     *     total_sales: float,
     *     total_qty: float,
     *     average_order_value: float,
     *     last_order_date: string|null,
     *     top_customer: array{customer: string, customer_name: string, total_sales: float, percentage: float}|null
     * }
     */
    public function summarize(string $itemCode, array $filters): array
    {
        $empty = [
            'unique_customers' => 0,
            'total_sales' => 0.0,
            'total_qty' => 0.0,
            'average_order_value' => 0.0,
            'last_order_date' => null,
            'top_customer' => null,
        ];

        if (! $this->tablesExist()) {
            return $empty;
        }

        $sales = $this->salesExpression();
        $qty = $this->qtyExpression();

        $totals = $this->baseQuery($itemCode, $filters)
            ->selectRaw("
                COUNT(DISTINCT so.customer) as unique_customers,
                COALESCE(SUM({$sales}), 0) as total_sales,
                COALESCE(SUM({$qty}), 0) as total_qty,
                COUNT(DISTINCT so.name) as number_of_orders,
                MAX(so.transaction_date) as last_order_date
            ")
            ->first();

        $unique = (int) ($totals->unique_customers ?? 0);
        $totalSales = (float) ($totals->total_sales ?? 0);
        $totalQty = (float) ($totals->total_qty ?? 0);
        $orderCount = (int) ($totals->number_of_orders ?? 0);

        $topCustomer = null;
        if ($unique > 0 && $totalSales > 0) {
            $top = $this->groupedQuery($itemCode, $filters)
                ->orderByDesc('total_sales')
                ->limit(1)
                ->first();

            if ($top) {
                $topSales = (float) $top->total_sales;
                $topCustomer = [
                    'customer' => $top->customer,
                    'customer_name' => $top->customer_name ?: $top->customer,
                    'total_sales' => $topSales,
                    'percentage' => round(($topSales / $totalSales) * 100, 2),
                ];
            }
        }

        return [
            'unique_customers' => $unique,
            'total_sales' => $totalSales,
            'total_qty' => $totalQty,
            'average_order_value' => $orderCount > 0 ? $totalSales / $orderCount : 0.0,
            'last_order_date' => $totals->last_order_date ?? null,
            'top_customer' => $topCustomer,
        ];
    }

    /**
     * Distinct customers who ordered this item (date-filtered, not customer-filtered).
     *
     * @param  array{date_from?: string|null, date_to?: string|null}  $filters
     * @return array<int, array{id: string, name: string}>
     */
    public function customerOptions(string $itemCode, array $filters): array
    {
        if (! $this->tablesExist()) {
            return [];
        }

        $optionsFilters = $filters;
        unset($optionsFilters['customer']);

        return $this->baseQuery($itemCode, $optionsFilters)
            ->select('so.customer', DB::raw('MAX(so.customer_name) as customer_name'))
            ->groupBy('so.customer')
            ->orderBy('customer_name')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->customer,
                    'name' => $row->customer_name ?: $row->customer,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array{date_from?: string|null, date_to?: string|null, customer?: string|null}  $filters
     */
    private function groupedQuery(string $itemCode, array $filters)
    {
        $sales = $this->salesExpression();
        $qty = $this->qtyExpression();

        return $this->baseQuery($itemCode, $filters)
            ->groupBy('so.customer')
            ->selectRaw("
                so.customer as customer,
                MAX(so.customer_name) as customer_name,
                SUM({$sales}) as total_sales,
                SUM({$qty}) as total_qty,
                CASE WHEN COALESCE(SUM({$qty}), 0) = 0 THEN 0 ELSE SUM({$sales}) / SUM({$qty}) END as avg_selling_price,
                MAX(so.transaction_date) as last_order_date,
                COUNT(DISTINCT so.name) as number_of_orders
            ");
    }

    /**
     * @param  array{date_from?: string|null, date_to?: string|null, customer?: string|null}  $filters
     */
    private function baseQuery(string $itemCode, array $filters)
    {
        $query = DB::table('tabSales Order Item as soi')
            ->join('tabSales Order as so', 'so.name', '=', 'soi.parent')
            ->where('soi.item_code', $itemCode)
            ->where('so.docstatus', 1);

        $dateFrom = Arr::get($filters, 'date_from');
        $dateTo = Arr::get($filters, 'date_to');
        $customer = Arr::get($filters, 'customer');

        if (filled($dateFrom)) {
            $query->whereDate('so.transaction_date', '>=', $dateFrom);
        }
        if (filled($dateTo)) {
            $query->whereDate('so.transaction_date', '<=', $dateTo);
        }
        if (filled($customer)) {
            $query->where('so.customer', $customer);
        }

        return $query;
    }

    /**
     * @param  Collection<int, object>  $rows
     * @param  array{date_from?: string|null, date_to?: string|null, customer?: string|null}  $filters
     */
    private function attachLastOrderNumbers(string $itemCode, Collection $rows, array $filters): void
    {
        $customerIds = $rows->pluck('customer')->filter()->unique()->values();
        if ($customerIds->isEmpty()) {
            return;
        }

        $latestDates = $this->baseQuery($itemCode, $filters)
            ->whereIn('so.customer', $customerIds->all())
            ->groupBy('so.customer')
            ->selectRaw('so.customer as customer_id, MAX(so.transaction_date) as max_date')
            ->pluck('max_date', 'customer_id');

        if ($latestDates->isEmpty()) {
            return;
        }

        $lastOrdersQuery = $this->baseQuery($itemCode, $filters)
            ->where(function ($query) use ($latestDates) {
                foreach ($latestDates as $customerId => $maxDate) {
                    $query->orWhere(function ($inner) use ($customerId, $maxDate) {
                        $inner->where('so.customer', $customerId)
                            ->where('so.transaction_date', $maxDate);
                    });
                }
            })
            ->select('so.customer', 'so.name as last_order_no');

        if ($this->hasModifiedColumn()) {
            $lastOrdersQuery->orderByDesc('so.modified');
        } else {
            $lastOrdersQuery->orderByDesc('so.name');
        }

        $lastByCustomer = $lastOrdersQuery->get()->unique('customer')->keyBy('customer');

        foreach ($rows as $row) {
            $row->last_order_no = optional($lastByCustomer->get($row->customer))->last_order_no;
        }
    }

    /**
     * @param  array{sort?: string, direction?: string}  $filters
     * @return array{column: string, direction: string}
     */
    private function resolveSort(array $filters): array
    {
        $sort = Arr::get($filters, 'sort', 'total_sales');
        $column = Arr::get(self::SORT_COLUMNS, $sort, 'total_sales');
        $direction = strtolower((string) Arr::get($filters, 'direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        return ['column' => $column, 'direction' => $direction];
    }

    private function salesExpression(): string
    {
        if ($this->salesExpression !== null) {
            return $this->salesExpression;
        }

        $hasNetAmount = Schema::hasColumn('tabSales Order Item', 'net_amount');
        $hasNetRate = Schema::hasColumn('tabSales Order Item', 'net_rate');

        if ($hasNetAmount && $hasNetRate) {
            $this->salesExpression = 'COALESCE(soi.net_amount, soi.qty * COALESCE(soi.net_rate, soi.rate, 0))';
        } elseif ($hasNetAmount) {
            $this->salesExpression = 'COALESCE(soi.net_amount, soi.qty * COALESCE(soi.rate, 0))';
        } elseif ($hasNetRate) {
            $this->salesExpression = '(soi.qty * COALESCE(soi.net_rate, soi.rate, 0))';
        } else {
            $this->salesExpression = '(soi.qty * COALESCE(soi.rate, 0))';
        }

        return $this->salesExpression;
    }

    private function qtyExpression(): string
    {
        if ($this->qtyExpression !== null) {
            return $this->qtyExpression;
        }

        $this->qtyExpression = Schema::hasColumn('tabSales Order Item', 'stock_qty')
            ? 'COALESCE(soi.stock_qty, soi.qty, 0)'
            : 'COALESCE(soi.qty, 0)';

        return $this->qtyExpression;
    }

    private function hasModifiedColumn(): bool
    {
        if ($this->hasModifiedColumn === null) {
            $this->hasModifiedColumn = Schema::hasColumn('tabSales Order', 'modified');
        }

        return $this->hasModifiedColumn;
    }

    private function tablesExist(): bool
    {
        return Schema::hasTable('tabSales Order') && Schema::hasTable('tabSales Order Item');
    }
}
