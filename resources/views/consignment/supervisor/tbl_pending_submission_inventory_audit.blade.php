<table class="table table-striped table-bordered">
    <thead>
        <th class="text-center text-uppercase font-responsive p-2" style="font-size: 12px;">Branch / Store</th>
    </thead>
    <tbody>
        @forelse ($pending as $row)
            <tr>
                <td class="font-responsive">
                    <span class="d-block" style="font-size: 11px;">{{ $row['store'] }}</span>
                    @if (!$row['beginning_inventory_date'])
                    <span class="d-block text-uppercase text-muted">- Create beginning inventory -</span>
                    @else
                    <span class="d-block {{ $row['is_late'] ? 'text-danger' : '' }}" style="font-size: 10px;">{{ $row['duration'] }}</span>
                    @endif
                    <span class="d-block" style="font-size: 9px;">Promodiser(s): {{ $row['promodisers'] }}</span>
                </td>
            </tr>
        @empty
            <tr>
                <td class="text-center text-uppercase text-muted">No pending for submission of inventory audit found</td>
            </tr>
        @endforelse
    </tbody>
</table>