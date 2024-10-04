<div id="images-control" class="carousel slide" data-interval="false">
    <ol class="carousel-indicators d-none">
        @foreach ($images as $i => $image)
            <li data-slide-to="{{ $i }}" class="{{ $selected == $i ? 'active' : null }}"></li>
        @endforeach
    </ol>
    <div class="carousel-inner">
        @foreach ($images as $i => $image)
            <div class="carousel-item {{ $selected == $i ? 'active' : null }}" style="max-height: 860px;">
                @if (!$image->original)
                    <a href="/download_image/{{ $image->image }}" class="btn btn-primary download-img hidden-on-slide {{ $selected != $i ? 'd-none' : null }}" data-download="{{ $image->image_path }}"><i class="fa fa-download"></i> Download Image</a>
                @else
                    <a href="{{ asset("storage/$image->image_path") }}" class="btn btn-primary hidden-on-slide {{ $selected != $i ? 'd-none' : null }}" download="{{ asset("storage/$image->image_path") }}"><i class="fa fa-download"></i> Download Image</a>
                @endif
                <center>
                    <img class="modal-img" src="{{ asset("storage/$image->image_path") }}">
                </center>
                <span class="font-italic hidden-on-slide" style="font-size: 8pt; font-weight: 600; position: absolute; right: 10px; bottom: 2px; z-index: 999">Uploaded By: {{ $image->modified_by ? $image->modified_by : $image->owner }} - {{ Carbon\Carbon::parse($image->creation)->format('M. d, Y h:i A') }}</span>
            </div>
        @endforeach
    </div>
    @if (count($images) > 1)
        <a class="carousel-control-prev carousel-control" href="#images-control" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next carousel-control" href="#images-control" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a> 
    @endif
</div>
<style>
    .carousel-control-prev-icon,
    .carousel-control-next-icon {
        height: 100px;
        width: 100px;
        background-size: 100%, 100%;
        border-radius: 50%;
        background-image: none;
    }
    .carousel-control-next-icon:after{
        content: '>';
        font-size: 55px;
        color: rgba(0,0,0,0.4);
    }

    .carousel-control-prev-icon:after {
        content: '<';
        font-size: 55px;
        color: rgba(0,0,0,0.4);
    }

    .download-img{
        font-size: 9pt;
        position: absolute;
        right: 15px !important;
        top: 15px !important;
        z-index: 9999 !important
    }

    #images-control img{
        flex-shrink:0;
        -webkit-flex-shrink: 0;
        max-width: 100%;
        max-height: 840px;
        padding: 10px 10px 20px 10px;
        background-color: #fff;
        border-radius: 5px;
    }
</style>
<script>
    // $(document).ready(function (){
        $('#images-control').on('slide.bs.carousel', function (){
            $('.hidden-on-slide').addClass('d-none');
            $('.carousel-control').addClass('d-none');
        });

        $('#images-control').on('slid.bs.carousel', function (){
            $(this).find('.active').find('a').removeClass('d-none');
            $(this).find('.active').find('span').removeClass('d-none');
            $('.carousel-control').removeClass('d-none');
        });
    // });
</script>