<table class="table">
    <tbody>
        @forelse ($list as $item)
        <tr>
            <td class="text-justify">
                <span class="font-weight-bold">{{ $item->item_code }}</span>
                <small class="d-block font-italic">{{ str_limit($item->description, $limit = 130, $end = '...') }}</small>
            </td>
        </tr>
        @empty
        <tr>
            <td>No Record(s) found.</td>
        </tr>
        @endforelse       
    </tbody>
</table>