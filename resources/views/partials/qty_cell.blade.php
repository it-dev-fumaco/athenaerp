{{--
    Warehouse / stock quantity cell with null guard.

    @param mixed $qty Display quantity (null or '' => em-dash placeholder)
    @param string|null $uom Unit of measure
    @param string $style plain | muted | badge
    @param bool $multiline Two-line layout (number then UOM)
    @param int|null $decimals If set, format with number_format (else integer-style trim)
    @param float|int|null $reorderLevel For badgeMode=reorder (warning when qty > 0 and <= level)
    @param string $badgeMode reorder | binary (binary: success if colorSource > 0)
    @param mixed|null $colorQty Optional; for binary badges when color differs from display qty
    @param string|null $badgeFontSize Inline font-size for badge (e.g. 14px, 10pt)
    @param string|null $badgeExtraClass Extra classes on badge span
    @param bool $badgeWrapUomInSmall When true (default), UOM is wrapped in <small> inside badge (search results). When false, "qty UOM" plain in badge (item stock level).
    @param bool $dashWhenZero Treat numeric 0 like missing (em-dash). Exception: binary badge with explicit colorQty &gt; 0 still shows display qty 0 (consignment: actual 0, available &gt; 0).
--}}
@php
    $style = $style ?? 'plain';
    $multiline = $multiline ?? false;
    $decimals = $decimals ?? null;
    $badgeMode = $badgeMode ?? 'reorder';
    $badgeWrapUomInSmall = $badgeWrapUomInSmall ?? true;
    $dashWhenZero = $dashWhenZero ?? false;
    $uom = $uom ?? '';
    $isMissing = is_null($qty ?? null) || $qty === '';
    if (! $isMissing && $dashWhenZero && (float) $qty == 0) {
        $hasExplicitColorQty = array_key_exists('colorQty', get_defined_vars());
        $colorQtyPositive = $hasExplicitColorQty && ! is_null($colorQty) && $colorQty !== '' && (float) $colorQty > 0;
        if (! $colorQtyPositive) {
            $isMissing = true;
        }
    }
@endphp
@if ($isMissing)
    <span class="text-muted">&mdash;</span>
@else
    @php
        $n = (float) $qty;
        $formatted = $decimals !== null ? number_format($n, (int) $decimals, '.', '') : (string) ($n * 1);
        if ($style === 'badge' && $badgeMode === 'binary') {
            // Optional `colorQty` for badge color when it differs from display `qty` (e.g. consignment modal). If the key
            // was not passed at all, use display qty ($n). If passed but null/empty, treat as unknown → secondary.
            $hasExplicitColorQty = array_key_exists('colorQty', get_defined_vars());
            if ($hasExplicitColorQty && ! is_null($colorQty) && $colorQty !== '') {
                $colorSource = (float) $colorQty;
            } elseif ($hasExplicitColorQty) {
                $colorSource = null;
            } else {
                $colorSource = $n;
            }
            $cls = ($colorSource !== null && $colorSource > 0) ? 'success' : 'secondary';
        } elseif ($style === 'badge') {
            $cls = $n == 0 ? 'secondary' : (($n <= ($reorderLevel ?? 0)) ? 'warning' : 'success');
        } else {
            $cls = null;
        }
        $badgeInlineStyle = ($badgeFontSize ?? null)
            ? 'font-size: '.$badgeFontSize.'; margin: 0 auto;'
            : 'font-size: 14px; margin: 0 auto;';
    @endphp
    @if ($style === 'badge')
        <span class="badge badge-{{ $cls }} {{ $badgeExtraClass ?? '' }}" style="{{ $badgeInlineStyle }}">
            @if ($multiline)
                {!! $formatted !!}<br>@if ($badgeWrapUomInSmall)<small>{{ $uom }}</small>@else{{ $uom }}@endif
            @elseif ($badgeWrapUomInSmall)
                {{ $formatted }} <small>{{ $uom }}</small>
            @else
                {{ $formatted }} {{ $uom }}
            @endif
        </span>
    @elseif ($style === 'muted')
        <small class="text-muted">
            @if ($multiline)
                {!! $formatted !!}<br/>{{ $uom }}
            @else
                {{ $formatted }} {{ $uom }}
            @endif
        </small>
    @else
        @if ($multiline)
            {{ $formatted }} <br/> {{ $uom }}
        @else
            {{ $formatted }} {{ $uom }}
        @endif
    @endif
@endif
