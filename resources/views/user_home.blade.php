@extends('layouts.inventory_shell', [
    'namePage' => 'User Home',
    'activePage' => 'user_home',
])

@section('inventory_main')
<div id="user-home-app"></div>
<div class="modal fade" id="ste-modal" tabindex="-1" role="dialog" aria-hidden="true"></div>
<div class="modal fade" id="ps-modal" tabindex="-1" role="dialog" aria-hidden="true"></div>
@endsection
