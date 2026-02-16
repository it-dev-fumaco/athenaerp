<div class="container-fluid bg-white p-0" style="font-size: 9pt;">
        <div class="col-12 p-0">
            <table class="table sales-report-table" border="1">
                <thead>
                    <tr>
                        <th class="bg-navy">Branch / Store</th>
                        @foreach ($includedDates as $transactionDate)
                        <th class="bg-navy">{{ $transactionDate }}</th>
                        @endforeach
                        <th class="bg-navy">Total Sales</th> 
                    </tr>
                </thead>
                <tbody>
                    @forelse ($warehousesWithData as $warehouse)
                    <tr>
                        <td class="bg-white">{{ $warehouse }}</td>
                        @foreach ($includedDates as $saleDate)
                        @php
                            $saleAmount = isset($report[$warehouse][$saleDate]) ? '₱ ' . number_format($report[$warehouse][$saleDate], 2)  : '--';
                        @endphp
                        <td class="bg-white">{{ $saleAmount }}</td>
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