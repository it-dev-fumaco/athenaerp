<div class="text-center pt-2">
    <h5>Brochure List</h5>
    <div class="overflow-auto" style="max-height: 240px;">
        @if (count($items) > 0)
            @foreach ($items as $item)
                <div class="card card-primary text-left p-0 m-1" style="font-size: 9pt;">
                    <div class="card-body p-2 m-0">
                        <b>{{ $item->item_code }}</b> - {{ \Illuminate\Support\Str::limit($item->item_name, 30, $end='...') }}
                    </div>
                </div>
            @endforeach
        @else
            <p class="p-2">No item(s) found.</p>
        @endif
    </div>
    @if (count($items) > 0)
        <div class="pt-3 pb-2 text-center">
            <a class="btn btn-sm btn-primary" href="/generate_multiple_brochures" style="font-size: 10pt;"><i class="fa fa-print"></i> Generate Brochure</a>
        </div>
    @endif
</div>
<script>
    $(document).ready(function (){
        $('.brochure-arr-count').text('{{ count($items) }}');
        @if (count($items) > 0)
            $('.brochures-icon').removeClass('d-none');
        @else
            $('.brochures-icon').addClass('d-none');
        @endif
    });
</script>