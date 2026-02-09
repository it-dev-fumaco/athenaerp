<table class="table table-striped" style="font-size: 13px;">
    <col style="width: 25%;">
    <col style="width: 45%;">
    <col style="width: 30%;">
    <thead>
        <th class="p-1 text-center text-uppercase">Month</th>
        <th class="p-1 text-center text-uppercase">Total Sales</th>
        <th class="p-1 text-center text-uppercase">Action</th>
    </thead>
    @foreach ($months as $index => $month)
    @php
        if(Carbon\Carbon::now() <= Carbon\Carbon::parse($month.'-'.$requestYear)){
            break;
        }
        $totalPerMonth = 0;
        $hasExistingRecord = false;
        $indicator = 'secondary';
        $status = 'Pending';
        if (isset($salesPerMonth[$month])) {
            $totalPerMonth = $salesPerMonth[$month][0]->total_amount;
            $hasExistingRecord = true;
            $status = $salesPerMonth[$month][0]->status;
            $indicator = $status == 'Draft' ? 'danger' : 'success';
        }
    @endphp
    <tr>
        <td class="p-2 align-middle text-left" style="white-space: nowrap">
            <span class="text-{{ $indicator }}">●</span> {{ $month }}
        </td>
        <td class="p-2 text-center align-middle">{{ '₱ ' . number_format($totalPerMonth, 2) }}</td>
        <td class="p-2 align-middle text-center">
            <div class="col-12 col-lg-4 mx-auto">
                @switch($status)
                    @case('Pending')
                        <a href="/view_monthly_sales_form/{{ $branch }}/{{ $month }}-{{ $requestYear }}" class="btn btn-xs btn-primary w-1010 p-2">
                            <i class="fas fa-plus"></i> Create
                        </a>
                        @break
                    @case('Draft')
                        <a href="/view_monthly_sales_form/{{ $branch }}/{{ $month }}-{{ $requestYear }}" class="btn btn-xs btn-warning w-1100 p-2">
                            <i class="fas fa-pencil-alt"></i> Update
                        </a>
                        @break
                    @default
                        <a href="/view_monthly_sales_form/{{ $branch }}/{{ $month }}-{{ $requestYear }}" class="btn btn-xs btn-info w-1100 p-2">
                            <i class="far fa-eye"></i> View
                        </a>
                @endswitch
            </div>
        </td>
    </tr>
    @endforeach
</table>
<hr class="p-0 m-1">
<div class="d-flex flex-row justify-content-between p-2 text-muted" style="font-size: 12px;">
    <div class="font-weight-bold">Legend:</div>
    <div>
        <span class="text-secondary">●</span> Pending
    </div>
    <div>
        <span class="text-danger">●</span> Draft
    </div>
    <div>
        <span class="text-success">●</span> Submitted
    </div>
</div>