<div id="images-control" class="carousel slide" data-ride="false">
    <ol class="carousel-indicators d-none">
        @foreach ($images as $i => $image)
            <li data-slide-to="{{ $i }}" class="{{ $loop->first ? 'active' : null }}"></li>
        @endforeach
    </ol>
    <div class="carousel-inner">
        @foreach ($images as $image)
            <div class="carousel-item {{ $loop->first ? 'active' : null }}" style="max-height: 860px;">
                @php
                   $webp = explode('.', $image)[0].'.webp'; 
                @endphp
                @if (Storage::disk('public')->exists('/img/'.$image))
                    <a href="{{ asset('storage/img/'.$image) }}" class="btn btn-primary download-img" download="{{ $image }}"><i class="fa fa-download"></i></a>
                @endif
                <center>
                    @if(!Storage::disk('public')->exists('/img/'.$webp))
                        <img src="{{ asset('storage/img/'.$image) }}">
                    @elseif(!Storage::disk('public')->exists('/img/'.$image))
                        <img src="{{ asset('storage/img/'.$webp) }}">
                    @else
                        <picture>
                            <source srcset="{{ asset('storage/img/'.$webp) }}" type="image/webp">
                            <source srcset="{{ asset('storage/img/'.$image) }}" type="image/jpeg">
                            <img src="{{ asset('storage/img/'.$image) }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $image)[0], '-') }}" class="img-responsive hover">
                        </picture>
                    @endif
                </center>
            </div>
        @endforeach
    </div>
    @if (count($images) > 1)
        <a class="carousel-control-prev" href="#images-control" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#images-control" role="button" data-slide="next">
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
        font-size: 12pt;
        position: absolute;
        right: 10px !important;
        top: 10px !important;
        z-index: 9999 !important
    }

    #images-control img{
        width: 100%;
        object-fit: fill !important;
    }
</style>