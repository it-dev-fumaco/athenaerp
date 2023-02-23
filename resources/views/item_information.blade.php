@php
    $package_weight = $item_details->package_weight && $item_details->package_weight > 0 ? number_format($item_details->package_weight).' '.$item_details->weight_uom : '-';
    $package_length = $item_details->package_length && $item_details->package_length > 0 ? number_format($item_details->package_length).' '.$item_details->package_dimension_uom : '-';
    $package_width = $item_details->package_width && $item_details->package_width > 0 ? number_format($item_details->package_width).' '.$item_details->package_dimension_uom : '-';
    $package_height = $item_details->package_height && $item_details->package_height > 0 ? number_format($item_details->package_height).' '.$item_details->package_dimension_uom : '-';
    $weight_per_unit = $item_details->weight_per_unit && $item_details->weight_per_unit > 0 ? number_format($item_details->weight_per_unit).' '.$item_details->weight_uom : '-';
    $length = $item_details->length && $item_details->length > 0 ? number_format($item_details->length).' '.$item_details->package_dimension_uom : '-';
    $width = $item_details->width && $item_details->width > 0 ? number_format($item_details->width).' '.$item_details->package_dimension_uom : '-';
    $thickness = $item_details->thickness && $item_details->thickness > 0 ? number_format($item_details->thickness).' '.$item_details->package_dimension_uom : '-';
@endphp
<dl class="ml-3">
    <dt class="responsive-item-code" style="font-size: 14pt;">{{ $item_details->name.' '.$item_details->brand }}</dt>
    <dd class="responsive-description" style="font-size: 11pt;" class="text-justify mb-2">{!! $item_details->description !!}</dd>
</dl>
<dl class="ml-3">
    <dt style="font-size: 10pt;">Package Dimension</dt>
    <dd style="font-size: 9pt;" class="text-justify mb-2">
        Net Weight: {{ $package_weight }},
        Length: {{ $package_length }},
        Width: {{ $package_width }},
        Height: {{ $package_height }}
    </dd>

    <dt style="font-size: 10pt;">Product Dimension</dt>
    <dd style="font-size: 9pt;" class="text-justify mb-2">
        Weight: {{ $weight_per_unit }},
        Length: {{ $length }},
        Width: {{ $width }},
        Thickness: {{ $thickness }}
    </dd>
</dl>