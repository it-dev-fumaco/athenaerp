<table class="table table-striped table-hover border" style="font-size: 13px;">
    <thead>
        <th class="p-2 text-center col-1">No.</th>
        <th class="p-2 text-center col-8">Attribute Value</th>
        <th class="p-2 text-center col-3">Action</th>
    </thead>
    <tbody>
        @forelse ($data as $row)
        <tr>
            <td class="p-2 text-center">
                <small class="text-muted">{{ $row->idx }}</small>
            </td>
            <td class="p-2">{{ $row->attribute_value }}</td>
            <td class="p-2 text-center"><a href="#" data-name="{{ $row->attribute_value }}" class="select-attribute-value-btn">Select</a></td>
        </tr>
        @empty
        <tr>
            <td colspan="3" class="text-center text-muted">No attribute values found.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="d-flex justify-content-center mx-2" id="attribute-values-pagination">{{ $data->onEachSide(1)->links() }}</div>