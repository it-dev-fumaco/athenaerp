<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Product Brochure Preview</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <style>
            @font-face{
                font-family: 'Poppins-Regular';
                src: url('{{ public_path("font/Poppins/Poppins-Regular.ttf") }}') format('truetype');
            }

            @font-face{
                font-family: 'Poppins-Bold';
                src: url('{{ public_path("font/Poppins/Poppins-Bold.ttf") }}') format('truetype');
            }

            @font-face{
                font-family: 'Poppins-Light';
                src: url('{{ public_path("font/Poppins/Poppins-Light.ttf") }}') format('truetype');
            }

            body, .regular-font{
                font-family: 'Poppins-Regular';
            }

            .thin{
                font-family: 'Poppins-Light';
            }

            strong, b, .bold{
                font-family: 'Poppins-Bold' !important;
            }

            header {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                padding: .5in .5in 0 .5in;
                display: block !important;
            }

            footer {
                position: fixed; 
                bottom: 0; 
                left: 0px; 
                right: 0px;
                height: 70px; 
                padding: 0 .2in 0 .25in;
                display: block !important;
            }

            main {
                height: 8.5in;
            }

            .left-container{
                display: inline-block;
                width: 44%;
                float: left;
            }
            .right-container{
                display: inline-block;
                width: 55%;
                float: left;
            }
            .page-break {
                page-break-after: always;
            }
        </style>
    </head>
    @php
        $imageToDataUri = function ($path) {
            if (!$path || !is_string($path) || !file_exists($path)) {
                return null;
            }
            $mime = @mime_content_type($path) ?: 'image/png';
            $data = @file_get_contents($path);
            return $data !== false ? 'data:' . $mime . ';base64,' . base64_encode($data) : null;
        };
        $fumacoLogoPath = public_path('storage/fumaco_logo.png');
        if (!file_exists($fumacoLogoPath)) {
            $fumacoLogoPath = storage_path('app/public/fumaco_logo.png');
        }
        $fumacoLogoSrc = $imageToDataUri($fumacoLogoPath);
        $margin = '1.2in';
        $rows = 1;
        if(strlen($project) > 29){
            $rows = (strlen($project) - 34) / 42;
            $rows = (int)$rows < 1 ? 1 : (int)$rows + 1.3;

            $margin = (1.5 + (.13 * $rows)).'in' ;
        }
    @endphp
<body style="border: 1px solid; padding: {{ $margin }} .5in 0 .5in;">
    <header style="height: auto !important;">
        <table style="width: 100% !important; border-collapse: collapse;"> 
            <tr>
                <td style="width: 43%; padding: 0 !important; vertical-align: top !important">
                    @if($fumacoLogoSrc)<img src="{{ $fumacoLogoSrc }}" alt="" style="width: 230px;">@endif
                </td>
                <td style="width: 55%; font-size: 11pt;">
                    <p style="text-transform: uppercase !important; margin: 0; line-height: .75rem;">
                        PROJECT: <span class="bold">{{ $project }}</span>
                    </p>
                    <p style="margin-top: 15px !important; margin: 0;">LUMINAIRE SPECIFICATION AND INFORMATION</p>
                </td>
            </tr>
        </table>
    </header>

    <footer style="border: 1px solid #0c0c0c !important;">
        <table style="width: 100% !important; border-collapse: collapse;">
            <tr>
                <td style="width: 28%; vertical-align: top; padding-top: 15px;">
                    @if($fumacoLogoSrc)<img src="{{ $fumacoLogoSrc }}" style="width: 80%;">@endif
                </td>
                <td style="width: 15%;font-size: .6rem; padding: 0 15px 10px 0 !important; line-height: .5rem;">
                    www.fumaco.com
                    <br><br><br><br><br>
                </td>
                <td style="width: 56%; font-size: .6rem; padding: 0 15px 10px 0 !important; line-height: .5rem;">
                    Plant: 35 Pleasant View Drive, Bagbaguin, Caloocan City <br>
                    <span style="white-space: nowrap">Sales & Showroom: 420 Ortigas Ave. cor. Xavier St., Greenhills, San Juan City</span><br>
                    Tel. No.: (632) 721-0362 to 66 <br>
                    Fax No.: (632) 721-0361 <br>
                    Email Address: sales@fumaco.com
                </td>
            </tr>
        </table>
    </footer>

    @foreach ($content as $r => $row)
    @if (!collect($row['attrib'])->filter()->values()->all())
        @continue
    @endif
    <main>
        <div style="display: block; padding-top: 5px !important; clear: both;">
            <div style="border: 2.5px solid #000; margin: 0 0 15px 0 !important; padding: 0 !important; vertical-align: top !important;">
                <span class="bold" style="font-size: 14pt; color: #FF611F; vertical-align: top !important; position: relative; top: -2; left: 2">
                    {{ $row['item_name'] }}
                </span>
            </div>
            <div class="left-container">
                <div style="width: 240px !important;">
                    @php $slot2_src = null; @endphp
                    @for ($i = 1; $i <= 3; $i++)
                        @php
                            $imgSrc = isset($row['image_data_uris'][$i]) && $row['image_data_uris'][$i] ? $row['image_data_uris'][$i] : null;
                            if (!$imgSrc && isset($row['images']['image'.$i]) && $row['images']['image'.$i]) {
                                $imgPath = null;
                                if (isset($isStandard) && $isStandard) {
                                    $filepath = $row['images']['image'.$i]['filepath'] ?? null;
                                    if ($filepath) {
                                        $imgPath = public_path($filepath);
                                        if (!file_exists($imgPath)) {
                                            $imgPath = storage_path('app/public/'.preg_replace('#^storage/#', '', $filepath));
                                        }
                                    }
                                } else {
                                    $imgName = trim((string)($row['images']['image'.$i] ?? ''));
                                    if ($imgName) {
                                        $tryPaths = [
                                            public_path('storage/brochures/'.strtoupper($project).'/'.$imgName),
                                            storage_path('app/public/brochures/'.strtoupper($project).'/'.$imgName),
                                            public_path('storage/brochures/'.$imgName),
                                            storage_path('app/public/brochures/'.$imgName),
                                        ];
                                        foreach ($tryPaths as $p) {
                                            if ($p && file_exists($p)) {
                                                $imgPath = $p;
                                                break;
                                            }
                                        }
                                    }
                                }
                                $imgSrc = ($imgPath ?? null) && file_exists($imgPath ?? '') ? $imageToDataUri($imgPath) : null;
                            }
                            if ($i === 2) {
                                $slot2_src = $imgSrc;
                            }
                            if ($i === 3 && isset($slot2_src) && $slot2_src !== null && $imgSrc === $slot2_src) {
                                $imgSrc = null;
                            }
                        @endphp
                        @if ($imgSrc)
                            <img src="{{ $imgSrc }}" width="100%" style="border: 2px solid #1C2833; margin-bottom: 15px !important; max-height: 775px !important;">
                        @endif
                    @endfor
                    &nbsp;
                </div>
            </div>
            <div class="right-container">
                <div style="min-height: 230px">
                    <span class="bold" style="font-size: 10pt; margin-top: 0 !important">Fitting Type / Reference:</span><br>
                    <span class="bold" style="font-size: 14pt; margin-top: 0 !important; color:#FF611F;">{{ $row['reference'] }}</span><br>
                    <span class="bold" style="margin-top: 20px !important; font-size: 10pt;">Description:</span>
                    <span style="font-size: 10pt; margin-top: 10px !important; display: block; line-height: 12px;">{{ $row['description'] }}</span><br>
                    @if ($row['location'])
                        <span class="bold" style="margin-top: 20px !important; font-size: 10pt;">Location:</span><br>
                        <span style="font-size: 10pt; margin-top: 20px !important;">{{ $row['location'] }}</span><br>
                    @endif
                </div>
                <table border="0" style="border-collapse: collapse; width: 100%; font-size: 8pt; line-height: .5rem">
                    @foreach ($row['attributes'] as $val)
                        @php
                            $attr_name_ok = !in_array($val['attribute_name'], ['Image 1', 'Image 2', 'Image 3']);
                            $attr_val_is_image = preg_match('/\.(png|jpg|jpeg|gif|webp|bmp)$/i', trim((string)$val['attribute_value']));
                        @endphp
                        @if ($val['attribute_value'] && $attr_name_ok && !$attr_val_is_image)
                            <tr>
                                <td class="regular-font" style="padding: 5px 0 5px 0 !important;width: 40%;">{{ $val['attribute_name'] }}</td>
                                <td class="bold" style="padding: 5px 0 5px 0 !important;width: 60%;">{{ $val['attribute_value'] }}</td>
                            </tr>
                        @endif
                    @endforeach
                    @if (isset($row['remarks']) && $row['remarks'])
                        <tr>
                            <td class="regular-font" style="padding: 5px 0 5px 0 !important;width: 40%;">Remarks</td>
                            <td class="bold" style="padding: 5px 0 5px 0 !important;width: 60%;">{{ $row['remarks'] }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>
    </main>
    {{-- Next Page --}}
    @if (!$loop->last)
        <div class="page-break"></div>
    @endif
    @endforeach
  </body>
</html>