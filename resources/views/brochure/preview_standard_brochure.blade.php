@extends('layout', [
    'namePage' => 'Item Profile',
    'activePage' => 'item_profile',
])

@section('content')
    <div class="container-fluid p-0 m-0">
        <div class="row m-0 p-0">
            <div class="col-md-12 p-0 m-0">



        <style>
       
            *:not(i):not(.fa){
                font-family: 'Poppins' !important;
            }
           

            #top-btn-container{
                position: fixed;
                right: 25px;
                top: 60px;
            }

            
            #top-left-btn-container {
                position: fixed;
                left: 25px;
                top: 70px;
            }

           

            .btn-ctrl{
                background-color: #f1f1f1;
                width: 50px;
                height: 50px;
                margin: 10px;
                padding: 5px 15px 5px 15px;
                border-radius: 50%;
                font-size: 20px;
                border: none;
                cursor: pointer;
                box-shadow: 0 0 10px rgba(0,0,0,0.25), 0 0 10px rgba(0,0,0,0.22);
                color: #446eac;
            }
            .btn-ctrl:hover{
                background-color:   #2980b9  ;
                transition: .4s;
                color: #f1f1f1;
            }
            .btn-ctrl:focus{
                outline: none !important;
                -webkit-box-shadow: none !important;
                box-shadow: 0 0 10px rgba(0,0,0,0.25), 0 0 10px rgba(0,0,0,0.22) !important;
            }
            .upload-image-placeholder {
                border: 2px dashed #d5d8dc;
                width: 230px;
                height: 230px;
                border-radius: 13px;
                display: block;
            }
            .upload-btn-wrapper {
                position: relative;
                overflow: hidden;
                display: block;
                width: 230px;
                height: 230px;
                top: 0;
                left: 0;
            }
            .custom-upload-btn {
                border: 1px solid transparent;
                width: 100%;
                height: 100%;
                text-align: center;
                background-color: transparent;
                cursor: pointer;
            }
            .upload-btn-wrapper i {
                display: block;
                font-size: 100px;
                color: #d5d8dc;
                opacity: 50%;
                margin-top: 40px !important;
            }
            .upload-btn-wrapper span {
                display: block;
                font-size: 18px;
                color:  #abb2b9 ;
            }
            .upload-btn-wrapper input[type=file] {
                font-size: 200px;
                position: absolute;
                left: 0;
                top: 0;
                cursor: pointer;
                opacity: 0;
            }
            .upload-image-placeholder.dragover{
                border-color:  #abb2b9;
                color:  #abb2b9;
            }
            .img-cont {
                position: relative;
                width: 230px;
            }
            .custom-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0);
                transition: background 0.5s ease;
            }
            .img-cont:hover .custom-overlay {
                display: block;
                background: rgba(196, 196, 196, 0.385);
            }
            .custom-hover-button {
                position: absolute;
                right: 10px;
                top: 10px;
                opacity: 0;
                transition: opacity .35s ease;
            }
            .custom-hover-button button {
                padding: 5px 8px !important;
                font-size: 12px;
            }
            .img-cont:hover .custom-hover-button {
                opacity: 1;
            }

            @media screen {
                .page-container * {
                    z-index: 0;
                    margin: 0 !important; padding: 0 !important;
                }
                .page-container{
                    padding: 15px 0 15px 0 !important;
                    background: #E6E6E6;
                }
                .pdf-page {
                    margin: 0 auto;
                    box-sizing: border-box;
                    box-shadow: 0 5px 10px 0 rgba(0,0,0,.3);
                    color: #333;
                    position: relative;
                    background-color: #fff;
                }
                .pdf-footer {
                    position: absolute;
                    bottom: .5in;
                    height: .8in;
                    left: .5in;
                    right: .5in;
                    padding-top: 10px;
                    border-top: 2px solid  #1c2833;
                    text-align: left;
                    color: #1b1a1a;
                    font-size: .6rem;
                }
                .pdf-footer-company-logo {
                    position: absolute;
                    left: .2in;
                    top: .15in;
                }
                .pdf-footer-company-website {
                    position: absolute;
                    left: 1.95in;
                    top: 2px;
                }
                .pdf-footer-contacts {
                    position: absolute;
                    right: 15px;
                    top: 2px;
                    /* font-weight: 500; */
                    line-height: 13px;
                }
                .pdf-body {
                    position: absolute;
                    top: .5in;
                    bottom: .8in;
                    left: .5in;
                    right: .5in;
                }
                .pdf-content {
                    position: absolute;
                    top: .5in;
                    bottom: .5in;
                    left: .5in;
                    right: .5in;
                    border: 2px solid #1c2833;
                }
                .size-a4 { width: 8.3in; height: 11.7in; }
                .size-letter { width: 8.5in; height: 11in; }
                .size-executive { width: 7.25in; height: 10.5in; }
                .d-print-none{
                    display: block;
                }
                .d-print, .print-page{
                    display: none;
                }
            }
            @media print {
                * { margin: 0 !important; padding: 0 !important; }
                .d-print-none{
                    display: none;
                }
                .d-print{
                    display: block;
                }
                .print-page {
                    display: block;
                    width: 12.3in !important;
                    height: 18.1in !important;
                }
                .header{
                    text-transform: uppercase;
                }
                .print-container{
                    border: 2px solid #1C2833;
                    padding: 46px !important;
                }
                .left-container{
                    display: inline-block;
                    width: 44%;
                    float: left;
                }
                .right-container{
                    display: inline-block;
                    width: 54%;
                    float: left;
                }
            }
        </style>
    </head>
<body>

<div id="top-btn-container">
    <button class="btn-ctrl" id="print-btn" style="display: block;"><i class="fas fa-print"></i></button>
</div>

<div id="top-left-btn-container">
    <a href="get_item_details/{{ $data['item_code'] }}" style="padding: 8px 10px 8px 10px !important; border-radius: 5px; background-color: #f8f9f9; font-size: 20px; border: 1px solid #d6dbdf; color:#999a9c; cursor: pointer;">
        <i class="fas fa-arrow-circle-left"></i> Back
    </a>
</div>
<div id="print-area">

        <div class="page-container d-print-none" style="padding: 15px 0 15px 0 !important; background: #E6E6E6;">
            <div class="pdf-page size-a4" style="margin-left: auto !important; margin-right: auto !important;">
                <div class="pdf-content">
                    <div class="pdf-body">
                        <div style="disply: block; clear: both; color: #000">
                            <div style="width: 43%; float: left; padding: 2px !important;">
                                <img src="{{ asset('/storage/fumaco_logo.png') }}" width="230">
                            </div>
                            <div style="width: 57%; float:left; text-transform: uppercase; font-size: 11pt;">
                                <p>PROJECT: <b>{{ $data['project'] }}</b></p>
                                <p style="margin-top: 15px !important;">LUMINAIRE SPECIFICATION AND INFORMATION</p>
                            </div>
                        </div>
                        <div style="display: block; padding-top: 5px !important; clear: both;">
                            <div style="border: 2px solid;">
                                <p style="font-size: 22px; padding: 3px !important; font-weight: bolder; color:#E67E22;">{{ $data['item_name'] }}</p>
                            </div>
                        </div>
                        <div style="display: block; padding-top: 5px !important; clear: both; margin-left: -2px !important;">
                            <div style="width: 43%; float: left; padding: 2px !important;">
                                @php
                                    $img1_actual = $img1_temp = null;
                                    $img1_src = $img2_src = $img3_src = '#';
                                    if (isset($images['image1']) && $images['image1']) {
                                        $img1_actual = null;
                                        $img1_temp = 'd-none';
                                        $img1_src = asset('/storage/brochures/' . $images['image1']);
                                    } else {
                                        $img1_actual = 'd-none';
                                        $img1_temp = null;
                                    }

                                    $img2_actual = $img2_temp = null;
                                    if (isset($images['image2']) && $images['image2']) {
                                        $img2_actual = null;
                                        $img2_temp = 'd-none';
                                        $img2_src = asset('/storage/brochures/' . $images['image2']);
                                    } else {
                                        $img2_actual = 'd-none';
                                        $img2_temp = null;
                                    }

                                    $img3_actual = $img3_temp = null;
                                    if (isset($images['image3']) && $images['image3']) {
                                        $img3_actual = null;
                                        $img3_temp = 'd-none';
                                        $img3_src = asset('/storage/brochures/' . $images['image3']);
                                    } else {
                                        $img3_actual = 'd-none';
                                        $img3_temp = null;
                                    }
                                @endphp
                                {{-- 1 --}}
                                <div class="img-cont {{ $img1_actual }}" style="margin-bottom: 20px !important;" id="item-01-actual">
                                    <img src="{{ $img1_src }}" width="230" style="border: 2px solid;" id="item-01-image">
                                    <div class="custom-overlay"></div>
                                    <div class="custom-hover-button">
                                        <button type="button" class="btn btn-danger remove-image-btn" data-item-image-id="item-01">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="upload-image-placeholder {{ $img1_temp }}" id="item-01" style="margin-bottom: 20px !important;">
                                    <div class="upload-btn-wrapper">
                                        <div class="custom-upload-btn">
                                            <i class="far fa-image"></i>
                                            <span>(230 x 230 px)<br>Add Image 1</span>
                                        </div>
                                        <input type="file" class="dropzone" accept=".jpg,.jpeg,.png" data-item-image-id="item-01">
                                    </div>
                                </div>
                                {{-- 2 --}}
                                <div class="img-cont {{ $img2_actual }}" style="margin-bottom: 20px !important;" id="item-02-actual">
                                    <img src="{{ $img2_src }}" width="230" style="border: 2px solid;" id="item-02-image">
                                    <div class="custom-overlay"></div>
                                    <div class="custom-hover-button">
                                        <button type="button" class="btn btn-danger remove-image-btn" data-item-image-id="item-02">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="upload-image-placeholder {{ $img2_temp }}" id="item-02" style="margin-bottom: 20px !important;">
                                    <div class="upload-btn-wrapper">
                                        <div class="custom-upload-btn">
                                            <i class="far fa-image"></i>
                                            <span>(230 x 230 px)<br>Add Image 2</span>
                                        </div>
                                        <input type="file" class="dropzone" accept=".jpg,.jpeg,.png" data-item-image-id="item-02">
                                    </div>
                                </div>
                                {{-- 3 --}}
                                <div class="img-cont {{ $img3_actual }}" style="margin-bottom: 20px !important;" id="item-03-actual">
                                    <img src="{{ $img3_src }}" width="230" style="border: 2px solid;" id="item-03-image">
                                    <div class="custom-overlay"></div>
                                    <div class="custom-hover-button">
                                        <button type="button" class="btn btn-danger remove-image-btn" data-item-image-id="item-03">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="upload-image-placeholder {{ $img3_temp }}" id="item-03" style="margin-bottom: 20px !important;">
                                    <div class="upload-btn-wrapper">
                                        <div class="custom-upload-btn">
                                            <i class="far fa-image"></i>
                                            <span>(230 x 230 px)<br>Add Image 3</span>
                                        </div>
                                        <input type="file" class="dropzone" accept=".jpg,.jpeg,.png" data-item-image-id="item-03">
                                    </div>
                                </div>
                            </div>
                            <div style="width: 57%; float:left; color: #000">
                                <p style="font-weight: bolder;">Fitting Type / Reference:</p>
                                <p style="font-size: 22px; margin-top: 10px !important; font-weight: bolder; color:#E67E22;">{{ $data['reference'] }}</p>
                                <p style="font-weight: bolder; margin-top: 10px !important;">Description:</p>
                                <p style="font-size: 16px; margin-top: 10px !important;">{{ $data['description'] }}</p>
                                @if ($data['location'])
                                <p style="font-weight: bolder; margin-top: 10px !important;">Location:</p>
                                <p style="font-size: 16px; margin-top: 10px !important;">{{ $data['location'] }}</p>
                                @endif
                                <table border="0" style="border-collapse: collapse; width: 100%; font-size: 11.5px; margin-top: 30px !important;">
                                    @foreach ($attributes as $val)
                                    <tr>
                                        <td style="padding: 5px 0 5px 0 !important;width: 40%;">{{ $val->attr_name ? $val->attr_name : $val->attribute }}</td>
                                        <td style="padding: 5px 0 5px 0 !important;width: 60%;"><strong>{{ $val->attribute_value }}</strong></td>
                                    </tr>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pdf-footer">
                    <div class="pdf-footer-company-logo">
                        <img src="{{ asset('/storage/fumaco_logo.png') }}" width="155">
                    </div>
                    <div class="pdf-footer-company-website">www.fumaco.com</div>
                    <div class="pdf-footer-contacts">
                        <p>Plant: 35 Pleasant View Drive, Bagbaguin, Caloocan City</p>
                        <p>Sales & Showroom: 420 Ortigas Ave. cor. Xavier St., Greenhills, San Juan City</p>
                        <p>Tel. No.: (632) 721-0362 to 66</p>
                        <p>Fax No.: (632) 721-0361</p>
                        <p>Email Address: sales@fumaco.com</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Print Page -->
        <div class="print-container print-page">
            <div style="display: block">
                <div class="left-container">
                    <div style="width: 430px !important;">
                        <img src="{{ asset('/storage/fumaco_logo.png') }}" width="100%">
                    </div>
                </div>
                <div class="right-container">
                    <p style="font-size: 26px !important; text-transform: uppercase !important">PROJECT: <b>{{ $data['project'] }}</b></p>
                    <p style="margin-top: 15px !important;font-size: 26px !important;">LUMINAIRE SPECIFICATION AND INFORMATION</p>
                </div>
            </div>
            <div style="display: block; width: 100%; float: left; height: 10px;">&nbsp;</div>
            <div style="display: block; width: 100%; float: left;">
                <p style="font-size: 41px; padding: 3px !important; font-weight: bolder; color:#E67E22; border: 2px solid #1C2833">{{ $data['item_name'] }}</p>
            </div> 
            <div style="display: block; width: 100%; float: left; height: 10px;">&nbsp;</div>
            <div style="display: block; width: 100%; float: left; margin-bottom: 5px;">
                <div class="left-container">
                    <div style="width: 420px !important;">
                        {{-- @for ($i = 1; $i <= 3; $i++)
                            @php
                                $img = isset($row['images']['image'.$i]) && $row['images']['image'.$i] ? '/storage/brochures/'.$row['images']['image'.$i] : null;
                            @endphp
                            <img id="item-{{ $r }}-0{{ $i }}-print-image" src="{{ asset($img) }}" class="{{ !$img ? 'd-none' : null }}" width="100%" style="border: 2px solid #1C2833; margin-bottom: 20px !important;">
                        @endfor --}}
                        &nbsp;
                    </div>
                </div>
                <div class="right-container" style="border: 1px solid red !important">
                    <p style="font-weight: bolder; font-size: 28px;">Fitting Type / Reference:</p>
                    <p style="font-size: 35px; margin-top: 20px !important; font-weight: bolder; color:#E67E22;">{{ $data['reference'] }}</p>
                    <p style="font-weight: bolder; margin-top: 20px !important; font-size: 28px;">Description:</p>
                    <p style="font-size: 28px; margin-top: 20px !important;">{{ $data['description'] }}</p>
                    @if ($data['location'])
                    <p style="font-weight: bolder; margin-top: 20px !important; font-size: 28px;">Location:</p>
                    <p style="font-size: 28px; margin-top: 20px !important;">{{ $data['location'] }}</p>
                    @endif
                    <table border="0" style="border-collapse: collapse; width: 100%; font-size: 20px; margin-top: 35px !important;">
                        @foreach ($attributes as $val)
                        <tr>
                            <td style="padding: 5px 0 5px 0 !important;width: 40%;">{{ $val->attribute }}</td>
                            <td style="padding: 5px 0 5px 0 !important;width: 60%;"><strong>{{ $val->attribute_value }}</strong></td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>

    <div class="footer d-print" style="position: fixed; bottom: 0; padding-right: 10px !important; position: fixed !important; bottom: 0; width: 100%;">
        <div style="border-top: 2px solid #1C2833; padding-left: 20px !important; padding-right: 20px !important; line-height: 23px;">
            <div class="left-container">
                <div style="width: 55%; display: inline-block; float: left;">
                    <img src="{{ asset('/storage/fumaco_logo.png') }}" width="100%" style="margin-top: 30px !important;">
                </div>
                <div style="width: 38%; display: inline-block; float: right">
                    <div class="pdf-footer-company-website" style="font-size: 12pt;">www.fumaco.com</div>
                </div>
            </div>
            <div class="right-container" style="font-size: 12pt; width: 56% !important;">
                <p>Plant: 35 Pleasant View Drive, Bagbaguin, Caloocan City</p>
                <p>Sales & Showroom: 420 Ortigas Ave. cor. Xavier St., Greenhills, San Juan City</p>
                <p>Tel. No.: (632) 721-0362 to 66</p>
                <p>Fax No.: (632) 721-0361</p>
                <p>Email Address: sales@fumaco.com</p>
            </div>
            <div style="display: block; width: 100%; float: left; height: 10px;">&nbsp;</div>
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
        $(document).on('click', '.remove-image-btn', function(e) {
            e.preventDefault();

            var el = $(this);
            var details = {
                'column': $(this).data('col'),
                'row': $(this).data('row'),
                'project': $('#project').val(),
                'filename': $('#filename').val(),
                'item_image_id': $(this).data('item-image-id'),
                '_token': '{{ csrf_token() }}'
            }

            $.ajax({
                url: '/remove_image',
                type: 'POST',
                data: details,
                success: function(response){
                    if(response.status == 0){
                        showNotification("danger", response.message, "fa fa-info");
                    }else{
                        $('#' + details.item_image_id + '-actual').addClass('d-none');
                        $('#' + details.item_image_id).removeClass('d-none');
                        $('#' + details.item_image_id + '-print-image').addClass('d-none');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    showNotification("danger", 'Something went wrong. Please contact your system administrator.', "fa fa-info");
                }
            });
        });

        $(document).on('click', '#print-btn', function(e){
            e.preventDefault();
            $('#print-area').printThis({
                copyTagClasses: true,
                canvas: true,
                importCSS: true,
                importStyle: true,
            });
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

        $('.upload-image-placeholder').on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
        });

        $('.upload-image-placeholder').on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
        });

        $(document).on('change', 'input[type="file"]', function() {
            var details = {
                'column': $(this).data('col'),
                'row': $(this).data('row'),
                'project': $('#project').val(),
                'filename': $('#filename').val(),
                'item_image_id': $(this).data('item-image-id'),
            }

            readFile(this, details);
        });

        $(document).on('click', '#download-btn', function (){
            var project = $(this).data('proj');
            var file = $(this).data('file');
            $.ajax({
                type: 'get',
                url: '/download/' + project + '/' + file,
                success: function(response){
                    if(response.success == 1){
                        var orig_name = (response.name_from_db);
                        var downloadLink = document.createElement('a');
                        downloadLink.href = '{{ asset('storage/brochures') }}/' + response.orig_path;
                        downloadLink.download = response.new_name;
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                    }else{
                        showNotification("danger", response.message, "fa fa-info");
                    }
                },
            });
        });

        function readFile(input, details) {
            if (input.files && input.files[0]) {
                var formData = new FormData();
                formData.append('selected-file', input.files[0]);
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('column', details.column);
                formData.append('row', details.row);
                formData.append('project', details.project);
                formData.append('filename', details.filename);
                formData.append('item_image_id', details.item_image_id);

                if (typeof (FileReader) != "undefined") {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        $('#' + details.item_image_id + '-actual').removeClass('d-none');
                        $('#' + details.item_image_id).addClass('d-none');
                        $('#' + details.item_image_id + '-image').attr('src', e.target.result);
                        $('#' + details.item_image_id + '-print-image').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(input.files[0]);
                    uploadData(formData);
                } else {
                    showNotification("danger", "This browser does not support FileReader.", "fa fa-info");
                }
            }
        }
           
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function uploadData(formdata){
            $.ajax({
                url: '/upload_image',
                type: 'POST',
                data: formdata,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response){
                    if(response.status == 0){
                        showNotification("danger", response.message, "fa fa-info");
                    }else{
                        $('#' + response.data.item_image_id + '-actual').removeClass('d-none');
                        $('#' + response.data.item_image_id).addClass('d-none');
                        $('#' + response.data.item_image_id + '-print-image').removeClass('d-none');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    showNotification("danger", 'Something went wrong. Please contact your system administrator.', "fa fa-info");
                }
            });
        }
    });
</script>
@endsection