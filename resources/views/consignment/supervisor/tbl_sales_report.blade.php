<table class="table table-bordered w-100" id="report-table" style="font-size: 10pt;">
    <tr>
        <th class="text-center font-responsive align-middle p-1" rowspan=2 colspan=2 style="width: 20%;">Promodiser</th>
        <th class="text-center font-responsive align-middle p-1" rowspan=2 style="width: 5%;">Opening Stock</th>
        <th class="text-center font-responsive align-middle p-1" colspan={{ count($cutoff_periods) }}>Cut Off Period(s)</th>
        <th class="text-center font-responsive align-middle p-1" rowspan=2 style="width: 8%;">Total</th>
    </tr>
    <tr>
        @foreach ($cutoff_periods as $period)
            <th class="text-center font-responsive align-middle p-1">{{ Carbon\Carbon::parse($period)->format('M-d') }}</th>
        @endforeach
    </tr>
    @foreach ($report_arr as $report)
        <tr>
            <td class="text-left font-responsive align-middle p-1 pl-4" colspan={{ count($cutoff_periods) + 4 }}>
                <span style="color: #001F3F">{{ $report['user'] }}</span>
            </td>
        </tr>
        @foreach ($report['assigned_warehouses'] as $warehouse)
            @php
                $opening_stock = isset($opening_stocks_arr[$report['user']][$warehouse->warehouse]['qty']) ? $opening_stocks_arr[$report['user']][$warehouse->warehouse]['qty'] : 0;
                $total_per_warehouse = isset($product_sold[$report['user']][$warehouse->warehouse]) ? collect($product_sold[$report['user']][$warehouse->warehouse])->sum('amount') : 0;
            @endphp
            <tr>
                <td>&nbsp;</td>
                <td class="text-left font-responsive align-middle p-1">{{ $warehouse->warehouse }}</td>
                <td class="text-center font-responsive align-middle p-1">
                    <span class="{{ $opening_stock <= 0 ? 'text-muted' : null  }}">{{ number_format($opening_stock) }}</span>
                    <span class="opening-stocks d-none">{{ $opening_stock * 1 }}</span>
                </td>
                @foreach ($cutoff_periods as $period)
                    @php
                        $amount = isset($product_sold[$report['user']][$warehouse->warehouse][$period]['amount']) ? $product_sold[$report['user']][$warehouse->warehouse][$period]['amount'] : 0;
                    @endphp
                    <td class="text-center font-responsive align-middle p-1">
                        <span class="{{ $amount <= 0 ? 'text-muted' : null  }}">₱ {{ number_format($amount) }}</span>
                        <span class="cutoff {{ $period }} d-none" data-period='{{ $period }}'>{{ $amount }}</span>
                    </td>
                @endforeach
                <td class="text-center font-responsive align-middle p-1">
                    <span class="{{ $total_per_warehouse <= 0 ? 'text-muted' : null  }}">₱ {{ number_format($total_per_warehouse) }}</span>
                    <span class="total-per-warehouse d-none">{{ $total_per_warehouse }}</span>
                </td>
            </tr>
        @endforeach
    @endforeach
    <tr>
        <td class="text-center font-responsive font-weight-bold align-middle p-1">Total: </td>
        <td>&nbsp;</td>
        <td class="text-center font-responsive align-middle p-1">
            <span id="total-opening-stocks"></span>
        </td>
        @foreach ($cutoff_periods as $period)
            <td class="text-center font-responsive align-middle p-1">
                <span id="total-of-cutoff-{{ $period }}"></span>
            </td>
        @endforeach
        <td class="text-center font-responsive align-middle p-1">
            <span id="total-of-all-warehouse"></span>
        </td>
    </tr>
</table>

<style>
    #report-table th{
        color: #fff;
        background-color: #001F3F;
    }
</style>

<script>
    $(document).ready(function (){
        var total_product_sold = 0;
        var total_opening_stocks = 0;

        get_total_per_cutoff();
        function get_total_per_cutoff(){
            $('.cutoff').each(function(){
                var period = $(this).data('period');
                var val = 0;
                $('.cutoff.'+period).each(function(){
                    val += parseInt($(this).text());
                    const cutoff = val.toLocaleString('en-US', {maximumFractionDigits: 2})
                    $('#total-of-cutoff-'+period).text('₱ ' + cutoff);
                });
            });
        }

        get_total_product_sold();
        function get_total_product_sold(){
            $('.total-per-warehouse').each(function(){
                total_product_sold += parseInt($(this).text());
            });
            
            const formatted = total_product_sold.toLocaleString('en-US', {maximumFractionDigits: 2})
            $('#total-of-all-warehouse').text('₱ ' + formatted);
        }
        
        get_total_opening_stocks();
        function get_total_opening_stocks(){
            $('.opening-stocks').each(function(){
                total_opening_stocks += parseInt($(this).text());
            });

            const formatted = total_opening_stocks.toLocaleString('en-US', {maximumFractionDigits: 2})
            $('#total-opening-stocks').text(formatted);
        }
    });
</script>