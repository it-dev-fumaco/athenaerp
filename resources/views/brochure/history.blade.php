<ul class="products-list product-list-in-card pl-2 pr-2">
    @forelse ($recentUploads as $recent)
    <li class="item pl-2 pr-2">
      <a href="/preview/{{ strtoupper($recent['project']) }}/{{ $recent['filename'] }}" class="d-block m-0 p-0">{{ $recent['project'] }}</a>
      <small class="text-muted" style="font-size: 12px;">Filename: {{ $recent['filename'] }}</small>
      <small class="d-block text-muted" style="font-size: 10px;">{{ $recent['created_by'] }} - {{ $recent['duration'] }}</small>
    </li>
    @empty
    <li class="item pl-2 pr-2">
      <span class="d-block text-center text-uppercase">No records found</span>
    </li>
    @endforelse
</ul>