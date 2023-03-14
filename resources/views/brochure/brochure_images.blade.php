<div class="card-body" style="min-height: 400px;">
    <div class="d-none">
        <input type="text" id="item-image-order-1" name="image_idx" placeholder="image_idx">
        <input type="text" id="item-code" name="item_code" value="" placeholder="item_code">
        <input type="text" name="existing" value="1" placeholder="existing">
    </div>
    @if ($current_images)
        <div class="row p-2">
            @foreach ($current_images as $cii)
            @php
                $img = $cii['filepath'];
                $img_webp = explode('.', $img)[0].'.webp';
            @endphp
            <div class="col-3 p-0">
                <label class="m-0 img-btn d-block">
                    <input type="radio" name="selected_image" value="{{ $cii['filename'] }}" required>
                    <div class="c-img rounded">
                        <picture>
                            <source srcset="{{ asset('storage/'.$img_webp) }}" type="image/webp" alt="{{ $img }}">
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

<script>
    $(document).ready(function(){
        $("input[name=selected_image]:radio").change(function (e) {
            e.preventDefault();
            $('#submit-selected-image-brochure').removeAttr('disabled');
        });

        // $('#image-upload-form-1').submit(function (e) {
        //     e.preventDefault();
        //     $.ajax({
        //         type: 'POST',
        //         url: $(this).attr('action'),
        //         data:  $(this).serialize(),
        //         success: function(response){
        //             if(response.status == 0){
        //                 showNotification("danger", response.message, "fa fa-info");
        //             }else{
        //                 var item_image_id = $('#item-image-container-id').val();
        //                 console.log($('#item-image-container-id').val());
        //                 $('#' + item_image_id).addClass('d-none');
        //                 $('#' + item_image_id + '-actual').removeClass('d-none');
        //                 $('#' + item_image_id + '-image').attr('src', '{{ asset("") }}' + response.src);
                        
        //                 $('#select-file-modal').modal('hide');
        //             }
        //         },
        //     });
        // });
    });
</script>