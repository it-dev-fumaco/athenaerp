<span class="d-block m-2 font-weight-bold font-responsive text-center">{{ $store }}</span>
@forelse  ($list as $row)
<div class="d-flex flex-row border-top justify-content-between align-items-center">
    <div class="p-1 font-responsive ml-2">{{ \Carbon\Carbon::parse($row->cutoff_period_from)->format('F d, Y') }} - {{ \Carbon\Carbon::parse($row->cutoff_period_to)->format('F d, Y') }}</div>
    <div class="p-1 font-responsive">
        <a href="/view_inventory_audit_items/{{ $store }}/{{ $row->cutoff_period_from }}/{{ $row->cutoff_period_to }}" class="btn btn-info btn-sm" style="width: 70px;"><i class="fas fa-search"></i></a>
    </div>
</div>
@empty
<div class="d-block text-center font-responsive m-0 text-uppercase text-muted border-top border-bottom pb-2 pt-2">No record(s) found</div>
@endforelse