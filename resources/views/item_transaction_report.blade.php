<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Item Transaction Report</title>
</head>
<body>
    <span>Total: {{ count($report) }} - DB: {{ $db }}</span> <br>
    {{-- <span>Created - Min Date: {{ collect($item_qry)->min('creation') }} - Max Date: {{ collect($item_qry)->max('creation') }}</span> <br>
    <span>Modified - Min Date: {{ collect($item_qry)->min('modified') }} - Max Date: {{ collect($item_qry)->max('modified') }}</span> --}}
    <table style='border: 1px solid #000; width: 100%'>
        <tr>
            <th style='border: 1px solid #000; width: 5%'>Name</th>
            <th style='border: 1px solid #000; width: 5%'>Item Code</th>
            <th style='border: 1px solid #000; width: 50%'>Item Description</th>
            <th style='border: 1px solid #000; width: 10%'>Item Classification</th>
            <th style='border: 1px solid #000; width: 10%'>Status</th>
            <th style='border: 1px solid #000; width: 10%'>Last Transaction Table</th>
            <th style='border: 1px solid #000; width: 10%'>Last Transaction Date</th>
        </tr>
        @foreach ($report as $item => $details)
            <tr>
                <td style='border: 1px solid #000; width: 5%'>{{ isset($item_details[$item]) ? strip_tags($item_details[$item][0]->name) : '-' }}</td>
                <td style='border: 1px solid #000; width: 5%'>{{ $item }}</td>
                <td style='border: 1px solid #000; width: 50%'>{{ isset($item_details[$item]) ? strip_tags($item_details[$item][0]->description) : '-' }}</td>
                <td style='border: 1px solid #000; width: 10%'>{{ isset($item_details[$item]) ? $item_details[$item][0]->item_classification : '-' }}</td>
                <td style='border: 1px solid #000; width: 10%'>
                    @php
                        $status = 0;
                        if(isset($item_details[$item])){
                            $status = $item_details[$item][0]->disabled == 0 ? 'Enabled' : 'Disabled';
                        }
                    @endphp
                    {{ $status }}
                </td>
                <td style='border: 1px solid #000; width: 10%'>{{ key($details) }}</td>
                <td style='border: 1px solid #000; width: 10%'>{{ $details[key($details)] ? Carbon\Carbon::parse($details[key($details)])->format('M d, Y h:i a') : null }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>