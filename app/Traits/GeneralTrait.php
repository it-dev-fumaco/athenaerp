<?php

namespace App\Traits;
use Illuminate\Support\Facades\Storage;

trait GeneralTrait
{
    public function base64_image($file, $original = 0){
        if(!$file){
            return null;
        }

        $webp_file = explode('.', $file)[0].'.webp';
        if(Storage::exists($webp_file) && !$original){
            $path = $webp_file;
        }else if(Storage::exists($file)){
            $path = $file;
        }else{
            $path = "/icon/no_img.webp";
        }

        $data = Storage::get($path);
        $base64 = base64_encode($data);
        $mimetype = Storage::mimeType($path);

        return "data:$mimetype;base64,$base64";
    }
}