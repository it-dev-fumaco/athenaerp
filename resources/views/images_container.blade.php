<div id="images-control" class="carousel slide" data-ride="false">
    <ol class="carousel-indicators d-none">
        @foreach ($images as $i => $image)
            <li data-slide-to="{{ $i }}" class="{{ $loop->first ? 'active' : null }}"></li>
        @endforeach
    </ol>
    <div class="carousel-inner">
        @foreach ($images as $image)
            <div class="carousel-item {{ $loop->first ? 'active' : null }}">
                @php
                   $webp = explode('.', $image)[0].'.webp'; 
                @endphp
                <center>
                    @if(!Storage::disk('public')->exists('/img/'.$webp))
                        <img src="{{ asset('storage/img/'.$image) }}" class="img w-100">
                    @elseif(!Storage::disk('public')->exists('/img/'.$image))
                        <img src="{{ asset('storage/img/'.$webp) }}" class="img w-100">
                    @else
                        <picture>
                            <source srcset="{{ asset('storage/img/'.$webp) }}" type="image/webp">
                            <source srcset="{{ asset('storage/img/'.$image) }}" type="image/jpeg">
                            <img src="{{ asset('storage/img/'.$image) }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $image)[0], '-') }}" class="img-responsive hover" style="width: 70%;">
                        </picture>
                    @endif
                </center>
                <div class="btn-group pt-2 float-right" role="group" aria-label="Basic example" style="z-index: 9999 !important;">
                    <a class="btn btn-app bg-primary" href="{{ asset('storage/img/'.$image) }}" style="font-size: 9pt;" download="{{ asset('storage/'.$image) }}">
                        <i class="fas fa-download pb-1" style="font-size: 10pt;"></i> JPEG
                    </a>
                    <a class="btn btn-app bg-primary" href="{{ asset('storage/img/'.$webp) }}" style="font-size: 9pt;" download="{{ asset('storage/'.$webp) }}">
                        <i class="fas fa-download pb-1" style="font-size: 10pt;"></i> WEBP
                    </a>
                </div>
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
</style>