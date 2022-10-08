@extends('layout', [
    'namePage' => 'Consignment Stock Movement',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="row">
                        <div class="col-2">
                            <div style="margin-bottom: -43px;">
                                @php
                                    $redirecthref = Auth::user()->user_group == 'Director' ? '/consignment_dashboard' : '/';
                                @endphp
                                <a href="{{ $redirecthref }}" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i></a>
                            </div>
                        </div>
                        <div class="col-8 col-lg-8 p-0">
                            <h4 class="text-center font-weight-bold m-2 text-uppercase">Consignment Stock Movement</h4>
                        </div>
                    </div>
                    <div class="card card-secondary card-outline">
                        <div class="card-body p-2">
                            <form action="#" method="GET">
                                <div class="row p-2">
                                    <div class="col-4">
                                        <select class="form-control filters-font" id="consignment-store-select"></select>
                                    </div>
                                    <div class="col-4">
                                        <select class="form-control filters-font" id="item-select"></select>
                                    </div>
                                    <div class="col-2">
                                        <button type="submit" class="btn btn-primary filters-font btn-block btn-sm"><i class="fas fa-search"></i> Search</button>
                                    </div>
                                </div>
                            </form>
                            <div id="consignment-ledger-content"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

<style>
    .myFont{
        font-size:9pt;
    }
    .select2{
        width: 100% !important;
        outline: none !important;
        font-size: 9pt;
    }
    .select2-selection__rendered {
        line-height: 25px !important;
        outline: none !important;
    }
    .select2-container .select2-selection--single {
        height: 29px !important;
        padding-top: 1.5%;
        outline: none !important;
    }
    .select2-selection__arrow {
        height: 28px !important;
    }
</style>
@endsection

@section('script')
<script>
    $(document).ready(function() {
        load();
        function load() {
            var branch_warehouse = $('#consignment-store-select').val();
            var item_code = $('#item-select').val();
            $.ajax({
                type: "GET",
                url: "/consignment_ledger",
                data: {branch_warehouse, item_code},
                success: function (response) {
                    $('#consignment-ledger-content').html(response);
                }
            });
        }

        $('form').submit(function (e) {
            e.preventDefault();
            load();
        });

        $('#consignment-store-select').select2({
            dropdownCssClass: "myFont",
            placeholder: "Select Store",
            ajax: {
                url: '/consignment_stores',
                method: 'GET',
                dataType: 'json',
                data: function (data) {
                    return {
                        q: data.term // search term
                    };
                },
                processResults: function (response) {
                    return {
                        results: response
                    };
                },
                cache: true
            }
        });

        $('#item-select').select2({
            dropdownCssClass: "myFont",
            placeholder: "Select Item Code",
            ajax: {
                url: '/get_item_list',
                method: 'GET',
                dataType: 'json',
                data: function (data) {
                    return {
                        q: data.term // search term
                    };
                },
                processResults: function (response) {
                    return {
                        results: response
                    };
                },
                cache: true
            }
        });   
    });
</script>
@endsection