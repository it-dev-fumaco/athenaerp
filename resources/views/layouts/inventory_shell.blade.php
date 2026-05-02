@extends('layout', [
    'namePage' => $namePage ?? 'Inventory',
    'activePage' => $activePage ?? 'dashboard',
])

@section('content')
<div
    class="inventory-shell flex w-full min-h-0 min-w-0 bg-slate-50"
    data-inventory-shell
>
    <div class="inventory-shell__sidebar-host">
        @include('partials.inventory_sidebar')
    </div>
    <div class="inventory-shell__main flex min-h-0 min-w-0 flex-1 flex-col overflow-auto">
        <div class="inventory-shell__mobile-bar">
            <button
                type="button"
                class="inventory-shell__mobile-menu-btn"
                data-mobile-nav-toggle
                aria-controls="inventory-sidebar"
                aria-expanded="false"
                aria-label="Open menu"
            >
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>
            <span class="inventory-shell__mobile-bar-title">Menu</span>
        </div>
        @yield('inventory_main')
    </div>
    <div
        class="inventory-shell__nav-backdrop"
        data-mobile-nav-backdrop
        aria-hidden="true"
    ></div>
</div>
@endsection
