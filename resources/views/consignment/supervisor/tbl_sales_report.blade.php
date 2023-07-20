<div class="container-fluid bg-white p-0" style="font-size: 9pt;">
        <div class="col-12 p-0">
            <table class="table sales-report-table" border="1">
                <thead>
                    <tr>
                        <th class="bg-navy">Branch / Store</th>
                        @foreach ($included_dates as $transaction_date)
                        <th class="bg-navy">{{ $transaction_date }}</th>
                        @endforeach
                        <th class="bg-navy">Total Sales</th> 
                    </tr>
                </thead>
                <tbody>
                    @forelse ($warehouses_with_data as $warehouse)
                    <tr>
                        <td class="bg-white">{{ $warehouse }}</td>
                        @foreach ($included_dates as $sale_date)
                        @php
                            $sale_amount = isset($report[$warehouse][$sale_date]) ? '₱ ' . number_format($report[$warehouse][$sale_date], 2)  : '--';
                        @endphp
                        <td class="bg-white">{{ $sale_amount }}</td>
                        @endforeach
                        <td class="bg-white">{{ '₱ ' . number_format(collect($report[$warehouse])->sum(), 2) }}</td>
                    </tr>
                    @empty
                        
                    @endforelse
                </tbody>
            </table>
        </div>
</div>
<script>
    $('.sales-report-table').bootstrapTable('destroy').bootstrapTable({
      height: undefined,
    //   showColumns: true,
    //   showToggle: true,
      fixedColumns: true,
      fixedNumber: 1,
      fixedRightNumber: 1
    })
</script>