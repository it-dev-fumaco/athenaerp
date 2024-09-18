<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait ERPTrait{
    private function erpOperation($method, $doctype, $name = null, $body = []){
        try {
            // ini_set('max_execution_time', 3600);
            $erp_api_key = env('ERP_API_KEY');
            $erp_api_secret_key = env('ERP_API_SECRET_KEY');
            $erp_api_base_url = env('ERP_API_BASE_URL');

            $url = "$erp_api_base_url/api/resource/$doctype";
            if($name){
                $url = "$url/$name";
            }
            $data = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "token $erp_api_key:$erp_api_secret_key"
            ])->$method($url, $body);

            return json_decode($data, true);
        } catch (\Throwable $th) {
            // throw $th;
            return ['error' => 1, 'message' => $th->getMessage()];
        }
    }
}