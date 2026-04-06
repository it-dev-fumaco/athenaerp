@extends('layouts.inventory_shell', [
    'namePage' => 'Dashboard',
    'activePage' => 'dashboard',
])

@section('inventory_main')
<div class="content content--dashboard border-0 bg-transparent shadow-none">
    <div class="content-header pt-0">
        <div class="dashboard-box">
            <div
                id="dashboard-app"
                data-user-group="{{ e(Auth::user()->user_group ?? '') }}"
                data-initial-tab="{{ e(request('tab', 'home')) }}"
            >
                <noscript>
                    <p class="p-4 text-center text-muted">Please enable JavaScript to use the dashboard.</p>
                </noscript>
                <div id="dashboard-app-fallback" class="p-4 text-center text-muted">
                    <p>Loading dashboard…</p>
                    <p class="small">If this does not load, try updating Safari or using a supported browser.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
