@extends('layout', [
    'namePage' => 'Sales Report',
    'activePage' => 'dashboard',
])

@section('content')
@php
    $submitted = $report && $report->status == 'Submitted' ? 1 : 0;
@endphp
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="card card-lightblue">
                        <div class="card-header text-center p-1">
                            <div class="d-flex flex-row align-items-center">
                                <div class="p-0 col-2 text-left">
                                    <a href="/sales_report_list/{{ $branch }}" class="btn btn-secondary m-0" style="width: 60px;"><i class="fas fa-arrow-left"></i></a>
                                </div>
                                <div class="p-1 col-8">
                                    <span class="font-weight-bolder d-block font-responsive text-uppercase">Monthly Sales Report</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-1">
                            @if(session()->has('error'))
                                <div class="row">
                                    <div class="col">
                                        <div class="alert alert-danger fade show text-center" role="alert">
                                            {!! session()->get('error') !!}
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(session()->has('success'))
                                <div class="row" style="font-size: 9pt;">
                                    <div class="col">
                                        <div class="alert alert-success fade show text-center" role="alert">
                                            {!! session()->get('success') !!}
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <form id="sales-report-form" action="/submit_monthly_sales_form" method="post">
                                @csrf
                                <h5 class="font-responsive font-weight-bold text-center m-2 text-uppercase d-block" id="branch-name">{{ $branch }}</h5>
                                @php
                                    $total_sales = $report ? $report->total_amount : 0;
                                @endphp
                                <p class="text-center p-0 m-0" style="font-size: 9pt">Total Sales for the month of <span class="font-weight-bold">{{ $month . ' ' . $year }}</span></p>
                                <span class="text-center d-block font-weight-bold mb-2" style="font-size: 14px;">₱ {{ number_format($total_sales, 2) }}</span>

                                @if ($report)
                                    @if ($report->status == 'Submitted')
                                    <h6 class="text-uppercase text-center"><span class="text-success" style="font-size: 20px;">●</span> Submitted</h6>
                                    @else
                                    <h6 class="text-uppercase text-center"><span class="text-danger" style="font-size: 20px;">●</span> Draft</h6>
                                    @endif
                                    @else
                                    <h6 class="text-uppercase text-center"><span class="text-secondary" style="font-size: 20px;">●</span> Pending</h6>
                                @endif
                                @if ($submitted)
                                @php
                                    $user = ucwords(str_replace('.', ' ', explode('@', $report->submitted_by)[0]));
                                @endphp
                                <small class="d-block mb-2 text-center font-italic" style="font-size: 10px;">Submitted by: {{ $user . ' - ' . \Carbon\Carbon::parse($report->date_submitted)->format('Y-m-d h:i A') }}</small>
                                @else
                                @if ($report)
                                @php
                                    $user = ucwords(str_replace('.', ' ', explode('@', $report->modified_by)[0]));
                                @endphp
                                <small class="d-block mb-2 text-center font-italic" style="font-size: 10px;">Last modified by: {{ $user . ' ' . \Carbon\Carbon::parse($report->modified)->format('Y-m-d h:i A') }}</small>
                                @endif
                                @endif
                                <div class="d-none">
                                    <input type="text" name="branch" value="{{ $branch }}" readonly>
                                    <input type="text" name="year" value="{{ $year }}" readonly>
                                    <input type="text" name="month" value="{{ $month }}" readonly>
                                </div>
                                <table class="table table-striped" style="font-size: 9pt;">
                                    <col style="width: 40%">
                                    <col style="width: 60%">
                                    <thead class="text-uppercase">
                                        <th class="text-center p-2">Day</th>
                                        <th class="text-center p-2">Amount</th>
                                    </thead>
                                    @foreach($data_per_day as $day => $data)
                                    <tr>
                                        <td class="text-center">
                                            <span class="d-block font-weight-bold">{{ $month.' '.$day }}</span>
                                            <small class="text-muted font-italic">{{ Carbon\Carbon::parse($month.' '.$day.', '.$year)->format('l') }}</small>
                                        </td>
                                        <td class="text-center align-middle">
                                            @if ($submitted)
                                                <b>₱ {{ number_format($data['amount'], 2) }}</b>
                                            @else
                                                <div class="row">
                                                    <div class="col-1 d-flex justify-content-center align-items-center">
                                                        <b>₱</b>
                                                    </div>
                                                    <div class="col-11">
                                                        <input type="number" pattern="[0-9]*" inputmode="numeric" class="form-control text-center amount" name="day[{{ $day }}][amount]" value="{{ $data['amount'] }}" style="font-size: 9pt;" {{ $submitted ? 'disabled' : null }}>
                                                    </div>
                                                </div> 
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </table>
                                <hr class="p-1 m-0">
                                <div class="row m-0 pr-2 pl-2">
                                    <label style="font-size: 9pt;">Remarks</label>
                                    <textarea name="remarks" cols="30" rows="3" class="form-control mb-3" placeholder="Remarks..." style="font-size: 9pt;">
                                        {{ $report ? $report->remarks : null }}
                                    </textarea>
                                </div>
                                @if (!$submitted)
                                    <div class="row d-flex justify-content-center align-items-center p-2">
                                        <div class="col-12 mb-3 mx-auto">
                                            <button type="button" class="btn btn-secondary w-100 save-form" data-draft=1><i class="fas fa-pencil-alt"></i> Save as Draft</button>
                                        </div>
                                        <div class="col-12 mx-auto">
                                            <button type="button" class="btn btn-primary w-100 save-form" data-draft=0><i class="fas fa-check"></i> Submit</button>
                                        </div>
                                        <input type="checkbox" name="draft" class="d-none">
                                    </div>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

<div class="modal" tabindex="-1" id="submit-warning" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
            <h6 class="modal-title">Are you sure you want to submit?</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <p></p>
            <div class="alert alert-warning text-center p-3" style="font-size: 9pt;">
                <b><i class="fa fa-warning"></i> Reminder: Once submitted, sales report cannot be edited.</b>
            </div>
            <div class="container mt-2 text-center">
                <h6><b>{{ $branch }}</b></h6>
                <p>Sales Report for {{ $month.'-'.$year }}</p>
                <p>Total Sales: <b id="total-sales"></b></p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary submit-form"><i class="fa fa-check"></i> Submit</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-remove"></i> Close</button>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('style')
    <style>
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        }

        input[type=number] {
        -moz-appearance: textfield;
        }
    </style>
@endsection

@section('script')
    <script>
        $(document).ready(function(){
            $(document).on('click', '.save-form', function (e){
                e.preventDefault();

                if($(this).data('draft')){
                    $('input[name="draft"]').attr('checked', true);
                    $('#sales-report-form').submit();
                }else{
                    $('input[name="draft"]').attr('checked', false);

                    var amount = 0;
                    $('.amount').each(function (i, e){
                        amount += parseFloat($(this).val());
                    });

                    $('#total-sales').text('₱ ' + amount.toLocaleString(window.document.documentElement.lang));
                    $('#submit-warning').modal('show');
                }
            });

            $(document).on('click', '.submit-form', function (e){
                e.preventDefault();
                $('#sales-report-form').submit();
            });
        });
    </script>
@endsection