<div id="preview-loop" class="row">
    <div id="top-btn-container" style="z-index: 10;">
        <a href="/generate_multiple_brochures?pdf=1" target="_blank" class="btn-ctrl" style="display: block;"><i class="fas fa-print"></i></a>
    </div>
    <div class="col-3">
        <h3 style="text-align: center; font-weight: bolder; text-transform: uppercase; margin: 15px 0 8px 0 !important; letter-spacing: 0.5px; font-size: 20px;">Product Brochure</h3>
        <ul id="sortable" class="list-group" style="font-size: 10pt;">
            @foreach ($content as $i => $item)
                <li class="list-group-item p-1">
                    <a href="#item-{{ $item['item_code'].'-'.$i }}">{{ $i + 1 }}.&nbsp;<b>{{ $item['item_code'] }}</b> - {{ $item['item_name'] }}</a>
                </li>
            @endforeach
        </ul>
    </div>
    <div class="col-9 overflow-auto" style="max-height: 83vh;">
        @foreach ($content as $i => $item)
            @php
                $images = $item['images'];
            @endphp
            <div id="item-{{ $item['item_code'].'-'.$i }}" class="page-container overflow-auto d-print-none" style="padding: 15px 0 15px 0 !important; background: #E6E6E6;">
                <div class="pdf-page size-a4" style="margin-left: auto !important; margin-right: auto !important;">
                    <div class="pdf-content">
                        <div class="pdf-body">
                            <div style="disply: block; clear: both; color: #000">
                                <div style="width: 43%; float: left; padding: 2px !important;">
                                    <img src="{{ $fumaco_logo }}" width="230">
                                </div>
                                <div style="width: 57%; float:left; text-transform: uppercase; font-size: 11pt;">
                                    <p>PROJECT: <b>{{ $item['project'] }}</b></p>
                                    <p style="margin-top: 15px !important;">LUMINAIRE SPECIFICATION AND INFORMATION</p>
                                </div>
                            </div>
                            <div style="display: block; padding-top: 5px !important; clear: both;">
                                <div style="border: 2px solid;">
                                    <p style="font-size: 14pt; padding: 3px !important; font-weight: bolder; color:#E67E22;">{{ $item['item_name'] }}</p>
                                </div>
                            </div>
                            <div style="display: block; padding-top: 5px !important; clear: both; margin-left: -2px !important;">
                                <div style="width: 43%; float: left; padding: 2px !important;">
                                    @for($img = 1; $img <= 3; $img++)
                                        @php
                                            $img_actual = 'd-none';
                                            $img_temp = $img_src = $img_id = null;
                                            if (isset($images['image'.$img]['filepath']) && $images['image'.$img]['filepath']) {
                                                $img_actual = null;
                                                $img_temp = 'd-none';
                                                $img_src = $images['image'.$img]['filepath'];
                                                $img_id = $images['image'.$img]['id'];
                                            }
                                        @endphp
                                        <div class="img-cont {{ $img_actual }}" style="margin-bottom: 20px !important;" id="{{ $item['item_code'] }}-0{{ $img }}-actual">
                                            <img src="{{ $img_src }}" width="230" style="border: 2px solid;" id="{{ $item['item_code'] }}-0{{ $img }}-image">
                                            <div class="custom-overlay"></div>
                                            <div class="custom-hover-button">
                                                <button type="button" class="btn btn-danger remove-image-btn" data-item-image-id="{{ $item['item_code'] }}-0{{ $img }}" data-id="{{ $img_id }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="upload-image-placeholder {{ $img_temp }}" id="{{ $item['item_code'] }}-0{{ $img }}" style="margin-bottom: 20px !important;" data-idx="{{ $img }}" data-item-code="{{ $item['item_code'] }}">
                                            <div class="upload-btn-wrapper">
                                                <div class="custom-upload-btn">
                                                    <i class="far fa-image"></i>
                                                    <span class="d-block">(230 x 230 px)</span>
                                                    <small class="d-block text-muted">Click here to select image</small>
                                                </div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                                <div style="width: 57%; float:left; color: #000">
                                    <p style="font-weight: bolder;">Fitting Type / Reference:</p>
                                    <p style="font-size: 22px; margin-top: 10px !important; font-weight: bolder; color:#E67E22;">{{ $item['reference'] }}</p>
                                    <p style="font-weight: bolder; margin-top: 10px !important;">Description:</p>
                                    <p style="font-size: 16px; margin-top: 10px !important;">{{ $item['description'] }}</p>
                                    @if ($item['location'])
                                    <p style="font-weight: bolder; margin-top: 10px !important;">Location:</p>
                                    <p style="font-size: 16px; margin-top: 10px !important;">{{ $item['location'] }}</p>
                                    @endif
                                    <table border="0" style="border-collapse: collapse; width: 100%; font-size: 11.5px; margin-top: 30px !important;">
                                        @foreach ($item['attributes'] as $val)
                                        <tr>
                                            <td style="padding: 5px 0 5px 0 !important;width: 40%;">{{ $val['attribute_name'] }}</td>
                                            <td style="padding: 5px 0 5px 0 !important;width: 60%;"><strong>{{ $val['attribute_value'] }}</strong></td>
                                        </tr>
                                        @endforeach
                                        @if ($item['remarks'])
                                        <tr>
                                            <td style="padding: 5px 0 5px 0 !important;width: 40%;">Remarks</td>
                                            <td style="padding: 5px 0 5px 0 !important;width: 60%;"><strong>{{ $item['remarks'] }}</strong></td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="pdf-footer">
                        <div class="pdf-footer-company-logo">
                            <img src="{{ $fumaco_logo }}" width="155">
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
        @endforeach
    </div>
</div>

<div class="modal fade" id="select-file-modal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Choose Image</h4>
                <button type="button" class="close close-modal" data-target="#select-file-modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="item-image-container-id">
                <div class="row p-0 m-0">
                    <div class="col-4">
                        <div class="card card-primary">
                            <div class="card-header pl-3 pr-3 pt-2 pb-2">
                                <h6 class="m-0 text-uppercase">Upload new Image</h6>
                            </div>
                            <form id="image-upload-form" method="POST" action="/upload_image_for_standard_brochure" autocomplete="off" enctype="multipart/form-data">
                                @csrf
                                <div class="card-body" style="min-height: 400px;">
                                    <div class="d-none">
                                        <input type="text" id="item-image-order" name="image_idx" placeholder="image_idx">
                                        <input type="text" name="project" value="{{ $project }}" placeholder="project">
                                        <input type="text" name="item_code" value="" placeholder="item_code">
                                    </div>
                                    <div class="row">
                                        <div class="col-12 p-2">
                                            <small class="d-block text-center text-muted" style="font-size: 10pt;">Files Supported: JPEG, JPG, PNG</small>
                                            <small class="d-block text-center text-muted" style="font-size: 8pt;">Image Size: (230 x 230 px)</small>
                                            <div class="form-group mt-2">
                                                <div class="input-group">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="browse-file" name="selected-file" accept=".jpg,.jpeg,.png,.webp" required>
                                                        <label class="custom-file-label" for="browse-file" id="browse-file-text" style="overflow: hidden;">Browse File</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-center mt-4" style="min-height: 200px;">
                                                <img src="{{ asset('/storage/icon/no_img.png') }}" width="230" class="img-thumbnail mb-3 d-none" id="img-preview">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="text-center">
                                        <button class="btn btn-primary col-8" type="submit" id="upload-btn" disabled><i class="fas fa-upload"></i> Upload</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-8">
                        <div class="card card-info">
                            <div class="card-header pl-3 pr-3 pt-2 pb-2">
                                <h6 class="m-0 text-uppercase">Select from existing images</h6>
                            </div>
                            <form id="image-upload-form-1" action="/upload_image_for_standard_brochure" method="POST" autocomplete="off">
                                @csrf
                                <input type="hidden" name="project" value="{{ $project }}" placeholder="project">
                                <div id="brochure-images-container"></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #sortable a {
        text-decoration: none;
        cursor: pointer;
        display: block;
        padding: 6px 10px;
        color: #4b6ea6;
    }
    #sortable a:hover {
        color: #4b6ea6;
        background-color:#f4f6f6;
    }

    *:not(i):not(.fa){
        font-family: 'Poppins' !important;
    }
    #top-btn-container{
        position: absolute;
        right: 50px;
        top: 50px;
    }
    #top-left-btn-container {
        position: fixed;
        left: 25px;
        top: 70px;
    }
    .btn-ctrl{
        background-color: #fff;
        width: 60px;
        height: 60px;
        margin: 10px;
        padding: 15px 20px 15px 20px;
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
    .img-btn > input{
        display:none
    }
    .img-btn > .c-img{
        cursor:pointer;
        padding: 10px;
    }
    .img-btn > input:checked + .c-img{
        background-color:  #d5d8dc;
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
</style>

<script>
    $('#browse-file').change(function(e){
        var fileName = e.target.files[0].name;
        $('#browse-file-text').text(fileName);
        $('#upload-btn').removeAttr('disabled');
        if (typeof (FileReader) != "undefined") {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#img-preview').removeClass('d-none').attr('src', e.target.result);
            }
            reader.readAsDataURL($(this)[0].files[0]);
        } else {
            showNotification("danger", "This browser does not support FileReader.", "fa fa-info");
        }
    });

    $(document).on('hidden.bs.modal', '.modal', function () {
        $('#img-preview').addClass('d-none').attr('src', '{{ asset('/storage/icon/no_img.png') }}');
        $('#browse-file-text').text('Browse Image');
        $('#image-upload-form-1')[0].reset();
        $('#image-upload-form')[0].reset();

        $('#submit-selected-image-brochure').attr('disabled', true);
        $('#upload-btn').attr('disabled', true);
    });

    $('#image-upload-form').submit(function (e) {
        e.preventDefault();

        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data:  new FormData(this),
            contentType: false,
            cache: false,
            processData: false,
            success: function(response){
                if(response.status == 0){
                    showNotification("danger", response.message, "fa fa-info");
                }else{
                    var item_image_id = $('#item-image-container-id').val();
                    $('#' + item_image_id).addClass('d-none');
                    $('#' + item_image_id + '-actual').removeClass('d-none');
                    $('#' + item_image_id + '-image').attr('src', $('#img-preview').attr('src'));
                    
                    $('#select-file-modal').modal('hide');
                }
            },
        });
    });

    $('#image-upload-form-1').submit(function (e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data:  $(this).serialize(),
            success: function(response){
                if(response.status == 0){
                    showNotification("danger", response.message, "fa fa-info");
                }else{
                    var item_image_id = $('#item-image-container-id').val();
                    console.log($('#item-image-container-id').val());
                    $('#' + item_image_id).addClass('d-none');
                    $('#' + item_image_id + '-actual').removeClass('d-none');
                    $('#' + item_image_id + '-image').attr('src', '{{ asset("") }}' + response.src);
                    
                    $('#select-file-modal').modal('hide');
                }
            },
        });
    });
</script>