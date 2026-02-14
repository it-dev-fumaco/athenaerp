<ul>
	@foreach(array_keys($groups) as $group)
    	@php
			$nextLevel = Arr::exists($all ?? [], $group) ? collect($all[$group])->groupBy('name') : [];
		@endphp
		<li style="margin-left: -10px">
            <span class="w-100 sub-item {{ request('group') == $group ? 'selected-tree-item' : 'tree-item' }}">
				<a style="font-size: 10pt; letter-spacing: -1px !important; color: inherit !important;" href="{!! request()->fullUrlWithQuery(['group' => $group]) !!}">
					<i class="far {{ $nextLevel ? 'fa-folder-open' : 'fa-file' }}"></i>&nbsp;{{ $group }}
				</a>
            </span>
			@if($nextLevel)
				@include('search_results_item_group_tree', ['all' => $all, 'groups' => $nextLevel->toArray(), 'current_lvl' => $currentLvl + 1, 'prev_obj' => $group])
			@endif
		</li>
	@endforeach
</ul>