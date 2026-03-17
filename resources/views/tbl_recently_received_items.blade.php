@php
    $noImgUrl = Storage::disk('upcloud')->url('icon/no-img.png');
@endphp
<div class="contaner text-right p-2">
    Total: <span class="badge badge-info">{{ $list->total() }}</span>
</div>
<table class="table-bordered w-100" style="font-size: 10pt;">
    <tr>
        <th class="text-center p-3" style="width: 15%;">Reference</th>
        <th class="text-center p-3" style="width: 50%;">Item</th>
        <th class="text-center p-3">Warehouse</th>
        <th class="text-center p-3">Qty</th>
    </tr>
    @forelse ($list as $item)
        @php
            if ($item->image) {
                $file = $item->image;
            } elseif (isset($itemImage[$item->item_code])) {
                $file = $itemImage[$item->item_code][0]->image_path;
            } else {
                $file = null;
            }

            $disk = Storage::disk('upcloud');
            $resolvedKey = null;

            if ($file) {
                $base = pathinfo($file, PATHINFO_FILENAME);

                // Prefer WebP where available, then original jpeg/png in common folders.
                $candidates = [
                    "items/{$base}.webp",
                    "img/{$base}.webp",
                    "img/{$base}.webp",
                    "items/{$file}",
                    "img/{$file}",
                    "img/{$file}",
                ];

                foreach ($candidates as $candidate) {
                    if ($disk->exists($candidate)) {
                        $resolvedKey = $candidate;
                        break;
                    }
                }
            }

            $finalImgUrl = $resolvedKey ? $disk->url($resolvedKey) : $noImgUrl;
        @endphp
        <tr>
            <td>
                <div class="container text-center">
                    <b>{{ $item->name }}</b><br>
                    <span style="font-size: 9pt;">
                        {{ Carbon\Carbon::parse($item->posting_date)->format('M d, Y') }} <br>
                        {{ Carbon\Carbon::parse($item->posting_time)->format('h:i A') }}
                    </span>
                </div>
            </td>
            <td>
                <div class="row">
                    <div class="col-2" style="display: flex; justify-content: center; align-items: center;">
                        <img src="{{ $finalImgUrl }}" class="img w-100" alt="" style="object-fit: contain;" onerror="this.onerror=null; this.src='{{ $noImgUrl }}';">
                    </div>
                    <div class="col-10">
                        <b>{{ $item->item_code }}</b><br>
                        {!! substr(strip_tags($item->description), 0, 70) !!}...
                    </div>
                </div>
            </td>
            <td>
                <div class="container text-center">
                    {{ $item->warehouse }}
                </div>
            </td>
            <td>
                <div class="container text-center">
                    <b>{{ $item->qty * 1 }}</b><br>{{ $item->uom }}
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan=4 class="text-center p-2">
                <span>No result(s) found.</span>
            </td>
        </tr>
    @endforelse
</table>
<div class="card-footer clearfix" style="font-size: 10pt;">
	{{ $list->links() }}
</div>