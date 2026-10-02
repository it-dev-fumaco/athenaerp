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
            <form method="GET" action="{{ url('/pending_submit_report') }}" class="form-inline mb-3">
                <label class="mr-2" for="document_type">Document</label>
                <select id="document_type" name="document_type" class="form-control mr-3">
                    <option value="all" @selected($documentType === 'all')>All</option>
                    <option value="ste" @selected($documentType === 'ste')>STE</option>
                    <option value="dr" @selected($documentType === 'dr')>Draft DR</option>
                </select>
                <label class="mr-2" for="item_code">Item Code</label>
                <input type="text" id="item_code" name="item_code" value="{{ $itemCode }}" class="form-control mr-2" placeholder="Item code">
                <label class="mr-2" for="created_by">Created By</label>
                <select id="created_by" name="created_by" class="form-control mr-2">
                    <option value="" @selected($createdBy === '')>All</option>
                    @foreach($creators as $creator)
                        <option value="{{ $creator['value'] }}" @selected($createdBy === $creator['value'])>{{ $creator['label'] }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary mr-2">Filter</button>
                @if($itemCode !== '' || $createdBy !== '' || $documentType !== 'all')
                    <a href="{{ url('/pending_submit_report') }}" class="btn btn-default mr-2">Clear</a>
                @endif
                <a class="btn btn-success" href="{{ url('/pending_submit_report') }}?export=1&amp;document_type={{ urlencode($documentType) }}&amp;item_code={{ urlencode($itemCode) }}&amp;created_by={{ urlencode($createdBy) }}">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </a>
                <span class="ml-3 font-weight-bold">Total records: {{ number_format($rows->total()) }}</span>
            </form>

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
