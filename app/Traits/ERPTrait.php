<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use DB;

trait ERPTrait{
    private function erpOperation($method, $doctype, $name = null, $body = [], $system_generated = false){
        try {
            $erp_api_base_url = env('ERP_API_BASE_URL');
            $erp_api_key = Auth::user()->api_key;
            $erp_api_secret_key = Auth::user()->api_secret;
            if($system_generated){
                $erp_api_key = env('ERP_API_KEY');
                $erp_api_secret_key = env('ERP_API_SECRET_KEY');
            }

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

    public function revertChanges($state_before_update){
        DB::connection('mysql')->beginTransaction();
        try {
            foreach($state_before_update as $doctype => $values){
                foreach($values as $id => $value){
                    if(!is_array($value) && !is_object($value)){
                        DB::connection('mysql')->table("tab$doctype")->where('name', $id)->delete();
                    }else{
                        $value = collect($value)->except(['name', 'owner', 'creation', 'docstatus', 'doctype', 'parent', 'parentfield', 'parenttype'])->toArray();
                        DB::connection('mysql')->table("tab$doctype")->where('name', $id)->update($value);
                    }
                }
            }
    
            DB::connection('mysql')->commit();
            return 1;
        } catch (\Throwable $th) {
            DB::connection('mysql')->rollBack();
            return 0;
        }
        
    }

    public function generateRandomString($length = 15) {
        return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', $length)), 0, $length);
    }

    public function generate_api_credentials(){
        try {
            $loggedInUser = str_replace('fumaco.local', 'fumaco.com', Auth::user()->wh_user);
            $existing_key = $this->erpOperation('get', 'User', $loggedInUser, [], true);
            if(!isset($existing_key['data'])){ // Promodisers
                $loggedInUser = Auth::user()->wh_user;
                $existing_key = $this->erpOperation('get', 'User', $loggedInUser, [], true);
            }
            $existing_key = $existing_key['data']['api_key'] ?? null;

            $tokens = [
                'api_key' => $existing_key ?? $this->generateRandomString(),
                'api_secret' => $this->generateRandomString()
            ];

            $user = $this->erpOperation('put', 'User', $loggedInUser, $tokens, true);

            if(!isset($user['data'])){
                $error = isset($user['exception']) ? $user['exception'] : 'An error occured while generating API tokens';
                throw new \Exception($error);
            }

            $warehouse_user = $this->erpOperation('put', 'Warehouse Users', Auth::user()->name, $tokens, true);

            if(!isset($warehouse_user['data'])){
                $error = isset($warehouse_user['exception']) ? $warehouse_user['exception'] : 'An error occured while generating API tokens';
                throw new \Exception($error);
            }

            $user = Auth::user();
            $user->api_key = $tokens['api_key'];
            $user->api_secret = $tokens['api_secret'];
            $user->save();
            
            return ['success' => 1, 'message' => 'API Credentials Created!'];
        } catch (\Exception $th) {
            // throw $th;
            return ['success' => 0, 'message' => $th->getMessage()];
        }
    }
}