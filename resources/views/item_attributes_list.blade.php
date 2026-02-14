<table class="table table-bordered table-striped" style="font-size: 12px;">
    <thead>
        <th class="text-center">No.</th>
        <th class="text-center">Attribute Name</th>
        <th class="text-center">Attribute Value</th>
        <th class="text-center">Action</th>
    </thead>
    <tbody>
        @forelse ($itemAttributes as $attribute)
        <tr>
            <td class="text-center align-middle p-2">{{ $attribute->idx }}</td>
            <td class="align-middle p-2">{{ $attribute->attribute }}</td>
            <td class="align-middle p-2">{{ $attribute->attribute_value }}</td>
            <td class="text-center align-middle p-2">
                <button class="btn btn-xs btn-outline-secondary px-2 py-1 edit-attribute-btn" data-toggle="modal" data-target="#selectAttributeModal" data-attribute-name="{{ $attribute->attribute }}" data-attribute-id="{{ $attribute->name }}">
                    <i class="far fa-edit"></i>
                </button>
                <button class="btn btn-xs btn-outline-danger px-2 py-1 delete-attribute-btn" data-toggle="modal" data-target="#deleteAttributeModal" data-attribute-name="{{ $attribute->attribute }}"  data-attribute-id="{{ $attribute->name }}" data-attribute-value="{{ $attribute->attribute_value }}">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="text-center align-middle">No item attribute found for <b>{{ $itemCode }}</b>.</td>
        </tr>
        @endforelse
    </tbody>
</table>