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
                                    <a href="/" class="btn btn-secondary m-0" style="width: 60px;"><i class="fas fa-arrow-left"></i></a>
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
                                        <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                                            {!! session()->get('error') !!}
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <form id="sales-report-form" action="/submit_monthly_sales_form" method="post">
                                @csrf
                                <h5 class="font-responsive font-weight-bold text-center m-1 text-uppercase d-block" id="branch-name">{{ $branch }}</h5>
                                <div class="d-none">
                                    <input type="text" name="branch" value="{{ $branch }}" readonly>
                                    <input type="text" name="year" value="{{ $year }}" readonly>
                                    <input type="text" name="month" value="{{ $month }}" readonly>
                                </div>
                                <table class="table table-striped" style="font-size: 9pt;">
                                    <col style="width: 40%">
                                    <col style="width: 60%">
                                    <tr>
                                        <th class="text-center p-2">Day</th>
                                        <th class="text-center p-2">Amount</th>
                                    </tr>
                                    @foreach($data_per_day as $day => $data)
                                    <tr>
                                        <td class="text-center">
                                            <b>{{ $month.'-'.$day }}</b> <br>
                                            <small class="text-muted">{{ Carbon\Carbon::parse($month.' '.$day.', '.$year)->format('l') }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="row">
                                                <div class="col-1 d-flex justify-content-center align-items-center">
                                                    <b>₱</b>
                                                </div>
                                                <div class="col-11">
                                                    <input type="text" class="form-control text-center price-format" name="day[{{ $day }}][amount]" value="{{ number_format($data['amount'], 2) }}" style="font-size: 9pt;" {{ $submitted ? 'disabled' : null }}>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </table>
                                <div class="row mb-3 p-2">
                                    <label style="font-size: 9pt;">Remarks</label>
                                    <textarea name="remarks" cols="30" rows="3" class="form-control" placeholder="Remarks..." style="font-size: 9pt;">
                                        {{ $report ? $report->remarks : null }}
                                    </textarea>
                                </div>
                                @if (!$submitted)
                                    <div class="row d-flex justify-content-center align-items-center p-2">
                                        <div class="col-12 mb-3 mx-auto">
                                            <button type="button" class="btn btn-secondary btn-sm w-100 save-form" data-draft=1>Save as Draft</button>
                                        </div>
                                        <div class="col-12 mx-auto">
                                            <button type="button" class="btn btn-primary btn-sm w-100 save-form" data-draft=0>Submit</button>
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
@endsection

@section('script')
    <script>
        $(document).ready(function(){
            $(document).on('click', '.save-form', function (e){
                e.preventDefault();
                $('input[name="draft"]').attr('checked', $(this).data('draft') ? true : false);
                $('#sales-report-form').submit();
            });

            const formatToCurrency = amount => {
                return amount.replace(/\d(?=(\d{3})+\.)/g, "$&,");
            };

            $(document).on('keyup', '.price-format', function (e){
                e.preventDefault();
                var val = $(this).val() ? $(this).val().replace(/[^\d.-]/g, '') : 0;
                $(this).val(parseFloat(val).toLocaleString(window.document.documentElement.lang));
            });
        });
    </script>
@endsection