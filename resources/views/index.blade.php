@extends('layout', [
    'namePage' => 'Dashboard',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content bg-white content--dashboard">
    <div class="content-header pt-0">
        <div class="dashboard-box">
            <div
                id="dashboard-app"
                data-user-group="{{ e(Auth::user()->user_group ?? '') }}"
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
