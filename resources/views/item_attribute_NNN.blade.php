@extends('layout', [
    'namePage' => 'ERPInventory',
    'activePage' => 'dashboard',
])

@section('content')
    <div class="container-fluid">
        <div class="text-center" style="margin: 0 auto !important;">
            <h3>Attribute Updated</h3>
        </div>
    </div>
    <script type="text/javascript">
        window.onload = function() {
            setTimeout(function() {
                window.location = "/item_attribute";
            }, 0000);
        };
    </script>

@endsection

@section('script')

@endsection