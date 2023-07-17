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
        if (array_key_exists($index, $sales_per_month)) {
            $total_per_month = $sales_per_month[$index];
            $has_existing_record = true;
        }
    @endphp
    @if ($request_year == $currentYear)
    @if ($index <= $currentMonth)
    <tr>
        <td class="p-2 align-middle text-center">{{ $month }}</td>
        <td class="p-2 text-center align-middle">{{ '₱ ' . number_format($total_per_month, 2) }}</td>
        <td class="p-2 align-middle text-center">
            @if ($has_existing_record) 
            <a href="/view_product_sold_form/{{ $branch }}/{{ $request_year }}-{{ $index }}-01" class="btn btn-xs btn-info">
                <i class="far fa-eye"></i> View
            </a>
            @else
            <a href="/view_product_sold_form/{{ $branch }}/{{ $request_year }}-{{ $index }}-01" class="btn btn-xs btn-primary">
                <i class="fas fa-plus"></i> Create
            </a>
            @endif
        </td>
    </tr>
    @endif
    @else
    <tr>
        <td class="p-2 align-middle text-center">{{ $month }}</td>
        <td class="p-2 text-center align-middle">{{ '₱ ' . number_format($total_per_month, 2) }}</td>
        <td class="p-2 align-middle text-center">
            @if ($has_existing_record) 
            <a href="/view_product_sold_form/{{ $branch }}/{{ $request_year }}-{{ $index }}-01" class="btn btn-xs btn-info">
                <i class="far fa-eye"></i> View
            </a>
            @else
            <a href="/view_product_sold_form/{{ $branch }}/{{ $request_year }}-{{ $index }}-01" class="btn btn-xs btn-primary">
                <i class="fas fa-plus"></i> Create
            </a>
            @endif
        </td>
    </tr>
    @endif
    @endforeach
</table>