@extends('layout', [
    'namePage' => 'Sales Report',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="card card-lightblue">
                        <div class="card-header text-center p-1">
                            <div class="d-flex flex-row align-items-center">
                                <div class="p-0 col-2 text-left">
                                    <a href="/" class="btn btn-secondary m-0" style="width: 60px;"><i class="fas fa-arrow-left"></i></a>
                                </div>
                                <div class="p-1 col-8">
                                    <span class="font-weight-bolder d-block font-responsive text-uppercase">Monthly Sales Report</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-1">
                            <h5 class="font-responsive font-weight-bold text-center m-1 text-uppercase d-block" id="branch-name">{{ $branch }}</h5>
                            <div class="row mt-3" style="font-size: 13px;">
                                <div class="col-6 offset-1 text-right">
                                    <span class="d-inline-block mt-2 mb-2 font-weight-bold">Sales Report for the year: </span>
                                </div>
                                <div class="col-4">
                                    <select class="form-control" id="sales-report-year">
                                        @foreach ($years as $year)
                                        <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @if(session()->has('success'))
                                <div class="row" style="font-size: 9pt;">
                                    <div class="col">
                                        <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
                                            {{ session()->has('success') }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div id="sales-report-table" class="mt-2"></div>
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
  $(document).ready(function (){
    $('#sales-report-year').change(function() {
        load_sales_report();
    });
      
    load_sales_report();
    function load_sales_report(){
      $.ajax({
        type: 'GET',
        url: '/sales_report_list/' + $('#branch-name').text(),
        data: {year: $('#sales-report-year').val()},
        success: function(response){
            $('#sales-report-table').html(response);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            showNotification("danger", 'Error in getting records.', "fa fa-info");
        }
      });
    }
  });
</script>
@endsection