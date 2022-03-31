@extends('layout', [
    'namePage' => 'Dashboard',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header pt-0">
		<div class="container-fluid">
			<div class="row pt-3">
				<div class="col-sm-12">
                    <div class="card card-secondary card-outline">
                        <div class="card-header d-flex p-0">
                            <ul class="nav nav-pills p-2">
                                @foreach ($assigned_consignment_store as $n => $store)
                                <li class="nav-item"><a class="font-responsive nav-link {{ $loop->first ? 'active' : '' }}" href="#tab{{ $n }}" data-toggle="tab">{{ $store }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="card-body p-0">
                            <div class="tab-content">
                                @foreach ($assigned_consignment_store as $m => $store)
                                <div class="tab-pane font-responsive {{ $loop->first ? 'active' : '' }}" id="tab{{ $m }}">
                                    {{-- {{ $store }} --}}
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

@section('script')

<script>

</script>

@endsection