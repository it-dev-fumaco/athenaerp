<table class="table table-striped">
    <thead>
        <th class="text-center text-uppercase font-responsive p-2">Period</th>
        <th class="text-center text-uppercase font-responsive p-2">Store</th>
        <th class="text-center text-uppercase font-responsive p-2">Promodiser</th>
    </thead>
    <tbody>
        @forelse ($pending as $row)

            <tr>
                <td class="font-responsive">
                    @if (!$row['beginning_inventory_date'])
                    <span class="d-block text-uppercase text-muted">- Create beginning inventory -</span>
                    @else
                    <span class="d-block {{ $row['is_late'] ? 'text-danger' : '' }}">{{ $row['duration'] }}</span>
                    @endif
                    
                   </td>
                <td class="font-responsive text-center">{{ $row['store'] }}</td>
                <td class="font-responsive text-center">{{ $row['promodisers'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="text-center text-uppercase text-muted">No pending for submission of inventory audit found</td>
            </tr>
        @endforelse
    </tbody>
</table>