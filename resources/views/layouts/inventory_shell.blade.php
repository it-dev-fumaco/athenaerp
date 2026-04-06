@extends('layout', [
    'namePage' => $namePage ?? 'Inventory',
    'activePage' => $activePage ?? 'dashboard',
])

@section('content')
<div
    class="inventory-shell flex w-full min-h-0 min-w-0 bg-slate-50"
    data-inventory-shell
>
    @include('partials.inventory_sidebar')
    <div class="inventory-shell__main flex min-h-0 min-w-0 flex-1 flex-col overflow-auto">
        @yield('inventory_main')
    </div>
</div>
@endsection
