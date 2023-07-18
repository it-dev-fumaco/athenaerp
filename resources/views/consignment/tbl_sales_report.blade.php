<table class="table table-striped" style="font-size: 13px;">
    <col style="width: 35%;">
    <col style="width: 40%;">
    <col style="width: 35%;">
    <thead>
        <th class="p-1 text-center text-uppercase">Month</th>
        <th class="p-1 text-center text-uppercase">Total Sales</th>
        <th class="p-1 text-center text-uppercase">Action</th>
    </thead>
    @foreach ($months as $index => $month)
    @php
        $index = $index + 1;
        $total_per_month = 0;
        $has_existing_record = false;
        $indicator = 'secondary';
        if (isset($sales_per_month[$month])) {
            $total_per_month = $sales_per_month[$month][0]->total_amount;
            $has_existing_record = true;
            $indicator = $sales_per_month[$month][0]->status == 'Draft' ? 'danger' : 'success';
        }
    @endphp
    <tr>
        <td class="p-2 align-middle text-center">
            <span class="text-{{ $indicator }}">●</span> {{ $month }}
        </td>
        <td class="p-2 text-center align-middle">{{ '₱ ' . number_format($total_per_month, 2) }}</td>
        <td class="p-2 align-middle text-center">
            @if ($has_existing_record) 
            <a href="/view_monthly_sales_form/{{ $branch }}/{{ $month }}-{{ $request_year }}" class="btn btn-xs btn-info">
                <i class="far fa-eye"></i> View
            </a>
            @else
            <a href="/view_monthly_sales_form/{{ $branch }}/{{ $month }}-{{ $request_year }}" class="btn btn-xs btn-primary">
                <i class="fas fa-plus"></i> Create
            </a>
            @endif
        </td>
    </tr>
    @endforeach
</table>
<div class="row" style="font-size: 9pt;">
    <div class="col-4">
        <span class="text-secondary">●</span> Pending
    </div>
    <div class="col-4">
        <span class="text-danger">●</span> Draft
    </div>
    <div class="col-4">
        <span class="text-success">●</span> Submitted
    </div>
</div>