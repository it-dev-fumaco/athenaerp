@extends('layouts.inventory_shell', [
    'namePage' => 'Unsubmitted Transactions',
    'activePage' => 'pending_submit_report',
])

@section('inventory_main')
<div class="content border-0 bg-transparent shadow-none p-3 m-0 w-100 min-width-0">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-1 font-weight-bold d-block float-none">Unsubmitted Transactions</h5>
            <p class="mb-0 text-muted clearfix">Issued items whose Stock Entry or Delivery is still draft.</p>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ url('/pending_submit_report') }}" class="pending-submit-filters mb-3">
                <div class="pending-submit-filters__field">
                    <label for="document_type">Document</label>
                    <select id="document_type" name="document_type" class="form-control">
                        <option value="all" @selected($documentType === 'all')>All</option>
                        <option value="ste" @selected($documentType === 'ste')>STE</option>
                        <option value="dr" @selected($documentType === 'dr')>Draft DR</option>
                    </select>
                </div>
                <div class="pending-submit-filters__field">
                    <label for="item_code">Item Code</label>
                    <input type="text" id="item_code" name="item_code" value="{{ $itemCode }}" class="form-control" placeholder="Item code">
                </div>
                <input type="hidden" name="created_by" value="{{ $createdBy }}">
                <div class="pending-submit-filters__field">
                    <label for="target_warehouse">Target Warehouse</label>
                    <select id="target_warehouse" name="target_warehouse" class="form-control">
                        <option value="" @selected($targetWarehouse === '')>All</option>
                        @foreach($targetWarehouses as $warehouse)
                            <option value="{{ $warehouse }}" @selected($targetWarehouse === $warehouse)>{{ $warehouse }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pending-submit-filters__actions">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    @if($itemCode !== '' || $createdBy !== '' || $targetWarehouse !== '' || $documentType !== 'all')
                        <a href="{{ url('/pending_submit_report') }}" class="btn btn-default">Clear</a>
                    @endif
                    <a class="btn btn-success" href="{{ url('/pending_submit_report') }}?export=1&amp;document_type={{ urlencode($documentType) }}&amp;item_code={{ urlencode($itemCode) }}&amp;created_by={{ urlencode($createdBy) }}&amp;target_warehouse={{ urlencode($targetWarehouse) }}">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </a>
                    <span class="font-weight-bold">Total records: {{ number_format($rows->total()) }}</span>
                </div>
            </form>

            @php
                $userTabQuery = request()->except(['page', 'export', 'created_by']);
            @endphp
            <div class="pending-submit-users" role="tablist" aria-label="Pending transactions by user">
                <a
                    href="{{ url('/pending_submit_report') }}{{ $userTabQuery ? '?'.http_build_query($userTabQuery) : '' }}"
                    class="pending-submit-users__tab {{ $createdBy === '' ? 'is-active' : '' }}"
                    role="tab"
                    aria-selected="{{ $createdBy === '' ? 'true' : 'false' }}"
                >
                    All
                    <span class="pending-submit-users__count">{{ number_format($userTotal) }}</span>
                </a>
                @foreach($userStats as $stat)
                    <a
                        href="{{ url('/pending_submit_report') }}?{{ http_build_query(array_merge($userTabQuery, ['created_by' => $stat['value']])) }}"
                        class="pending-submit-users__tab {{ $createdBy === $stat['value'] ? 'is-active' : '' }}"
                        role="tab"
                        aria-selected="{{ $createdBy === $stat['value'] ? 'true' : 'false' }}"
                    >
                        {{ $stat['label'] }}
                        <span class="pending-submit-users__count">{{ number_format($stat['count']) }}</span>
                    </a>
                @endforeach
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 pending-submit-table">
                    <thead>
                        <tr>
                            <th rowspan="2" class="align-middle text-center">Item Code</th>
                            <th colspan="10" class="text-center">Pending Transactions</th>
                            <th colspan="3" class="text-center">Current Available Stock Per Source</th>
                        </tr>
                        <tr>
                            <th class="text-center">No.</th>
                            <th class="text-center">Date Created</th>
                            <th class="text-center">Created By</th>
                            <th class="text-center">Transaction Type</th>
                            <th class="text-center">Item Status</th>
                            <th class="text-center">Date Issued</th>
                            <th class="text-center">Issued By</th>
                            <th class="text-center">Main Status</th>
                            <th class="text-center">Doc Status</th>
                            <th class="text-center">Qty</th>
                            <th class="text-center">Warehouse</th>
                            <th class="text-center">Actual</th>
                            <th class="text-center">Available</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                @if($row['show_item'])
                                    <td rowspan="{{ $row['item_rowspan'] }}" class="align-middle font-weight-bold">{{ $row['item_code'] }}</td>
                                @endif
                                <td>{{ $row['document_no'] }}</td>
                                <td class="text-nowrap">{{ $row['date_created'] ?: '—' }}</td>
                                <td>{{ $row['created_by'] ?: '—' }}</td>
                                <td>{{ $row['transaction_type'] }}</td>
                                <td class="text-center">{{ $row['item_status'] }}</td>
                                <td class="text-nowrap">{{ $row['date_issued'] ?: '—' }}</td>
                                <td>{{ $row['issued_by'] ?: '—' }}</td>
                                <td class="text-center">{{ $row['main_status'] ?: '—' }}</td>
                                <td class="text-center">{{ $row['doc_status'] }}</td>
                                <td class="text-right">{{ (float) $row['qty'] == (int) $row['qty'] ? number_format($row['qty']) : number_format($row['qty'], 2) }}</td>
                                @if($row['show_warehouse'])
                                    <td rowspan="{{ $row['warehouse_rowspan'] }}" class="align-middle">{{ $row['warehouse'] }}</td>
                                    <td rowspan="{{ $row['warehouse_rowspan'] }}" class="align-middle text-right">{{ (float) $row['actual'] == (int) $row['actual'] ? number_format($row['actual']) : number_format($row['actual'], 2) }}</td>
                                    <td rowspan="{{ $row['warehouse_rowspan'] }}" class="align-middle text-right">{{ (float) $row['available'] == (int) $row['available'] ? number_format($row['available']) : number_format($row['available'], 2) }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="text-center text-muted">No issued items are waiting for a Stock Entry or Delivery to be submitted.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3 pending-submit-pager">
                {{ $rows->links() }}
            </div>
        </div>
    </div>
</div>
<style>
    .pending-submit-filters {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem 1.25rem;
        width: 100%;
        min-width: 0;
    }
    .pending-submit-filters__field {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex: 1 1 auto;
        max-width: 100%;
    }
    .pending-submit-filters__field label {
        margin: 0;
        flex: 0 0 auto;
        white-space: nowrap;
    }
    .pending-submit-filters__field .form-control {
        flex: 1 1 9rem;
        width: 9rem;
        min-width: 9rem;
        max-width: 16rem;
    }
    @media (max-width: 575.98px) {
        .pending-submit-filters__field {
            flex-basis: 100%;
        }
        .pending-submit-filters__field .form-control {
            width: 1%;
            min-width: 0;
        }
    }
    .pending-submit-filters__actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }
    .pending-submit-users {
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        margin-bottom: 1rem;
        padding-bottom: 0.25rem;
    }
    .pending-submit-users__tab {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        flex: 0 0 auto;
        border: 1px solid #dbe3ee;
        background: #fff;
        color: #0c2347;
        border-radius: 999px;
        padding: 0.35rem 0.75rem;
        font-size: 0.8125rem;
        font-weight: 600;
        line-height: 1.2;
        text-decoration: none;
        white-space: nowrap;
    }
    .pending-submit-users__tab:hover {
        color: #0c2347;
        text-decoration: none;
        border-color: #3b82f6;
    }
    .pending-submit-users__tab.is-active {
        background: #3b82f6;
        border-color: #3b82f6;
        color: #fff;
    }
    .pending-submit-users__count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.35rem;
        padding: 0 0.35rem;
        border-radius: 999px;
        background: rgba(12, 35, 71, 0.08);
        font-size: 0.75rem;
        font-weight: 700;
    }
    .pending-submit-users__tab.is-active .pending-submit-users__count {
        background: rgba(255, 255, 255, 0.25);
        color: #fff;
    }
    .pending-submit-table {
        font-size: 12px;
    }
    .pending-submit-table th,
    .pending-submit-table td {
        padding: 0.3rem 0.4rem;
    }
    .pending-submit-pager {
        font-size: 13px;
    }
</style>
@endsection
