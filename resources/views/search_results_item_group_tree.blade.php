<ul>
	@foreach(array_keys($groups) as $group)
    	@php
			$next_level = isset($all[$group]) ? collect($all[$group])->groupBy('name') : [];
		@endphp
		<li>
            <span class="w-100">
				<a style="color: #000; font-size: 10pt; {{ request('group') == $group ? 'text-decoration: underline;' : null }}" href="{!! $next_level ? request()->fullUrlWithQuery(['group' => $group]) : request()->fullUrlWithQuery(['searchString' => null, 'group' => $group, 'wh' => null, 'classification' => null]) !!}">
                	<i class="far {{ $next_level ? 'fa-folder-open' : 'fa-file' }}"></i>&nbsp;{{ $group }}
				</a>
            </span>
			@if($next_level)
				@include('search_results_item_group_tree', ['all' => $all, 'groups' => $next_level->toArray(), 'current_lvl' => $current_lvl + 1, 'prev_obj' => $group])
			@endif
		</li>
	@endforeach
</ul>