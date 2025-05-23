@php
    $action = $inv_name ? "/update_beginning_inventory/$inv_name" : '/save_beginning_inventory';
@endphp

<form action='{{ $action }}' id='beginning-inventory-form' method="post" class="text-center {{ $branch != 'none' ? null : 'd-none' }}">
    @csrf
    <div class="row">
        <div class="col-8">
            <input type="text" class="form-control form-control-sm mt-2 mb-2 ml-0 mr-0" id="item-search" name="search" autocomplete="off" placeholder="Search"/>
        </div>
        <div class="col-4" style="display: flex; justify-content: center; align-items: center;">
            <button type="button" class="btn btn-primary btn-sm" id="open-add-items-modal" style="font-size: 10pt;">
                <i class="fa fa-plus"></i> Add Items
            </button>
              
              <!-- Modal -->
            <div class="modal fade" id="add-item-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header" style="background-color: #001F3F; color: #fff;">
                            <div class="row text-left">
                                <div class="col-12">
                                    <h6>Beginning Inventory Entry</h6>
                                </div>
                                <div class="col-12">
                                    <h5>Add an item</h5>
                                </div>
                            </div>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff;">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <select id="item-selection" class="form-control"></select>
                            <div class="row mt-3 d-none" id="item-to-add">
                                <table class="table table-striped" id="new-item-table">
                                    <thead>
                                        <th class="font-responsive text-center p-1 align-middle" style="width: 42%">Item Code</th>
                                        <th class="font-responsive text-center p-1 align-middle">Opening Stock</th>
                                        <th class="font-responsive text-center p-1 align-middle">Price</th>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-justify p-1 align-middle" colspan="3">
                                                <div class="d-flex flex-row justify-content-center align-items-center">
                                                    <div class="p-1 col-2 text-center">
                                                        <img src="" alt="" id="new-img" class="img-thumbna1il" alt="User Image" width="40" height="40">
                                                    </div>
                                                    <div class="p-1 col m-0">
                                                        <span class="font-weight-bold font-responsive"><span id="new-item-code"></span></span>
                                                    </div>
                                                    <div class="p-0 col-4">
                                                        <div class="input-group p-1 ml-3">
                                                            <div class="input-group-prepend p-0">
                                                                <button class="btn btn-outline-danger btn-xs new-item-qtyminus" style="padding: 0 5px 0 5px;" type="button">-</button>
                                                            </div>
                                                            <div class="custom-a p-0">
                                                                <input type="text" class="form-control form-control-sm qty new-item-validate new-item-stock" id="new-item-stock" value="0" style="text-align: center; width: 47px">
                                                            </div>
                                                            <div class="input-group-append p-0">
                                                                <button class="btn btn-outline-success btn-xs new-item-qtyplus" style="padding: 0 5px 0 5px;" type="button">+</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="p-1 col">
                                                        <div class="input-group p-1">
                                                            <div class="p-0">
                                                                <input type="text" class="form-control form-control-sm qty new-item-validate new-itemprice" id="new-item-price" value="" placeholder="0" style="text-align: center;">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="p-1" style="font-size: 9.5pt !important;">
                                                    <span class="font-italic" id="new-classification"></span> <br>
                                                    <span id="new-description"></span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="d-none">
                                    <span id="new-img-txt"></span>
                                    <span id="new-webp-txt"></span>
                                    <span id="new-alt-txt"></span>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" id="add-item-btn" class="btn btn-primary w-100 d-none">Add item</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div id="item-codes-with-beginning-inventory" class='d-none'>
        @foreach ($inv_items as $item)
            <span class="{{ $item }}">{{ $item }}</span>
        @endforeach
    </div>
    <div class="m-2">
        @php
            if($inv_name) {
                $transaction_date = $detail ? \Carbon\Carbon::parse($detail->transaction_date)->format('Y-m-d') : \Carbon\Carbon::now()->format('Y-m-d');
            } else {
                $transaction_date = \Carbon\Carbon::now()->format('Y-m-d');
            }
        @endphp
        <label for="transaction-date" style="font-size: 16px;">Transaction Date</label>
        <input type="date" id="transaction-date" name="transaction_date" class="form-control validate" value="{{ $transaction_date }}" required>
    </div>
    <table class="table table-striped text-left" id="items-table"> 
        <thead>
            <th class="font-responsive text-center p-1 align-middle" style="width: 38%">Item Code</th>
            <th class="font-responsive text-center p-1 align-middle">Opening Stock</th>
            <th class="font-responsive text-center p-1 align-middle">Price</th>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr id="{{ $item['item_code'] }}" class="{{ $item['item_code'] }}">
                    @php
                        $img = isset($item_images[$item['item_code']]) ? $item_images[$item['item_code']] : $item_images['no_img'];
                    @endphp 
                    <td class="text-justify p-1 align-middle" colspan="3">
                        <input type="text" name="item_code[]" id="{{ $item['item_code'] }}-id" class="d-none" value="{{ $item['item_code'] }}" />
                        <div class="d-flex flex-row justify-content-center align-items-center">
                            <div class="p-1 col-2 text-center">
                                <a href="{{ $img }}" class="view-images" data-item-code="{{ $item['item_code'] }}">
                                    <img src="{{ $img }}" alt="{{ Illuminate\Support\Str::slug(strip_tags($item['item_description']), '-') }}" width="40" height="40">
                                </a>
                            </div>
                            <div class="p-1 col m-0">
                                <span class="font-weight-bold font-responsive">{{ $item['item_code'] }}</span>
                            </div>
                            <div class="p-0 col-4">
                                <div class="input-group p-1 ml-3">
                                    <div class="input-group-prepend p-0">
                                        <button class="btn btn-outline-danger btn-xs qtyminus" style="padding: 0 5px 0 5px;" type="button">-</button>
                                    </div>
                                    <div class="custom-a p-0">
                                        <input type="text" class="form-control form-control-sm qty validate stock" id="{{ $item['item_code'] }}-stock" value="{{ $item['opening_stock'] }}" data-item-code="{{ $item['item_code'] }}" name="opening_stock[{{ $item['item_code'] }}]" style="text-align: center; width: 47px">
                                    </div>
                                    <div class="input-group-append p-0">
                                        <button class="btn btn-outline-success btn-xs qtyplus" style="padding: 0 5px 0 5px;" type="button">+</button>
                                    </div>
                                </div>
                            </div>
                            <div class="p-1 col">
                                <div class="input-group p-1">
                                    <div class="p-0">
                                        <input type="text" class="form-control form-control-sm qty validate price" id="{{ $item['item_code'] }}-price" data-item-code="{{ $item['item_code'] }}" placeholder="{{ $item['price'] }}" value="{{ $inv_name ? $item['price'] : 0 }}" name="price[{{ $item['item_code'] }}]" style="text-align: center;">
                                    </div>
                                </div>
                            </div>
                            <div class="p-1 col-1 text-center h-100 font-responsive remove-item" style="width: 15px !important; color: red; cursor: pointer" data-id="{{ $item['item_code'] }}"><i class="fa fa-remove"></i></div>
                        </div>
                        <div class="p-1 item-description" style="font-size: 9.5pt !important;">
                            {!! strip_tags($item['item_description']) !!}
                        </div>
                    </td>
                </tr>
            @empty
                <tr id="placeholder">
                    <td class="text-center font-responsive" colspan="3">
                        No item(s) available.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="col-12 text-left">
        <label style="font-size: 9pt;">Remarks</label>
        <textarea name="remarks" rows="5" class="form-control" placeholder='Remarks' id="remarks" style="font-size: 9pt;">{{ $remarks }}</textarea>
        <br>
    </div>
    
    <div class="col-12 text-right">
        <span class="d-block" style="font-size: 15px;">Total items: <b><span id="item-count">{{ count($items) }}</span></b></span>
        <div class="m-2">
            @if ($inv_name)
                <button type="button" class="btn btn-primary btn-block submit-once" id="submit-btn"><i class="fas fa-check"></i> UPDATE</button>

                <button type="button" class="btn btn-secondary btn-block submit-once" data-toggle="modal" data-target="#cancel-beginning-inventory-modal"><i class="fas fa-remove"></i> CANCEL</button>

                <div class="modal fade" id="cancel-beginning-inventory-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog text-center" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-navy">
                                <h5 class="modal-title" id="exampleModalLabel">Cancel Beginning Inventory</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to cancel Beginning Inventory Entry: <b>{{ $inv_name }}</b>?
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <a href="/cancel_beginning_inventory/{{ $inv_name }}" class="btn btn-primary">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <button type="button" class="btn btn-primary btn-block submit-once" id="submit-btn"><i id="submit-logo" class="fas fa-check"></i> SUBMIT</button>
            @endif
            <input type="checkbox" class='d-none' name="cancel" id="cancel-check" {{ $inv_name ? 'checked' : null }}>
        </div>
    </div>

    <div class="d-none">
        {{-- values to save --}}
        <input type="text" id="branch-warehouse" name="branch" value="{{ $branch }}">
        <input type="text" name="inv_name" value="{{ $inv_name }}">
    </div>

    <div class="w-100 text-center d-none p-2" id="add-item-success" style="position: absolute; top: 0; left: 0">
        <div class="callout callout-danger font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">New item added.</div>
    </div>

    <div class="modal fade" id="inputErrorModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-navy">
                    <h6 class="modal-title" id="exampleModalLabel">Warning</h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true" style="color: #fff;">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid text-justify" id="input-error-container" style="font-size: 8pt;">
                        <span><i class="fas fa-info-circle"></i> Please remove item(s) with zero(0) stocks and/or price:</span> <br>
                        <span>You can remove item(s) by clicking (<i class="fa fa-remove" style='color: red;'></i>)</span>
                        <div id="inc-item-codes">
                            <ul></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<style>
    .morectnt span {
        display: none;
    }
</style>
<script>
    $(document).ready(function(){
        var branch = '{{ $branch }}';
        var existing_record = '{{ $inv_name ? 1 : 0 }}';

        $(document).on('change', '#transaction-date', function(e){
            if(existing_record == 1){
                enable_submit();
            }

            validate_submit();
        });

        $(document).on('keyup', '.validate', function(){            
            if(existing_record == 1){
                enable_submit();
            }

            validate_submit();
        });

        function valid_branch(){
            if($('#branch-warehouse').val() == '' || $('#branch-warehouse').val() == 'null'){
                $('#null-store-warning').removeClass('d-none');
                $('#selected-branch').css('border', '1px solid red');
            }else{
                $('#null-store-warning').addClass('d-none');
                $('#selected-branch').css('border', '1px solid #CED4DA');
            }
        }
        if(branch != 'null' && branch != ''){
            valid_branch();
        }

        $('#remarks').keyup(function(){
            if(existing_record == 1){
                enable_submit();
            }
            validate_submit();
        });

        $("#open-add-items-modal").click(function (){
            if($('#branch-warehouse').val() == '' || $('#branch-warehouse').val() == 'null'){
                $('#null-store-warning').removeClass('d-none');
                $('#selected-branch').css('border', '1px solid red');
            }else{
                $('#null-store-warning').addClass('d-none');
                $('#selected-branch').css('border', '1px solid #CED4DA');
                $('#add-item-Modal').modal('show');
            }
        });

        var item_codes = new Array();
        var incorrect_item_codes = new Array();
        $('#submit-btn').click(function (){
            $('.wrong-item-code').remove();
            item_codes = [];
            incorrect_item_codes = [];

            $('.validate.stock').each(function(){ // check stocks
                var item_code = $(this).data('item-code');
                var stock_value = parseInt($(this).val().replace(/,/g, ''));
                var val = 0;

                if($(this).val() != ''){
                    if($.isNumeric($(this).val().replace(/,/g, ''))){
                        if(stock_value > 0){ // push in array if user puts value in stocks
                            item_codes.push(item_code);
                        }
                    }else{
                        incorrect_item_codes.push(item_code);
                    }
                }
            });

            $('.validate.price').each(function(){ // check price
                var item_code = $(this).data('item-code');
                var price = parseInt($(this).val().replace(/,/g, ''));

                if($(this).val() != ''){
                    if($.isNumeric($(this).val().replace(/,/g, ''))){
                        if(price > 0){ // push in array if user puts value in stocks
                            item_codes.push(item_code);
                        }
                    }else{
                        incorrect_item_codes.push(item_code);
                    }
                }
            });

            $.each(item_codes, function (e, item){ // validate stocks and price
                if($.isNumeric($('#'+item+'-price').val().replace(/,/g, '')) && $.isNumeric($('#'+item+'-stock').val().replace(/,/g, ''))){
                    var stock = parseInt($('#'+item+'-stock').val().replace(/,/g, ''));
                    var price = parseInt($('#'+item+'-price').val().replace(/,/g, ''));
                    
                    if(price > 0 && stock > 0){
                        incorrect_item_codes = jQuery.grep(incorrect_item_codes, function(value) {
                            return value != item;
                        });
                    }else{
                        incorrect_item_codes.push(item);
                    }
                }else{
                    incorrect_item_codes.push(item);
                }
            });

            incorrect_item_codes = incorrect_item_codes.filter(function(element,index,self){
                return index === self.indexOf(element); 
            });

            if(incorrect_item_codes.length > 0){
                $.each(incorrect_item_codes, function(e, item){
                    $('#inc-item-codes ul').append('<li class="wrong-item-code">'+ item +'</li>');
                });

                $('#inputErrorModal').modal('show');
            }else{
                $('#beginning-inventory-form').submit();
            }
        });


        validate_submit();
        function validate_submit(){
            if(parseInt($('#item-count').text()) > 0){
                $('#submit-btn').prop('disabled', false);
            }else{
                $('#submit-btn').prop('disabled', true);
            }
        }

        function enable_submit(){
            $('#cancel-check').prop('checked',  false);
            $('#submit-btn').text('UPDATE');
            $('#submit-btn').removeClass('btn-danger').addClass('btn-info');
            $('#submit-logo').removeClass('fa-remove').addClass('fa-check');
        }

        $('table#items-table').on('click', '.remove-item', function(){
            var item_code = $(this).data('id');
            $('#'+item_code).remove();

            $('#item-count').text(parseInt($('#item-count').text()) - 1);

            items_array = jQuery.grep(items_array, function(value) {
                return value != item_code;
            });

            if(existing_record == 1){
                enable_submit();
            }
            validate_submit();
        });
        $('table#items-table').on('keyup', '.stock', function(e){
            if(existing_record == 1){
                enable_submit();
            }
            validate_submit();
        });

        $('table#items-table').on('keyup', '.price', function(e){

            if(existing_record == 1){
                enable_submit();
            }
            validate_submit();
        });

        $('table#items-table').on('click', '.qtyplus', function(e){
            // Stop acting like a button
            e.preventDefault();
            // Get the field name
            var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
            var item_code = fieldName.data('item-code');
            // Get its current value
            var currentVal = parseInt(fieldName.val());
            // If is not undefined
            if (!isNaN(currentVal)) {
                // Increment
                fieldName.val(currentVal + 1);
            } else {
                // Otherwise put a 0 there
                fieldName.val(0);
            }

            if(existing_record == 1){
                enable_submit();
            }
            validate_submit();
        });

        // This button will decrement the value till 0
        $('table#items-table').on('click', '.qtyminus', function(e){
            // Stop acting like a button
            e.preventDefault();
            // Get the field name
            var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
            var item_code = fieldName.data('item-code');
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

            if(existing_record == 1){
                enable_submit();
            }
            validate_submit();
        });

        $("#item-search").on("keyup", function() {
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
                var txt = con + '<span class="dots">...</span><span class="morectnt"><span>' + hcon + '</span>&nbsp;&nbsp;<a href="#" class="show-more">' + showChar + '</a></span>';
                $(this).html(txt);
            }
        });

        $(".show-more").click(function(e) {
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

        $('#item-selection').select2({
            templateResult: formatState,
            // templateSelection: formatState,
            placeholder: 'Select an Item',

            ajax: {
                url: '/get_items/{{ $branch }}',
                method: 'GET',
                dataType: 'json',
                data: function (data) {
                    return {
                        q: data.term, // search term
                        excluded_items: items_array
                    };
                },
                processResults: function (response) {
                    return {
                        results: response.items
                    };
                },
                cache: true
            }
        });

        function formatState (opt) {
            if (!opt.id) {
                return opt.text;
            }

            var optimage = opt.image;

            if(!optimage){
                return opt.text;
            } else {
                var $opt = $(
                '<span style="font-size: 10pt;"><img src="' + optimage + '" width="40px" /> ' + opt.text + '</span>'
                );
                return $opt;
            }
        };

        $(document).on('select2:select', '#item-selection', function(e){
            $('#new-item-code').text(e.params.data.id); // item code
            $('#new-description').text(e.params.data.description); // description
            $('#new-classification').text(e.params.data.classification); // classification
            $('#new-img').attr('src', e.params.data.image); // image
            $('#new-img-txt').text(e.params.data.image); // image

            $('#new-alt-txt').text(e.params.data.alt); // alt text

            $('#add-item-btn').removeClass('d-none');
            $('#item-to-add').removeClass('d-none');
        });

        $('#add-item-btn').click(function (){
            add_item('#items-table tbody');
            $('#add-item-Modal').modal('hide')

            // Reset values
            $('#new-item-code').text('');
            $('#new-description').text('');
            $('#new-classification').text('');
            $('#new-img').attr('src', '');

            $('#new-img-txt').text('');
            $('#new-webp-txt').text('');
            $('#new-alt-txt').text('');

            $('#new-item-stock').val(0);
            $('#new-item-price').val('');

            $('#new-item-stock').prop('required', false);
            $('#new-item-price').prop('required', false);

            $('#add-item-btn').addClass('d-none');
            $('#item-to-add').addClass('d-none');

            $('#add-item-success').removeClass('d-none');
            $('#add-item-success').fadeOut(3000, 'linear', 'complete');

            validate_submit();
        });

        var items_array = new Array();
        function add_item(table){
            var item_code = $('#new-item-code').text();
            var description = $('#new-description').text();
            var classification = $('#new-classification').text();
            var image = $('#new-img-txt').text();
            var webp = $('#new-webp-txt').text();
            var alt = $('#new-alt-txt').text();

            var stock = $('#new-item-stock').val();
            var price = $('#new-item-price').val();
            
            var existing = $('#items-table').find('.' + item_code).eq(0).length;
            var existing_in_inventory = $('#item-codes-with-beginning-inventory').find('.' + item_code).eq(0).length;

            if (existing) {
                showNotification("warning", 'Item <b>' + item_code + '</b> already exists in the list.', "fa fa-info");
                $("#item-selection").empty().trigger('change');
                return false;
            }else if(existing_in_inventory){
                showNotification("warning", 'Beginning Inventory for Item <b>' + item_code + '</b> already exists.', "fa fa-info");
                $("#item-selection").empty().trigger('change');
                return false;
            }

			var row = '<tr id="' + item_code + '" class="' + item_code + '">' +
                '<td class="text-justify p-1 align-middle" colspan="3">' +
                    '<input type="text" name="item_code[]" id="' + item_code + '-id" class="d-none" value="' + item_code + '" />' +
                    '<div class="d-flex flex-row justify-content-center align-items-center">' +
                        '<div class="p-1 col-2 text-center">' +
                            '<img src="' + image + '" alt="' + alt + '" class="img-thumbna1il" alt="User Image" width="40" height="40">' +
                        '</div>' +
                        '<div class="p-1 col m-0">' +
                            '<span class="font-weight-bold font-responsive">' + item_code + '</span>' +
                        '</div>' +
                        '<div class="p-0 col-4">' +
                            '<div class="input-group p-1 ml-3">' +
                                '<div class="input-group-prepend p-0">' +
                                    '<button class="btn btn-outline-danger btn-xs qtyminus" style="padding: 0 5px 0 5px;" type="button">-</button>' +
                                '</div>' +
                                '<div class="custom-a p-0">' +
                                    '<input type="text" class="form-control form-control-sm qty validate stock" id="' + item_code + '-stock" value="' + stock + '" data-item-code="' + item_code + '" name="opening_stock[' + item_code + ']" style="text-align: center; width: 47px">' +
                                '</div>' +
                                '<div class="input-group-append p-0">' +
                                    '<button class="btn btn-outline-success btn-xs qtyplus" style="padding: 0 5px 0 5px;" type="button">+</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="p-1 col">' +
                            '<div class="input-group p-1">' +
                                '<div class="p-0">' +
                                    '<input type="text" class="form-control form-control-sm qty validate price" id="' + item_code + '-price" data-item-code="' + item_code + '" placeholder="0" value="' + price + '" name="price[' + item_code + ']" style="text-align: center;">' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="p-1 font-responsive remove-item" style="width: 15px !important; color: red; cursor: pointer" data-id="' + item_code + '"><i class="fa fa-remove"></i></div>' +
                    '</div>' +
                    '<div class="p-1 item-description" style="font-size: 9.5pt !important;">' +
                        description
                    '</div>' +
                '</td>' +
            '</tr>';

			$(table).prepend(row);
            $('#placeholder').addClass('d-none');

            if(jQuery.inArray(item_code, items_array) === -1){
                items_array.push(item_code);
            }
            
            if(existing_record == 1){
                enable_submit();
            }

            $("#item-selection").empty().trigger('change');
            $('#item-count').text(parseInt($('#item-count').text()) + 1);

            validate_submit();
		}

        // separate controls for 'Add Item' modal 
        // to prevent events from firing multiple times e.g., Clicking '+' on stocks adds 2 instead of 1
        $('table#new-item-table').on('keyup', '.new-item-validate', function(){
            if(existing_record == 1){
                enable_submit();
            }

            validate_submit();
        });

        $('.new-item-qtyplus').click(function(e){
            // Stop acting like a button
            e.preventDefault();
            // Get the field name
            var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
            var item_code = fieldName.data('item-code');
            // Get its current value
            var currentVal = parseInt(fieldName.val());
            // If is not undefined
            if (!isNaN(currentVal)) {
                // Increment
                fieldName.val(currentVal + 1);
            } else {
                // Otherwise put a 0 there
                fieldName.val(0);
            }

            if(existing_record == 1){
                enable_submit();
            }

            validate_submit();
        });

        // This button will decrement the value till 0
        $('.new-item-qtyminus').click(function(e){
            // Stop acting like a button
            e.preventDefault();
            // Get the field name
            var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
            var item_code = fieldName.data('item-code');
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

            if(existing_record == 1){
                enable_submit();
            }

            validate_submit();
        });

        function showNotification(color, message, icon){
            $.notify({
                icon: icon,
                message: message
            },{
                type: color,
                timer: 500,
                z_index: 1060,
                placement: {
                    from: 'top',
                    align: 'center'
                }
            });
        }
    });
</script>