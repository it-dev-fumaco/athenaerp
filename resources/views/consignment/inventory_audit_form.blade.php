@extends('layout', [
    'namePage' => 'Inventory Audit Form',
    'activePage' => 'dashboard',
])

@section('content')
@php
// $input_values = [ // Uriah
//     'DB00007' => 1,
//     'DB00008' => 2,
//     'DB00010' => 2,
//     'DB00005' => 3,
//     'DB00006' => 2,
//     'DB00009' => 1,
//     'DB00003' => 4,

//     'BR00012' => 12,
//     'BR00003' => 29,
//     'BR00005' => 7,
//     'BR00007' => 0,
//     'BR00009' => 16,
//     'BR00010' => 9,

//     'HO02122' => 9,
//     'BT00797' => 28,
//     'BT00461' => 29,
//     'BT00153' => 0,
//     'BT00333' => 3,

//     'EB14196' => 10,

//     'ME00019' => 3,

//     'FG77208' => 3,
//     'FG77207' => 1,
//     'FG64287' => 4,
//     'FG64288' => 2,
//     'FG70811' => 1,
//     'FG71490' => 3,
//     'FG70818' => 2,
//     'FG71813' => 7,

//     'LT01509' => 2,
//     'LT01467' => 3,
//     'LT01513' => 5,
//     'LT01473' => 1,
//     'LT01475' => 5,
//     'LT01511' => 0,
//     'LT01515' => 5,
//     'LT01477' => 5,
// ];

// $input_values = [ // Melvin
//     'FG70811' => 0,
//     'FG71808' => 11,
//     'FG70861' => 44,
//     'FG71818' => 48,
//     'FG71084' => 5,
//     'FG70818' => 22,
//     'FG71811' => 19,
//     'FG70824' => 11,
//     'FG71813' => 12,
//     'FG71085' => 36,
//     'FG71823' => 36,
//     'FG71089' => 9,
//     'FG71826' => 9,
//     'FG70844' => 3,
//     'FG71816' => 5,
//     'LT01465' => 0,
//     'LT01513' => 0,
//     'LT01475' => 0,
//     'LT01515' => 0,
//     'LT01477' => 0,
//     'LT01516' => 0,
//     'HI00059' => 6,
//     'SA00278' => 6,
//     'SA00299' => 0,
//     'SA00280' => 8,
//     'SA00281' => 7,
//     'SA00282' => 10,
//     'SA00283' => 6,
//     'SA00279' => 6,
//     'SA00306' => 5,
//     'SA00301' => 11,
//     'SA00302' => 14,
//     'SA00303' => 11,
//     'SA00305' => 10,
//     'SA00304' => 9,
//     'DB00007' => 5,
//     'DB00008' => 6,
//     'DB00010' => 4,
//     'DB00005' => 10,
//     'DB00006' => 7,
//     'DB00009' => 5,
//     'DB00003' => 8,
//     'SE00092' => 8,
//     'SE00046' => 4,
//     'BR00012' => 36,
//     'BR00011' => 15,
//     'BR00003' => 90,
//     'BR00004' => 88,
//     'BR00005' => 77,
//     'BR00006' => 75,
//     'BR00007' => 47,
//     'BR00008' => 39,
//     'BR00009' => 0,
//     'BR00010' => 38,
//     'HO02122' => 6,
//     'HO01979' => 19,
//     'BT00916' => 0,
//     'TT00483' => 3,
//     'TT00466' => 8,
//     'TT00471' => 2,
//     'TT00473' => 5,
//     'EB14192' => 250,
//     'EB14196' => 150,
//     'EB14194' => 200,
//     'EB14195' => 265,
//     'ME00019' => 15,
//     'FG77209' => 10,
//     'FG77210' => 0,
//     'FG77207' => 16,
//     'FG77208' => 27,
//     'FG76183' => 5,
//     'FG65655' => 45,
//     'FG67538' => 41,
//     'FG63922' => 20,
//     'FG72000' => 9,
//     'FG72006' => 4,
//     'FG71995' => 4,
//     'FG63955' => 17,
//     'FG63707' => 10,
//     'FG68790' => 6,
//     'FG68791' => 14,
// ];

$input_values = [ // Eric Cayabyab
    'HO02122' => 24,
    'HO01979' => 16,
    'HO05157' => 1,
    'PA74779' => 5,
    'EB00009' => 67,
    'EB14195' => 159,
    'ME00019' => 34,
    'DB00009' => 5,
    'DB00003' => 11,
    'DB00004' => 6,
    'SE00092' => 20,
    'DB00007' => 0,
    'DB00008' => 0,
    'DB00010' => 8,
    'DB00005' => 1,
    'FG65655' => 63,
    'FG67538' => 44,
    'FG64457' => 43,
    'FG65713' => 26,
    'FG71818' => 0,
    'FG70434' => 19,
    'FG70216' => 0,
    'FG63904' => 2
];

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
                                    <a href="/inventory_audit" class="btn btn-secondary m-0" style="width: 60px;"><i class="fas fa-arrow-left"></i></a>
                                </div>
                                <div class="p-1 col-8">
                                    <span class="font-weight-bolder d-block font-responsive text-uppercase">Inventory Report Form</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-1">
                            <h6 class="font-weight-bold text-center m-1 text-uppercase" style="font-size: 10pt;">{{ $branch }}</h6>
                            <h5 class="text-center mt-1 font-weight-bolder font-responsive">{{ $duration }}</h5>
                            <div class="callout callout-info font-responsive text-center pr-3 pl-3 pb-3 pt-3 m-2" style="font-size: 10pt;">
                                <span class="d-block"><i class="fas fa-info-circle"></i> Instructions: Enter your current physical count of quantity per item as of <u>TODAY</u>.</span>
                            </div>
                            <form action="/submit_inventory_audit_form" method="POST" autocomplete="off" id="inventory-report-entry-form">
                                @csrf
                                <div id="input-values" class="d-none">
                                    <input type="text" name="transaction_date" value="{{ $transaction_date }}">
                                    <input type="text" name="branch_warehouse" value="{{ $branch }}">
                                    <input type="text" name="audit_date_from" value="{{ $inventory_audit_from }}">
                                    <input type="text" name="audit_date_to" value="{{ $transaction_date }}">
                                </div>
                                <div class="form-group m-2">
                                    <input type="text" class="form-control text-center mb-1 d-none" id="duration">
                                    <input type="text" class="form-control" placeholder="Search Items" id="search-filter">
                                </div>
                                <table class="table" style="font-size: 8pt;" id="items-table">
                                    <thead>
                                        <tr>
                                            <th class="text-center p-1" style="width: 35%;">ITEM CODE</th>
                                            <th class="text-center p-1" style="width: 30%;">CURRENT QTY</th>
                                            <th class="text-center p-1" style="width: 35%;">AUDIT QTY</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($item_classification as $class => $items)
                                            <tr>
                                                <td colspan="3" class="p-0">
                                                    <div class="bg-navy p-2">
                                                        <span style="font-weight: bold; font-size: 10pt;">{{ $class }}</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @forelse ($items as $row)
                                            @php
                                                $id = $row->item_code;
                                                $img = array_key_exists($row->item_code, $item_images) ? "/img/" . $item_images[$row->item_code][0]->image_path : "/icon/no_img.png";
                                                $img_webp = array_key_exists($row->item_code, $item_images) ? "/img/" . explode('.',$item_images[$row->item_code][0]->image_path)[0].'.webp' : "/icon/no_img.webp";
                                                $consigned_qty = array_key_exists($row->item_code, $consigned_stocks) ? ($consigned_stocks[$row->item_code] * 1) : 0;

                                                $img_count = array_key_exists($row->item_code, $item_images) ? count($item_images[$row->item_code]) : 0;

                                                $qty = null;
                                                if(session()->has('error')) {
                                                    $data = session()->get('old_data');
                                                    $qty = isset($data['items'][$row->item_code]['qty']) ? $data['items'][$row->item_code]['qty'] : 0;
                                                }
                                            @endphp
                                            <tr style="border-bottom: 0 !important;" class="item-row {{ (session()->has('error') && session()->has('item_codes') && in_array($row->item_code, session()->get('item_codes'))) ? 'bg-warning' : '' }}">
                                                <td class="text-justify p-1 align-middle" style="border-bottom: 10px !important;">
                                                    <div class="d-flex flex-row justify-content-start align-items-center">
                                                        <div class="p-1 text-left mx-auto">
                                                            <input type="hidden" name="item[{{ $row->item_code }}][description]" value="{!! strip_tags($row->description) !!}">
                                                            <a href="{{ asset('storage/') }}{{ $img }}" class="view-images" data-item-code="{{ $row->item_code }}">
                                                                <picture>
                                                                    <source srcset="{{ asset('storage'.$img_webp) }}" type="image/webp" alt="" width="40" height="40">
                                                                    <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg" alt="" width="40" height="40">
                                                                    <img src="{{ asset('storage'.$img) }}" alt="" width="40" height="40">
                                                                </picture>
                                                            </a>
                                                        </div>
                                                        <div class="p-1 m-0">
                                                            <span class="font-weight-bold">{{ $row->item_code }}</span>
                                                            <div class="d-none">{!! strip_tags($row->description) !!}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center p-1 align-middle font-weight-bold" style="border-bottom: 0 !important;">
                                                    <span class="d-block item-consigned-qty">{{ $consigned_qty }}</span>
                                                    <span class="d-none orig-item-consigned-qty">{{ $consigned_qty }}</span>
                                                </td>
                                                <td class="text-justify p-0 align-middle" style="border-bottom: 0 !important;">
                                                    <div class="d-flex flex-row justify-content-center align-items-center">
                                                        <div class="p-0">
                                                            <div class="input-group p-1 justify-content-center">
                                                                <div class="input-group-prepend p-0">
                                                                    <button class="btn btn-outline-danger btn-xs qtyminus" style="padding: 0 5px 0 5px;" type="button">-</button>
                                                                </div>
                                                                <div class="custom-a p-0">
                                                                    {{-- <input type="text" class="form-control form-control-sm qty item-audit-qty" name="item[{{ $row->item_code }}][qty]" style="text-align: center; width: 50px;" required id="{{ $row->item_code }}" value="{{ isset($input_values[$row->item_code]) ? $input_values[$row->item_code] : null }}"> --}}
                                                                    <input type="text" class="form-control form-control-sm qty item-audit-qty" name="item[{{ $row->item_code }}][qty]" style="text-align: center; width: 50px;" required id="{{ $row->item_code }}" value="{{ $consigned_qty }}">
                                                                </div>
                                                                <div class="input-group-append p-0">
                                                                    <button class="btn btn-outline-success btn-xs qtyplus" style="padding: 0 5px 0 5px;" type="button">+</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="{{ (session()->has('error') && session()->has('item_codes') && in_array($row->item_code, session()->get('item_codes'))) ? 'bg-warning' : '' }}">
                                                <td colspan="3" style="border-top: 0 !important;">
                                                    <span class="font-weight-bold d-none">{{ $row->item_code }}</span>
                                                    <div class="item-description">{!! strip_tags($row->description) !!}</div>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td class="text-center font-weight-bold text-uppercase text-muted" colspan="3">No item(s) found</td>
                                            </tr> 
                                            @endforelse
                                        @empty
                                        <tr>
                                            <td class="text-center font-weight-bold text-uppercase text-muted" colspan="3">No item(s) found</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                <div class="m-3">
                                    <button type="button" id="submit-form" class="btn btn-primary btn-block" {{ $item_count <= 0 ? 'disabled' : ''  }}><i class="fas fa-check"></i> SUBMIT</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

<div class="modal fade" id="confirmation-modal" tabindex="-1" role="dialog" aria-labelledby="instructions-modal" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-navy">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> CONFIRM INVENTORY AUDIT</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form></form>
                <p class="text-center mt-0">
                    <span class="d-block">Click <strong>"CONFIRM"</strong> to submit your current physical count of quantity per item as of <strong><u>{{ \Carbon\Carbon::parse($transaction_date)->format('F d, Y') }}</u></strong>.</span>
                </p>
                <div class="text-center mb-3 mt-2" style="font-size: 9pt;">
                    <span class="d-block font-weight-bolder mt-4">{{ $branch }}</span>
                    <small class="d-block">Branch / Store</small>
                </div>
                <div class="text-center mb-3 mt-2" style="font-size: 9pt;">
                    <span class="d-block font-weight-bolder mt-3 cutoff-period">{{ $duration }}</span>
                    <small class="d-block">Cut-off Period</small>
                </div>
                <div class="row pt-5">
                    <div class="col-6">
                        <button type="button" class="btn btn-primary btn-block" id="confirm-inventory-report-btn"><i class="fas fa-check"></i> CONFIRM</button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-secondary btn-block" data-dismiss="modal"><i class="fas fa-times"></i> CLOSE</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="instructions-modal" tabindex="-1" role="dialog" aria-labelledby="instructions-modal" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-navy">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> INSTRUCTIONS</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form></form>
                <p class="text-center mt-0">
                    <span class="d-block">Enter your current physical count of quantity per item as of <strong><u>{{ \Carbon\Carbon::parse($transaction_date)->format('F d, Y') }}</u></strong>.</span>
                </p>
                <div class="text-center mb-3 mt-3" style="font-size: 9pt;">
                    <span class="d-block font-weight-bolder mt-4">{{ $branch }}</span>
                    <small class="d-block">Branch / Store</small>
                </div>
                <div class="d-flex flex-row justify-content-center">
                    <div class="p-2">
                        <button type="button" class="btn btn-primary" data-dismiss="modal" aria-label="Close"><i class="fas fa-times"></i> CLOSE</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="success-modal" tabindex="-1" role="dialog" aria-labelledby="success-modalTitle" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <form></form>
                <div class="d-flex flex-row justify-content-end">
                    <div class="p-1">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                @if(session()->has('success'))
                <p class="text-success text-center mb-0" style="font-size: 5rem; margin-top: -40px;">
                    <i class="fas fa-check-circle"></i>
                </p>
                <p class="text-center text-uppercase mt-0 font-weight-bold">{{ session()->get('success') }}</p>
                <hr>
                <p class="text-center mb-0 mt-4 font-weight-bolder text-uppercase">Inventory Audit Report</p>
                <div class="text-center mb-2" style="font-size: 9pt;">
                    <span class="d-block font-weight-bold mt-3">{{ session()->get('branch') }}</span>
                    <small class="d-block">Branch / Store</small>
                    <span class="d-block font-weight-bold mt-3">
                        {{ session()->get('transaction_date') ? \Carbon\Carbon::parse(session()->get('transaction_date'))->format('F d, Y') : \Carbon\Carbon::now()->format('F d, Y') }}
                    </span>
                    <small class="d-block">Transaction Date</small>
                </div>
                <div class="d-flex flex-row justify-content-center">
                    <div class="p-2">
                        <a href="/inventory_audit" class="btn btn-secondary font-responsive"><i class="fas fa-list"></i> Return to List</a>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if(session()->has('error'))
<div class="modal fade" id="error-modal" tabindex="-1" role="dialog" aria-labelledby="notifications-modal-title" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-navy">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> WARNING</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="font-size: 10pt;">
                @if(session()->has('item_codes') && count(session()->get('item_codes')) > 0)
                    <p class="text-center mt-0">
                        <span class="d-block">Insufficient stocks for the following item(s):</span>
                    </p>
                    <div>
                        <ul>
                            @foreach (session()->get('item_codes') as $code)
                                <li>{{ $code }}</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="text-center">
                        <span class="d-block">{{ session()->get('error') }}.</span>
                    </div>
                @endif
                <div class="d-flex flex-row justify-content-center">
                    <div class="p-2">
                        <button type="button" class="btn btn-primary" data-dismiss="modal" aria-label="Close"><i class="fas fa-times"></i> CLOSE</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="notifications-modal" tabindex="-1" role="dialog" aria-labelledby="notifications-modal-title" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-navy">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> WARNING</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="font-size: 10pt;">
                <form></form>
                <p class="text-center mt-0">
                    <span class="d-block">Please enter physical count of quantity for the following item(s):</span>
                </p>
                <div id="inc-item-codes">
                    <ul></ul>
                </div>
                <div class="d-flex flex-row justify-content-center">
                    <div class="p-2">
                        <button type="button" class="btn btn-primary" data-dismiss="modal" aria-label="Close"><i class="fas fa-times"></i> CLOSE</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Chrome, Safari, Edge, Opera */
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    /* Firefox */
    input[type=number] {
        -moz-appearance: textfield;
    }
    .morectnt span {
        display: none;
    }
</style>
@endsection

@section('script')
<script>
    $(function () {
        @if (!session()->has('success') && !session()->has('error'))
        $('#instructions-modal').modal('show');
        @endif
        @if (session()->has('success'))
        $('#success-modal').modal('show');
        @endif

        @if (session()->has('error'))
        $('#error-modal').modal('show');
        @endif

        @php
            $explode_duration = explode(' - ', $duration);
            $startDate = isset($explode_duration[0]) ? $explode_duration[0] : Carbon\Carbon::now()->format('Y-M-d');
            $endDate = isset($explode_duration[1]) ? $explode_duration[1] : Carbon\Carbon::now()->format('Y-M-d');
        @endphp

        $("#duration").daterangepicker({
            placeholder: 'Select Duration',
            locale: {
                format: 'YYYY-MMM-DD',
                separator: " to "
            },
            startDate: '{{ Carbon\Carbon::parse($startDate)->format("Y-M-d") }}',
            endDate: '{{ Carbon\Carbon::parse($endDate)->format("Y-M-d") }}',
        });

        $("#duration").on('hide.daterangepicker', function (ev, picker) {
            var duration = picker.startDate.format('YYYY-MMM-DD') + ' to ' + picker.endDate.format('YYYY-MMM-DD');
            $(this).val(duration);

            $('#input-values input[name=audit_date_from]').val(picker.startDate.format('YYYY-MM-DD'));
            $('#input-values input[name=audit_date_to]').val(picker.endDate.format('YYYY-MM-DD'));

            $('#confirmation-modal .cutoff-period').text(picker.startDate.format('MMMM D, Y') + ' - ' + picker.endDate.format('MMMM D, Y'));
        });

        $("#duration").on('apply.daterangepicker', function (ev, picker) {
            var duration = picker.startDate.format('YYYY-MMM-DD') + ' to ' + picker.endDate.format('YYYY-MMM-DD');
            $(this).val(duration);

            $('#input-values input[name=audit_date_from]').val(picker.startDate.format('YYYY-MM-DD'));
            $('#input-values input[name=audit_date_to]').val(picker.endDate.format('YYYY-MM-DD'));

            $('#confirmation-modal .cutoff-period').text(picker.startDate.format('MMMM D, Y') + ' - ' + picker.endDate.format('MMMM D, Y'));
        });

        $("#duration").on('cancel.daterangepicker', function (ev, picker) {
            var duration = '{{ Carbon\Carbon::parse($inventory_audit_from)->addDays(1)->format("Y-M-d")." to ".Carbon\Carbon::parse($transaction_date)->format("Y-M-d") }}';
            $(this).val(duration);
            
            $('#input-values input[name=audit_date_from]').val('{{ $inventory_audit_from }}');
            $('#input-values input[name=audit_date_to]').val('{{ $transaction_date }}');
            
            $('#confirmation-modal .cutoff-period').text('{{ $duration }}');
        });

        const formatToCurrency = amount => {
            return "₱ " + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, "$&,");
        };

        $('#submit-form').click(function(e) {
            e.preventDefault();

            var empty_inputs = [];
            $('.item-audit-qty').each(function() {
                if (!$(this).val().replace(/,/g, '') || !$.isNumeric($(this).val().replace(/,/g, ''))) {
                    var id = $(this).attr('id');
                    empty_inputs.push(id);
                }
            });

            if(empty_inputs.length > 0) {
                $('#inc-item-codes ul').empty();
                $.each(empty_inputs, function(e, item){
                    $('#inc-item-codes ul').append('<li class="wrong-item-code">'+ item +'</li>');
                });

                $('#notifications-modal').modal('show');

                return false;
            }

            var form = $('#inventory-report-entry-form');
            var reportValidity = form[0].reportValidity();

            if(reportValidity){
                $('#confirmation-modal').modal('show');
            }
        });

        $('#confirm-inventory-report-btn').click(function(e){
            e.preventDefault();
            $(this).prop('disabled', true)
            $('#inventory-report-entry-form').submit();
        });

        $('.qtyplus').click(function(e){
            // Stop acting like a button
            e.preventDefault();
            // Get the field name
            var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
            // Get its current value
            var currentVal = parseInt(fieldName.val().replace(/,/g, ''));
            // get consigned qty
            var origConsigned = parseInt($(this).parents('tr').find('.orig-item-consigned-qty').eq(0).text());
            var consignedField = $(this).parents('tr').find('.item-consigned-qty').eq(0);
            // get sold qty
            var origSold = parseInt($(this).parents('tr').find('.orig-item-sold-qty').eq(0).text());
            var soldField = $(this).parents('tr').find('.item-sold-qty').eq(0);
            // If is not undefined
            if (!isNaN(currentVal)) {
                // Increment
                fieldName.val(currentVal + 1);
            } else {
                // Otherwise put a 0 there
                fieldName.val(0);
            }
            var new_sold_qty = 0;
            new_sold_qty = (origConsigned - fieldName.val());
            if (new_sold_qty > -1) {
                soldField.text(new_sold_qty + origSold);
            } else {
                soldField.text(0 + origSold);
            }
        });
        // This button will decrement the value till 0
        $(".qtyminus").click(function(e) {
            // Stop acting like a button
            e.preventDefault();
            // Get the field name
            var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
            // Get its current value
            var currentVal = parseInt(fieldName.val().replace(/,/g, ''));
             // get consigned qty
            var origConsigned = parseInt($(this).parents('tr').find('.orig-item-consigned-qty').eq(0).text());
            var consignedField = $(this).parents('tr').find('.item-consigned-qty').eq(0);
            // get sold qty
            var origSold = parseInt($(this).parents('tr').find('.orig-item-sold-qty').eq(0).text());
            var soldField = $(this).parents('tr').find('.item-sold-qty').eq(0);
            // If it isn't undefined or its greater than 0
            if (!isNaN(currentVal) && currentVal > 0) {
                // Decrement one
                fieldName.val(currentVal - 1);
            } else {
                // Otherwise put a 0 there
                fieldName.val(0);
            }
            var new_sold_qty = 0;
            new_sold_qty = (origConsigned- parseInt(fieldName.val()));
            if (new_sold_qty > -1) {
                soldField.text(new_sold_qty + origSold);
            } else {
                soldField.text(0 + origSold);
            }
        });

        $("#search-filter").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#items-table tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
        
        var showTotalChar = 98, showChar = "Show more", hideChar = "Show less";
        $('.item-description').each(function() {
            var content = $(this).text();
            if (content.length > showTotalChar) {
                var con = content.substr(0, showTotalChar);
                var hcon = content.substr(showTotalChar, content.length - showTotalChar);
                var txt = con + '<span class="dots">...</span><span class="morectnt"><span>' + hcon + '</span>&nbsp;&nbsp;<a href="#" class="showmoretxt">' + showChar + '</a></span>';
                $(this).html(txt);
            }
        });

        $(".showmoretxt").click(function(e) {
            e.preventDefault();
            if ($(this).hasClass("sample")) {
                $(this).removeClass("sample");
                $(this).text(showChar);
            } else {
                $(this).addClass("sample");
                $(this).text(hideChar);
            }

            $(this).parent().prev().toggle();
            $(this).prev().toggle();

            return false;
        });

        var input = document.querySelector('.price-input');
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9.\,]/, '');
        });
    });
</script>
@endsection