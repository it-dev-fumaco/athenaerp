
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Product Brochure Preview</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <link rel="stylesheet" href="{{ asset('/updated/custom/font.css') }}">
        <link rel="stylesheet" href="{{ asset('/updated/icons/font-awesome.min.css') }}">
        {{--  <!-- Font Awesome Icons -->  --}}
        <link rel="stylesheet" href="{{ asset('/updated/plugins/fontawesome-free/css/all.min.css') }}">
        {{--  <!-- Theme style -->  --}}
        <link rel="stylesheet" href="{{ asset('/updated/dist/css/adminlte.min.css') }}">

        <style>
            html {
                scroll-behavior: smooth;
            }
            body {
                margin: 0 !important; padding: 0 !important;
            }
            * {
                font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
            }
            #table-of-contents-sidebar {
                position: fixed;
                width: 280px;
                top:0;
                left: 0;
                bottom: 0;
                background: #fff;
                border-right: 1px solid  #abb2b9 ;
                overflow-y:auto;
                overflow-x:hidden;
            }
            #toc-links { 
                margin: 10px;
            }
            #toc-links a {
                text-decoration: none;
                cursor: pointer;
                display: block;
                padding: 6px 10px;
                color:   #4b6ea6  ;
                border-bottom: 1px solid#d5d8dc;
            }
            #toc-links a:hover {
                background-color:#f4f6f6;
            }
            #print-btn{
                position: fixed;
                right: 25px;
                top: 40px;
                background-color: #f1f1f1;
                width: 50px;
                height: 50px;
                padding: 5px 15px 5px 15px;
                border-radius: 50%;
                font-size: 20px;
                border: none;
                cursor: pointer;
                box-shadow: 0 0 10px rgba(0,0,0,0.25), 0 0 10px rgba(0,0,0,0.22);
                color: #446eac;
            }
            #print-btn:hover{
                background-color:   #2980b9  ;
                transition: .4s;
                color: #f1f1f1;
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

            @media screen {
                .page-container * {
                    z-index: 0;
                    margin: 0 !important; padding: 0 !important;
                }
                .page-container{
                    margin-left: 280px !important;
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
                    font-size: 11.5px;
                }
                .pdf-footer-company-logo {
                    position: absolute;
                    left: .2in;
                    top: .15in;
                }
                .pdf-footer-company-website {
                    position: absolute;
                    left: 2.1in;
                    top: 2px;
                }
                .pdf-footer-contacts {
                    position: absolute;
                    right: 28px;
                    top: 2px;
                    font-weight: 500;
                    line-height: 14px;
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
                .print-page{
                    display: none;
                }
            }
            @media print {
                * { margin: 0 !important; padding: 0 !important; }
                .d-print-none{
                    display: none;
                }
                .print-page {
                    display: block;
                    width: 8.3in !important;
                    height: 12.7in !important;
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
<input type="hidden" id="project" value="{{ $project }}">
<input type="hidden" id="filename" value="{{ $filename }}">
<div id="table-of-contents-sidebar">
    <div style="display: block; text-align: center; margin: 20px 0;">
        <a href="/brochure" style="padding: 8px 10px 8px 10px !important; margin: 15px !important; border-radius: 5px; background-color: #f8f9f9; font-size: 20px; border: 1px solid #d6dbdf; color:#d5d8dc; cursor: pointer;">
            <i class="fas fa-home"></i>
        </a>
    </div>
    <h3 style="text-align: center; font-weight: bolder; text-transform: uppercase; margin: 15px 0 8px 0 !important; letter-spacing: 0.5px; font-size: 20px;">Table of Contents</h3>
    <div id="toc-links">
        @php
            $a = 1;
        @endphp
        @foreach ($table_of_contents as $index => $toc)
            @if (!$toc['id'] && !$toc['text'])
                @continue
            @endif
            <a href="#{{ $toc['id'] }}">{{ $a++ .'. ' . $toc['text'] }}</a>
        @endforeach
    </div>
</div>

<button id="print-btn"><i class="fas fa-print"></i></button>

<div id="print-area">
    @foreach ($content as $r => $row)
    @if (!collect($row['attrib'])->filter()->values()->all())
        @continue
    @endif
    <div class="page-container d-print-none" id="{{ $row['id'] }}" style="margin-left: 280px !important; padding: 15px 0 15px 0 !important; background: #E6E6E6;">
            <div class="pdf-page size-a4" style="margin-left: auto !important; margin-right: auto !important;">
                <div class="pdf-content">
                    <div class="pdf-body">
                        <div style="display: block; clear: both;">
                            <div style="width: 44%; float: left; padding: 2px !important;">
                                <img src="{{ asset('/storage/fumaco_logo.png') }}" width="230">
                            </div>
                            <div style="width: 54%; float:left; text-transform: uppercase;">
                                <p>PROJECT: <b>{{ $row['project'] }}</b></p>
                                <p style="margin-top: 15px !important;">LUMINAIRE SPECIFICATION AND INFORMATION</p>
                            </div>
                        </div>
                        <div style="display: block; padding-top: 5px !important; clear: both;">
                            <div style="border: 2px solid;">
                                <p style="font-size: 22px; padding: 3px !important; font-weight: bolder; color:#E67E22;">{{ $row['item_name'] }}</p>
                            </div>
                        </div>
                        <div style="display: block; padding-top: 5px !important; clear: both; margin-left: -2px !important;">
                            <div style="width: 44%; float: left; padding: 2px !important;">
                                @if (isset($row['images']['image1']) && $row['images']['image1'])
                                <img src="{{ asset('/storage/brochures/' . $row['images']['image1']) }}" width="230" style="margin-bottom: 20px !important; border: 2px solid;" id="item-{{ $r }}-01-image">
                                @else
                                <img src="{{ asset('/storage/icon/no_img.png') }}" class="d-none" width="230" style="margin-bottom: 20px !important; border: 2px solid;" id="item-{{ $r }}-01-image">
                                <div class="upload-image-placeholder" id="item-{{ $r }}-01" data-col="Image 1" data-row="{{ $row['row'] }}">
                                    <div class="upload-btn-wrapper">
                                        <div class="custom-upload-btn">
                                            <i class="far fa-image"></i>
                                            <span>(230 x 230 px)<br>Add Image</span>
                                        </div>
                                    </div>
                                </div>
                                <br>
                                @endif
                                <br>
                                @if (isset($row['images']['image2']) && $row['images']['image2'])
                                <img src="{{ asset('/storage/brochures/' . $row['images']['image2']) }}" width="230" style="margin-bottom: 20px !important; border: 2px solid;" id="item-{{ $r }}-02-image">
                                @else
                                <img src="{{ asset('/storage/icon/no_img.png') }}" class="d-none" width="230" style="margin-bottom: 20px !important; border: 2px solid;" id="item-{{ $r }}-02-image">
                                <div class="upload-image-placeholder {{ isset($row['images']['image1']) && $row['images']['image1'] ? '' : 'd-none' }}" id="item-{{ $r }}-02" data-col="Image 2" data-row="{{ $row['row'] }}">
                                    <div class="upload-btn-wrapper">
                                        <div class="custom-upload-btn">
                                            <i class="far fa-image"></i>
                                            <span>(230 x 230 px)<br>Add Image</span>
                                        </div>
                                    </div>
                                </div>
                                <br>
                                @endif
                                 <br>
                                @if (isset($row['images']['image3']) && $row['images']['image3'])
                                <img src="{{ asset('/storage/brochures/' . $row['images']['image3']) }}" width="230" style="margin-bottom: 20px !important; border: 2px solid;" id="item-{{ $r }}-03-image">
                                @else
                                <img src="{{ asset('/storage/icon/no_img.png') }}" class="d-none" width="230" style="margin-bottom: 20px !important; border: 2px solid;" id="item-{{ $r }}-03-image">
                                <div class="upload-image-placeholder {{ isset($row['images']['image2']) && $row['images']['image2'] ? '' : 'd-none' }}" id="item-{{ $r }}-03" data-col="Image 3" data-row="{{ $row['row'] }}">
                                    <div class="upload-btn-wrapper">
                                        <div class="custom-upload-btn">
                                            <i class="far fa-image"></i>
                                            <span>(230 x 230 px)<br>Add Image</span>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                            <div style="width: 54%; float:left;">
                                <p style="font-weight: bolder;">Fitting Type / Reference:</p>
                                <p style="font-size: 22px; margin-top: 10px !important; font-weight: bolder; color:#E67E22;">{{ $row['reference'] }}</p>
                                <p style="font-weight: bolder; margin-top: 10px !important;">Description:</p>
                                <p style="font-size: 16px; margin-top: 10px !important;">{{ $row['description'] }}</p>
                                @if ($row['location'])
                                <p style="font-weight: bolder; margin-top: 10px !important;">Location:</p>
                                <p style="font-size: 16px; margin-top: 10px !important;">{{ $row['location'] }}</p>
                                @endif
                                <table border="0" style="border-collapse: collapse; width: 100%; font-size: 13px; margin-top: 30px !important;">
                                    @foreach ($row['attributes'] as $val)
                                    @if ($val['attribute_value'] && !in_array($val['attribute_name'], ['Image 1', 'Image 2', 'Image 3']))
                                    <tr>
                                        <td style="padding: 5px 0 5px 0 !important;width: 40%;">{{ $val['attribute_name'] }}</td>
                                        <td style="padding: 5px 0 5px 0 !important;width: 60%;"><strong>{{ $val['attribute_value'] }}</strong></td>
                                    </tr>
                                    @endif
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
                    <div style="width: 300px !important;">
                        <img src="{{ asset('/storage/fumaco_logo.png') }}" width="100%">
                    </div>
                </div>
                <div class="right-container">
                    <p style="font-size: 20px !important; text-transform: uppercase !important">PROJECT: <b>{{ $row['project'] }}</b></p>
                    <p style="margin-top: 15px !important;font-size: 20px !important;">LUMINAIRE SPECIFICATION AND INFORMATION</p>
                </div>
            </div>
            <div style="display: block; width: 100%; float: left; height: 10px;">&nbsp;</div>
            <div style="display: block; width: 100%; float: left;">
                <p style="font-size: 29px; padding: 3px !important; font-weight: bolder; color:#E67E22; border: 2px solid #1C2833">{{ $row['item_name'] }}</p>
            </div> 
            <div style="display: block; width: 100%; float: left; height: 10px;">&nbsp;</div>
            <div style="display: block; width: 100%; float: left; margin-bottom: 5px;">
                <div class="left-container">
                    <div style="width: 300px !important;">
                        <img src="{{ asset('/storage/icon/no_img.png') }}" width="100%" style="border: 2px solid #1C2833;">
                    </div>
                </div>
                <div class="right-container">
                    <p style="font-weight: bolder; font-size: 21px;">Fitting Type / Reference:</p>
                    <p style="font-size: 27px; margin-top: 10px !important; font-weight: bolder; color:#E67E22;">{{ $row['reference'] }}</p>
                    <p style="font-weight: bolder; margin-top: 10px !important; font-size: 21px;">Description:</p>
                    <p style="font-size: 21px; margin-top: 10px !important;">{{ $row['description'] }}</p>
                    @if ($row['location'])
                    <p style="font-weight: bolder; margin-top: 10px !important; font-size: 21px;">Location:</p>
                    <p style="font-size: 21px; margin-top: 10px !important;">{{ $row['location'] }}</p>
                    @endif
                    <table border="0" style="border-collapse: collapse; width: 100%; font-size: 18px; margin-top: 30px !important;">
                        @foreach ($row['attributes'] as $val)
                        @if ($val['attribute_value'])
                        <tr>
                            <td style="padding: 5px 0 5px 0 !important;width: 40%;">{{ $val['attribute_name'] }}</td>
                            <td style="padding: 5px 0 5px 0 !important;width: 60%;"><strong>{{ $val['attribute_value'] }}</strong></td>
                        </tr>
                        @endif
                        @endforeach
                    </table>
                </div>
            </div>
            @if ($loop->last)
                <div class="footer" style="position: fixed; bottom: 0; padding-right: 10px !important;">
                    <div style="border-top: 2px solid #1C2833; padding-left: 20px !important; padding-right: 20px !important">
                        <div class="left-container">
                            <div style="width: 55%; display: inline-block; float: left;">
                                <img src="{{ asset('/storage/fumaco_logo.png') }}" width="100%" style="margin-top: 30px !important;">
                            </div>
                            <div style="width: 38%; display: inline-block; float: right">
                                <div class="pdf-footer-company-website" style="font-size: 16.5px;">www.fumaco.com</div>
                            </div>
                        </div>
                        <div class="right-container" style="font-size: 16.5px;">
                            <p>Plant: 35 Pleasant View Drive, Bagbaguin, Caloocan City</p>
                            <p>Sales & Showroom: 420 Ortigas Ave. cor. Xavier St., Greenhills, San Juan City</p>
                            <p>Tel. No.: (632) 721-0362 to 66</p>
                            <p>Fax No.: (632) 721-0361</p>
                            <p>Email Address: sales@fumaco.com</p>
                        </div>
                        <div style="display: block; width: 100%; float: left; height: 10px;">&nbsp;</div>
                    </div>
                </div>
            @endif
        </div>
        <!-- Print Page -->
    @endforeach
</div>

<div class="modal fade" id="select-file-modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Image</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="select-item-image-id">
                {{-- <ul class="nav nav-pills ml-auto p-2">
                    <li class="nav-item"><a class="nav-link active" href="#tab_1" data-toggle="tab">Upload File</a></li>
                    <li class="nav-item"><a class="nav-link" href="#tab_2" data-toggle="tab">Select from Media Files</a></li>
                </ul> --}}
                <div class="tab-content" style="min-height: 400px;">
                    <div class="tab-pane active" id="tab_1">
                        <form id="image-upload-form" method="POST" action="/upload_image" autocomplete="off" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" id="excel-column" name="column">
                            <input type="hidden" id="excel-row" name="row">
                            <input type="hidden" id="excel-project" name="project">
                            <input type="hidden" id="excel-filename" name="filename">
                            <div class="row mb-5">
                                <div class="col-8 offset-2">
                                    <div class="text-center">
                                        <img src="{{ asset('/storage/icon/no_img.png') }}" width="230" class="img-thumbnail mb-3" id="img-preview">
                                    </div>
                                    <p class="text-center text-muted">Files Supported: JPEG, JPG, PNG</p>
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="browse-file" name="selected-file" accept=".jpg,.jpeg,.png" required>
                                                <label class="custom-file-label" for="browse-file" id="browse-file-text">Browse File</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <button class="btn btn-primary col-8" type="submit" id="upload-btn" disabled>Upload</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- /.tab-pane -->
                    {{-- <div class="tab-pane" id="tab_2">
                        <div id="media-files"></div>
                    </div> --}}
                    <!-- /.tab-pane -->
                </div>
                <!-- /.tab-content -->
            </div>
        </div>
    </div>
</div>

    <script src="{{ asset('/updated/plugins/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('/updated/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script type="text/javascript" src="{{  asset('js/printThis.js') }}"></script>
    
    <script>
        $(document).ready(function (){
            $(document).on('click', '#print-btn', function(e){
                e.preventDefault();
                $('#print-area').printThis({
                    copyTagClasses: true,
                    canvas: true,
                    importCSS: true,
                    importStyle: true,
                });
            });

            $(document).on('click', '.upload-image-placeholder', function(e) {
                e.preventDefault();

                $('#select-item-image-id').val($(this).attr('id'));
                $('#excel-column').val($(this).data('col'));
                $('#excel-row').val($(this).data('row'));
                $('#excel-project').val($('#project').val());
                $('#excel-filename').val($('#filename').val());
                $('#select-file-modal').modal('show');
            });

            function get_uploaded_brochure_images() {
                $.ajax({
					type: 'GET',
					url: '/get_uploaded_brochure_images',
					success: function(response){
						$('#media-files').html(response);
					},
				});
            }

            $('input[type="file"]').change(function(e){
                var fileName = e.target.files[0].name;
                $('#browse-file-text').text(fileName);
                $('#upload-btn').removeAttr('disabled');

                if (typeof (FileReader) != "undefined") {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        $('#img-preview').attr('src', e.target.result);
                    }
                    reader.readAsDataURL($(this)[0].files[0]);
                } else {
                    showNotification("danger", "This browser does not support FileReader.", "fa fa-info");
                }
            });

            $('#image-upload-form').submit(function(e) {
                e.preventDefault();

                $.ajax({
					type: 'POST',
					url: $(this).attr('action'),
                    data:  new FormData(this),
                    contentType: false,
                    cache: false,
                    processData:false,
					success: function(response){
                        if(response.status == 0){
    						showNotification("danger", response.message, "fa fa-info");
                        }else{
                            var item_image_id = $('#select-item-image-id').val();
                            $('#' + item_image_id).remove();
                            $('#' + item_image_id + '-image').removeClass('d-none').attr('src', $('#img-preview').attr('src'));
                            $('#' + item_image_id + '-image').parent().find('.upload-image-placeholder').eq(0).removeClass('d-none');
                           
                            $('#select-file-modal').modal('hide');
                        }
					},
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

            $(document).on('hidden.bs.modal', '.modal', function () {
                $(this).find('form')[0].reset();

                $('#img-preview').attr('src', '{{ asset('/storage/icon/no_img.png') }}');
                $('#browse-file-text').text('Browse File');
            });
        });
    </script>
  </body>
</html>
