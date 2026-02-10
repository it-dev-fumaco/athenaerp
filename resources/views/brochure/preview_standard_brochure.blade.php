<div class="container-fluid p-0 m-0">
    <div class="row m-0 p-0">
        <div class="col-md-12 p-0 m-0">
            <span class="close-modal" data-target="#print-brochure-modal" style="position: absolute; right: 10px; top: 5px; cursor: pointer;z-index: 10;"><i class="fa fa-remove"></i></span>
            <div id="top-btn-container" style="z-index: 10;">
                <button class="btn-ctrl generate-brochure-btn" style="display: block;"><i class="fas fa-print"></i></button>
            </div>
            <div id="print-area">
                <div class="page-container overflow-auto d-print-none" style="padding: 15px 0 15px 0 !important; background: #E6E6E6;">
                    <div class="pdf-page size-a4" style="margin-left: auto !important; margin-right: auto !important;">
                        <div class="pdf-content">
                            <div class="pdf-body">
                                <div style="disply: block; clear: both; color: #000">
                                    <div style="width: 43%; float: left; padding: 2px !important;">
                                        <img src="{{ $fumacoLogo }}" width="230">
                                    </div>
                                    <div style="width: 57%; float:left; text-transform: uppercase; font-size: 11pt;">
                                        <p>PROJECT: <b>{{ $data['project'] }}</b></p>
                                        <p style="margin-top: 15px !important;">LUMINAIRE SPECIFICATION AND INFORMATION</p>
                                    </div>
                                </div>
                                <div style="display: block; padding-top: 5px !important; clear: both;">
                                    <div style="border: 2px solid;">
                                        <p style="font-size: 14pt; padding: 3px !important; font-weight: bolder; color:#E67E22;">{{ $data['item_name'] }}</p>
                                    </div>
                                </div>
                                <div style="display: block; padding-top: 5px !important; clear: both; margin-left: -2px !important;">
                                    <div style="width: 43%; float: left; padding: 2px !important;">
                                        @php
                                            $img1Actual = $img1Temp = null;
                                            $img1Src = $img2Src = $img3Src = '#';
                                            $img1Id = $img2Id = $img3Id = null;
                                            if (isset($images['image1']['filepath']) && $images['image1']['filepath']) {
                                                $img1Actual = null;
                                                $img1Temp = 'd-none';
                                                $img1Src = asset($images['image1']['filepath']);
                                                $img1Id = $images['image1']['id'];
                                            } else {
                                                $img1Actual = 'd-none';
                                                $img1Temp = null;
                                            }

                                            $img2Actual = $img2Temp = null;
                                            if (isset($images['image2']['filepath']) && $images['image2']['filepath']) {
                                                $img2Actual = null;
                                                $img2Temp = 'd-none';
                                                $img2Src = asset($images['image2']['filepath']);
                                                $img2Id = $images['image2']['id'];
                                            } else {
                                                $img2Actual = 'd-none';
                                                $img2Temp = null;
                                            }

                                            $img3Actual = $img3Temp = null;
                                            if (isset($images['image3']['filepath']) && $images['image3']['filepath']) {
                                                $img3Actual = null;
                                                $img3Temp = 'd-none';
                                                $img3Src = asset($images['image3']['filepath']);
                                                $img3Id = $images['image3']['id'];
                                            } else {
                                                $img3Actual = 'd-none';
                                                $img3Temp = null;
                                            }
                                        @endphp
                                        {{-- 1 --}}
                                        <div class="img-cont {{ $img1Actual }}" style="margin-bottom: 20px !important;" id="item-01-actual">
                                            <img src="{{ $img1Src }}" width="230" style="border: 2px solid;" id="item-01-image">
                                            <div class="custom-overlay"></div>
                                            <div class="custom-hover-button">
                                                <button type="button" class="btn btn-danger remove-image-btn" data-item-image-id="item-01" data-id="{{ $img1Id }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="upload-image-placeholder {{ $img1Temp }}" id="item-01" style="margin-bottom: 20px !important;" data-idx="1">
                                            <div class="upload-btn-wrapper">
                                                <div class="custom-upload-btn">
                                                    <i class="far fa-image"></i>
                                                    <span class="d-block">(230 x 230 px)</span>
                                                    <small class="d-block text-muted">Click here to select image</small>
                                                </div>
                                            </div>
                                        </div>
                                        {{-- 2 --}}
                                        <div class="img-cont {{ $img2Actual }}" style="margin-bottom: 20px !important;" id="item-02-actual">
                                            <img src="{{ $img2Src }}" width="230" style="border: 2px solid;" id="item-02-image">
                                            <div class="custom-overlay"></div>
                                            <div class="custom-hover-button">
                                                <button type="button" class="btn btn-danger remove-image-btn" data-item-image-id="item-02" data-id="{{ $img2Id }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="upload-image-placeholder {{ $img2Temp }}" id="item-02" style="margin-bottom: 20px !important;" data-idx="2">
                                            <div class="upload-btn-wrapper">
                                                <div class="custom-upload-btn">
                                                    <i class="far fa-image"></i>
                                                    <span class="d-block">(230 x 230 px)</span>
                                                    <small class="d-block text-muted">Click here to select image</small>
                                                </div>
                                            </div>
                                        </div>
                                        {{-- 3 --}}
                                        <div class="img-cont {{ $img3Actual }}" style="margin-bottom: 20px !important;" id="item-03-actual">
                                            <img src="{{ $img3Src }}" width="230" style="border: 2px solid;" id="item-03-image">
                                            <div class="custom-overlay"></div>
                                            <div class="custom-hover-button">
                                                <button type="button" class="btn btn-danger remove-image-btn" data-item-image-id="item-03" data-id="{{ $img3Id }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="upload-image-placeholder {{ $img3Temp }}" id="item-03" style="margin-bottom: 20px !important;" data-idx="3">
                                            <div class="upload-btn-wrapper">
                                                <div class="custom-upload-btn">
                                                    <i class="far fa-image"></i>
                                                    <span class="d-block">(230 x 230 px)</span>
                                                    <small class="d-block text-muted">Click here to select image</small>
                                                </div>
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
                                            @if (isset($remarks) && $remarks)
                                            <tr>
                                                <td style="padding: 5px 0 5px 0 !important;width: 40%;">Remarks</td>
                                                <td style="padding: 5px 0 5px 0 !important;width: 60%;"><strong>{{ $remarks }}</strong></td>
                                            </tr>
                                            @endif
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
                            <div style="width: 430px !important; max-width: 100%;">
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
                        <p style="font-size: 34px; padding: 3px !important; font-weight: bolder; color:#E67E22; border: 2px solid #1C2833">{{ $data['item_name'] }}</p>
                    </div> 
                    <div style="display: block; width: 100%; float: left; height: 10px;">&nbsp;</div>
                    <div style="display: block; width: 100%; float: left; margin-bottom: 5px;">
                        <div class="left-container">
                            <div style="width: 420px !important; max-width: 100%;">
                                @for ($i = 1; $i <= 3; $i++)
                                    @php
                                        $img = isset($images['image'.$i]['filepath']) && $images['image'.$i]['filepath'] ? $images['image'.$i]['filepath'] : null;
                                    @endphp
                                    <img id="item-0{{ $i }}-print-image" src="{{ $img ? asset($img) : '' }}" class="{{ !$img ? 'd-none' : null }}" width="100%" style="border: 2px solid #1C2833; margin-bottom: 20px !important;">
                                @endfor
                                &nbsp;
                            </div>
                        </div>
                        <div class="right-container">
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
                                    <input type="hidden" id="item-image-order" name="image_idx">
                                    <input type="hidden" name="project" value="{{ $data['project'] }}">
                                    <input type="hidden" name="item_code" value="{{ $data['item_code'] }}">
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
                            <div class="card-body" style="min-height: 400px;">
                                    <input type="hidden" id="item-image-order-1" name="image_idx">
                                    <input type="hidden" name="project" value="{{ $data['project'] }}">
                                    <input type="hidden" name="item_code" value="{{ $data['item_code'] }}">
                                    <input type="hidden" name="existing" value="1">
                                    @if ($imgCheck)
                                    <div class="row p-2">
                                        @foreach ($currentImages as $cii)
                                        @php
                                            $img = $cii['filepath'];
                                            $imgWebp = explode('.', $img)[0].'.webp';
                                        @endphp
                                        <div class="col-3 p-0">
                                            <label class="m-0 img-btn d-block">
                                                <input type="radio" name="selected_image" value="{{ $cii['filename'] }}" required>
                                                <div class="c-img rounded">
                                                    <picture>
                                                        <source srcset="{{ asset('storage/'.$imgWebp) }}" type="image/webp" alt="{{ $img }}">
                                                        <source srcset="{{ asset('storage/'.$img) }}" type="image/jpeg" alt="{{ $img }}">
                                                        <img src="{{ asset('storage/'.$img) }}" alt="{{ $img }}" class="img-responsive img-thumbnail" style="width: 100% !important;">
                                                    </picture>
                                                </div>
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                    @else
                                    <div class="row mt-3">
                                        <div class="col-4 offset-4 text-center text-muted text-uppercase">No image(s) found</div>
                                    </div>
                                    @endif
                                </div>
                                <div class="card-footer">
                                    <div class="text-center">
                                        <button class="btn btn-primary" type="submit" id="submit-selected-image-brochure" disabled><i class="fas fa-check"></i> Submit Selected Image</button>
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

<style>
    *:not(i):not(.fa){
        font-family: 'Poppins' !important;
    }
    #top-btn-container{
        position: absolute;
        right: 25px;
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
        width: 100%;
        max-width: 230px;
        height: 230px;
        border-radius: 13px;
        display: block;
    }
    .upload-btn-wrapper {
        position: relative;
        overflow: hidden;
        display: block;
        width: 100%;
        max-width: 230px;
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
        width: 100%;
        max-width: 230px;
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
<script>
    $(document).ready(function (){
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        $(document).on('click', '.upload-image-placeholder', function(e) {
            e.preventDefault();
            $('#item-image-order').val($(this).data('idx'));
            $('#item-image-order-1').val($(this).data('idx'));
            $('#item-image-container-id').val($(this).attr('id'));

            $('#select-file-modal').modal('show');
        });

        $(document).on('change', '#browse-file', function (e) {
            var file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
            if (!file) {
                $('#browse-file-text').text('Browse File');
                $('#img-preview').addClass('d-none').attr('src', '{{ asset('/storage/icon/no_img.png') }}');
                $('#upload-btn').attr('disabled', true);
                return;
            }

            $('#browse-file-text').text(file.name);
            $('#upload-btn').removeAttr('disabled');
            if (typeof (FileReader) != "undefined") {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#img-preview').removeClass('d-none').attr('src', e.target.result);
                }
                reader.readAsDataURL(file);
            } else {
                showNotification("danger", "This browser does not support FileReader.", "fa fa-info");
            }
        });

        $(document).on('hidden.bs.modal', '.modal', function () {
            $('#img-preview').addClass('d-none').attr('src', '{{ asset('/storage/icon/no_img.png') }}');
            $('#browse-file-text').text('Browse File');
            $('#image-upload-form-1')[0].reset();
            $('#image-upload-form')[0].reset();

            $('#submit-selected-image-brochure').attr('disabled', true);
            $('#upload-btn').attr('disabled', true);
        });

        $("input[name=selected_image]:radio").change(function (e) {
            e.preventDefault();
            $('#submit-selected-image-brochure').removeAttr('disabled');
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
                        $('#' + item_image_id).addClass('d-none');
                        $('#' + item_image_id + '-actual').removeClass('d-none');
                        $('#' + item_image_id + '-image').attr('src', '{{ asset("") }}' + response.src);
                        
                        $('#select-file-modal').modal('hide');
                    }
                },
            });
        });

        $(document).on('click', '.remove-image-btn', function(e) {
            e.preventDefault();

            var el = $(this);
            var details = {
                'id': $(this).data('id'),
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
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    showNotification("danger", 'Something went wrong. Please contact your system administrator.', "fa fa-info");
                }
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
    });
</script>