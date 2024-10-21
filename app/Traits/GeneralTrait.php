<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

use Mail;
use Illuminate\Support\Facades\Response;
trait GeneralTrait
{
    public function base64_image($file, $original = 0){
        // $file = explode('.', $file);
        // $file = $file[0].'.webp';
        return asset("storage/$file");
        // if(!$file){
        //     return null;
        // }

        // $webp_file = explode('.', $file)[0].'.webp';
        // if(Storage::exists($webp_file) && !$original){
        //     $path = $webp_file;
        // }else if(Storage::exists($file)){
        //     $path = $file;
        // }else{
        //     $path = "/icon/no_img.webp";
        // }

        // $data = Storage::get($path);
        // $base64 = base64_encode($data);
        // $mimetype = Storage::mimeType($path);

        // return "data:$mimetype;base64,$base64";
    }

    public function sendMail($template, $data, $recipient, $subject = null){
        try {
            Mail::send($template, $data, function($message) use ($recipient, $subject){
                $message->to($recipient);
                $message->subject($subject);
            });

            return ['success' => 1, 'message' => 'Email Sent!'];
        } catch (\Throwable $th) {
            return ['success' => 0, 'message' => $th->getMessage()];
        }
    }

}