@extends('layout', [
    'namePage' => 'Damage Report',
    'activePage' => 'beginning_inventory',
])

@section('content')
    <div class="content">
        <div class="content-header p-0">
            <div class="container">
                <div class="row pt-1">
                    <div class="col-md-12 p-0 m-0">
                        <div class="card card-secondary card-outline">
                            <div class="card-header text-center" id="report">
                                @if(session()->has('success'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        {{ session()->get('success') }}
                                    </div>
                                @endif
                                @if(session()->has('error'))
                                    <div class="alert alert-danger alert-dismissible fade show font-responsive" role="alert">
                                        {{ session()->get('error') }}
                                    </div>
                                @endif
                                <span class="font-responsive font-weight-bold text-uppercase d-inline-block">Damage Report</span>
                                <h5 class="text-center mt-1 font-weight-bolder">{{ \Carbon\Carbon::now()->format('F d, Y') }}</h5>
                            </div>
                            <div class="card-body p-0">
                                <form action="/promodiser/damage_report/submit" method="post">
                                    @csrf
                                    <div class="d-none">
                                        <input type="text" id="item-code" name="item_code" value="">
                                        <input type="text" id="description" name="description" value="">
                                        <input type="text" id="transaction_date" name="transaction_date" value="">
                                    </div>
                                    <div class="container">
                                        <label for="branch" style="font-size: 10pt;">Select a Branch</label>
                                        <select name="branch" id="branch" class="form-control">
                                            <option value="" disabled selected>Select a Branch</option>
                                            @foreach ($assigned_consignment_store as $store)
                                                <option value="{{ $store }}">{{ $store }}</option>
                                            @endforeach
                                        </select>
                                        <br>
                                        <div class="w-100 d-none" id="select-an-item">
                                            <label for="selected-item" style="font-size: 10pt;">Select Damaged Item</label>
                                            <select id="item-selection" class="form-control" placeholder="Select an item"></select>
                                        </div>
                                        <div class="w-100 d-none" id="items-container">
                                            <div class="d-flex flex-row justify-content-center align-items-center mt-3">
                                                <div class="p-1 col-2 text-center">
                                                    <a href="" id="link" data-toggle="lightbox" data-gallery="" data-title="">
                                                        <picture>
                                                            <source srcset="" id="webp-src" type="image/webp">
                                                            <source srcset="" id="img-src" type="image/jpeg">
                                                            <img src="" alt="" id="img-display" class="img-thumbnail" alt="User Image" width="40" height="40">
                                                        </picture>
                                                    </a>
                                                </div>
                                                <div class="p-1 col m-0">
                                                    <span class="font-weight-bold" id="item-code-display" style="font-size: 10pt" ></span>
                                                </div>
                                                <div class="p-1 col-5">
                                                    <div class="input-group p-1 justify-content-center">
                                                        <div class="input-group-prepend p-0">
                                                            <button class="btn btn-outline-danger btn-xs qtyminus" style="padding: 0 5px 0 5px;" type="button">-</button>
                                                        </div>
                                                        <div class="custom-a p-0">
                                                            <input type="number" class="form-control form-control-sm qty" id="qty" value="0" name="qty" style="text-align: center; width: 80px;" data-max="">
                                                        </div>
                                                        <div class="input-group-append p-0">
                                                            <button class="btn btn-outline-success btn-xs qtyplus" style="padding: 0 5px 0 5px;" type="button">+</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="p-1 col">
                                                    <div class="input-group p-1">
                                                        <div class="p-0">
                                                            <span id="price" style="font-size: 10pt"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-row">
                                                <div class="p-1 text-justify">
                                                    <div class="item-description" id="description-display" style="font-size: 9.5pt"></div>
                                                </div>
                                            </div>

                                            <label for="damage_description" style="font-size: 10pt;">Damage</label>
                                            <textarea name="damage_description" cols="30" rows="3" class="form-control" placeholder="Describe the damage..." style="font-size: 10pt;" required></textarea>
                                            <br>
                                            <button type="submit" class="btn btn-primary float-right" id="submit-btn" disabled>Submit</button>
                                        </div> 
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
    <style>
        table {
            table-layout: fixed;
            width: 100%;   
        }
        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
@endsection

@section('script')
    <script>
        $(document).ready(function (){
            $('#branch').change(function (){
                get_items($(this).val());
                $('#select-an-item').removeClass('d-none');
                $('#items-container').addClass('d-none');
                $('#qty').val(0);
            });

            $('#qty').keyup(function () {
                qty_checker();
            });

            function qty_checker(){
                if(parseInt($('#qty').val()) > 0 && parseInt($('#qty').val()) <= parseInt($('#qty').data('max')) ){
                    $('#submit-btn').prop('disabled', false);
                    $('#qty').css('border', '1px solid #CED4DA');
                }else{
                    $('#submit-btn').prop('disabled', true);
                    $('#qty').css('border', '1px solid red');
                }
            }

            function get_items(branch){
				$('#item-selection').select2({
                    placeholder: 'Select an item',
                    ajax: {
                        url: '/beginning_inv/get_received_items/' + branch,
                        method: 'GET',
                        dataType: 'json',
                        data: function (data) {
                            return {
                                q: data.term // search term
                            };
                        },
                        processResults: function (response) {
                            return {
                                results:response
                            };
                        },
                        cache: true
                    }
                });
            }

            $(document).on('select2:select', '#item-selection', function(e){
                $('#item-code-display').text(e.params.data.id); // item code
                $('#description-display').text(e.params.data.description); // description
                $('#img-src').attr('src', e.params.data.img); // image

                $('#link').attr('href', e.params.data.img); // link
                $('#link').data('gallery', e.params.data.id); // link
                $('#link').data('title', e.params.data.id); // link

                $('#webp-src').attr('src', e.params.data.webp); // webp
                $('#img-display').attr('src', e.params.data.img); // image
                $('#price').text(e.params.data.price); // price
                $('#qty').data('max', e.params.data.max); // webp

                // hidden values
                $('#item-code').val(e.params.data.id);
                $('#description').val(e.params.data.description);
                $('#transaction_date').val(e.params.data.transaction_date);
                
                $('#items-container').removeClass('d-none');
                $('#qty').val(0);
            });

            $('.qtyplus').click(function(e){
                // Stop acting like a button
                e.preventDefault();
                // Get the field name
                var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
                // get max value
                var max = fieldName.data('max');
                // Get its current value
                var currentVal = parseInt(fieldName.val());
                // If is not undefined
                if (!isNaN(currentVal)) {
                    // Increment
                    if (currentVal < max) {
                        fieldName.val(currentVal + 1);
                    }
                } else {
                    // Otherwise put a 0 there
                    fieldName.val(0);
                }
                qty_checker();
            });
            // This button will decrement the value till 0
            $(".qtyminus").click(function(e) {
                // Stop acting like a button
                e.preventDefault();
                // Get the field name
                var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
                // Get its current value
                var currentVal = parseInt(fieldName.val());
                // If it isn't undefined or its greater than 0
                if (!isNaN(currentVal) && currentVal > 0) {
                    // Decrement one
                    fieldName.val(currentVal - 1);
                } else {
                    // Otherwise put a 0 there
                    fieldName.val(0);
                }
                qty_checker();
            });
        });
    </script>
@endsection