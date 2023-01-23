
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
                padding: 8px 10px;
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
            }
            .upload-btn-wrapper i {
                display: block;
                font-size: 100px;
                color: #d5d8dc;
                opacity: 50%;
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
 
<div id="table-of-contents-sidebar">
    <div style="display: block; text-align: center; margin: 20px 0;">
        <a href="/brochure" style="padding: 8px 10px 8px 10px !important; margin: 15px !important; border-radius: 5px; background-color: #f8f9f9; font-size: 20px; border: 1px solid #d6dbdf; color:#d5d8dc; cursor: pointer;">
            <i class="fas fa-home"></i>
        </a>
    </div>
    <h3 style="text-align: center; font-weight: bolder; text-transform: uppercase; margin: 15px 0 8px 0 !important; letter-spacing: 0.5px;">Table of Contents</h3>
    {{-- <hr style="margin: 0 5px 0 5px; padding: 0 !important; border: 1px solid;"> --}}
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
    @foreach ($content as $row)
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
                                <div class="upload-image-placeholder">
                                    <div class="upload-btn-wrapper">
                                        <button class="custom-upload-btn">
                                            <i class="far fa-image"></i>
                                            <span>Add Image</span>
                                        </button>
                                        <input type="file" name="myfile" />
                                    </div>
                                </div>
                                <br>
                                <div class="upload-image-placeholder">
                                    <div class="upload-btn-wrapper">
                                        <button class="custom-upload-btn">
                                            <i class="far fa-image"></i>
                                            <span>Add Image</span>
                                        </button>
                                        <input type="file" name="myfile" />
                                    </div>
                                </div>
                                <br>
                                <div class="upload-image-placeholder">
                                    <div class="upload-btn-wrapper">
                                        <button class="custom-upload-btn">
                                            <i class="far fa-image"></i>
                                            <span>Add Image</span>
                                        </button>
                                        <input type="file" name="myfile" />
                                    </div>
                                </div>

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


    <script src="{{ asset('/updated/plugins/jquery/jquery.min.js') }}"></script>
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
        });
    </script>
    
  </body>
</html>
