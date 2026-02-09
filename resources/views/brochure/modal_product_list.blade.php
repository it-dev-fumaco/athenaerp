<h6><b>Project: </b><span style="text-transform: uppercase">{{ $project ? $project : '-' }}</span></h6>
<h6><b>Customer: </b><span style="text-transform: uppercase">{{ $customer ? $customer : '-' }}</span></h6>
<div class="overflow-auto" style="max-height: 70vh;">
    <table class="table table-bordered table-striped table-hovered" id="attrib-table" style="font-size: 9pt;">
        <thead>
            <tr>
                @foreach ($headers as $col)
                    @if($col)
                        <th style="white-space: nowrap;">{{ $col }}</th>
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $generatedCount = 0;
            @endphp
            @foreach ($content as $row)
                @php
                    $attributes = isset($row['attrib']) ? $row['attrib'] : [];
                    $checker = isset($row['attributes']) ? collect($row['attributes'])->pluck('attribute_value')->filter()->values()->all() : [];

                    $isEmpty = $checker ? 0 : 1;

                    if(!$isEmpty){
                        $generatedCount += 1;
                    }
                @endphp
                <tr style="background-color: {{ $isEmpty ? 'rgb(247, 93, 93)' : 'rgba(0,0,0,0)' }}">
                    @foreach ($headers as $col)
                        @if($col)
                            @php
                                $colValue = '-';
                                if(isset($attributes[$col])){
                                    $colValue = $attributes[$col];
                                }else if(isset($row[$col])){
                                    $colValue = $row[$col];
                                }
                            @endphp
                            <td style="white-space: nowrap;">{{ $colValue }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<style>
    #attrib-table{
        overflow: auto;
        height: 70vh;
    }
    #attrib-table thead th {
        position: sticky;
        top: 0;
        background-color: #fff;
        z-index: 1;
    }
</style>

<script>
    $(document).ready(function (){
        $('#generated-prod-count').text('{{ $generatedCount }}');
    });
</script>