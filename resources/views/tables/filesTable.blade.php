<table class="table table-sm border">
    <tbody>
        @forelse ($item_files as $file)
        <tr>
            <td class="h4 align-middle"><i class="fas fa-file"></i></td>
            <td>
                <a href="{{ asset('storage/'.$file->file_path) }}" target="_blank" class="text-xs">{{ $file->file_name }}</a>
                <span class="d-block font-italic text-muted" style="font-size: 10px;">By: {{ ucwords(str_replace('.', ' ', explode('@', $file->owner)[0])) }} - {{ \Carbon\Carbon::parse($file->creation)->format('M d, Y h:i A') }}
</span>
            </td>
            <td class="align-middle">
                <span class="text-xs text-muted">{{ $file->file_size }}</span>
            </td>
            <td class="align-middle text-right">
                <a href="/download/{{ $file->file_path }}" target="_blank" class="btn btn-outline-info btn-xs"><i class="fas fa-download"></i></a>

                <button class="btn btn-xs btn-outline-danger delete-file-btn" data-file-name="{{ $file->file_name }}"  data-id="{{ $file->name }}">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="text-center text-muted p-4 text-xs">No files uploaded.</td>
            </tr>
        @endforelse
    </tbody>
</table>
    