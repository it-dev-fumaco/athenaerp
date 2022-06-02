@extends('layout', [
    'namePage' => 'Success',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="card card-success card-outline">
                        <div class="card-body p-1">
                            @if(session()->has('success'))
                            <p class="text-success text-center mb-0" style="font-size: 10rem;">
                                <i class="fas fa-check-circle"></i>
                            </p>
                            <p class="text-center text-uppercase mt-0 font-weight-bold">{{ session()->get('success') }}</p>

                            <div class="text-center mb-4">
                                <a href="/" class="btn bg-lightblue"><i class="fas fa-home"></i> Homepage</a>
                            </div>
                            @else
                            <script>window.location = "/";</script>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>
@endsection

@section('script')
@endsection