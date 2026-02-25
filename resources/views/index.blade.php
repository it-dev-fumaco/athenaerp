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
            ></div>
        </div>
    </div>
</div>
@endsection
