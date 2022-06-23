<table class="table table-striped" style="font-size: 9pt;">
    <thead>
        <th class="text-center align-middle p-1" style="width: 15%;">Transaction Date</th>
        <th class="text-center align-middle p-1" style="width: 55%;">Subject</th>
        <th class="text-center align-middle p-1" style="width: 15%;">Ref. No.</th>
        <th class="text-center align-middle p-1" style="width: 15%;">Created by</th>
    </thead>
    <tbody>
        @forelse ($logs as $r)
        <tr>
            <td class="text-center align-middle p-1">{{ \Carbon\Carbon::parse($r->creation)->format('F d, Y h:i A') }}</td>
            <td class="text-justify align-middle p-1">{{ $r->subject }}</td>
            <td class="text-center align-middle p-1">{{ $r->reference_name }}</td>
            <td class="text-center align-middle p-1">{{ $r->full_name }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="text-center text-uppercase text-muted p-1">No record(s) found</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="float-right m-2" id="activity-logs-pagination">{{ $logs->links('pagination::bootstrap-4') }}</div>