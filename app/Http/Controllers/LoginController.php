<?php

namespace App\Http\Controllers;
use Exception;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Validator;
use DB;

use App\LdapClasses\adLDAP;
use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;

class LoginController extends Controller
{
    use ERPTrait, GeneralTrait;
    public function view_login(){

        // $so = DB::table('tabSales Order Item')->where('parent', 'SO-28492')
        //     ->select('item_code','description', 'qty', 'uom', 'base_rate', 'base_amount')->get();

        // $dr = DB::table('tabDelivery Note as dr')->join('tabDelivery Note Item as dri', 'dr.name', 'dri.parent')
        //     ->where('dr.sales_order', 'SO-28492')
        //     ->orWhere('dri.against_sales_order', 'SO-28492')
        //     ->select('dr.name', 'dr.delivery_date', 'dr.is_return', 'dr.return_against', 'dri.item_code', 'dri.base_rate', 'dri.qty', 'dri.base_amount')
        //     ->get();

        // $dr_returns = $dr->filter(function($i) {
        //     return $i->is_return > 0;
        // })->groupBy('item_code')->toArray();

        // $drs= $dr->filter(function($i) {
        //     return $i->is_return <= 0;
        // })->groupBy('item_code')->toArray();


        // return view('test', compact('so', 'drs', 'dr_returns'));

        // return $dr_returns;
        if(Auth::check()){
            return redirect('/');
            
        }

        $bg1 = $this->base64_image('/img/img1.png');
        $bg2 = $this->base64_image('/img/img2.png');

        return view('login_v2', compact('bg1', 'bg2'));
    }

    public function login (Request $request){
        try {
            $email = str_replace('@fumaco.com', null, $request->email);
            $email = str_replace('@fumaco.local', null, $email);

            $email = "$email@fumaco.com";
            $password = $request->password;
    
            $erp_api_base_url = env('ERP_API_BASE_URL');
            $response = Http::post("$erp_api_base_url/api/method/login", [
                'usr' => $email,
                'pwd' => $password,
            ]);
    
            if ($response->successful()) {
                $user = DB::table('tabWarehouse Users')
                    ->where('wh_user', $email)
                    ->first();

                if (Auth::loginUsingId($user->frappe_userid)) {
                    if (!Auth::user()->api_key || !Auth::user()->api_secret) {
                        $api_credentials = $this->generate_api_credentials();
            
                        if (!$api_credentials['success']) {
                            Auth::logout();
                            throw new Exception($api_credentials['message']);
                        }
                    }
            
                    DB::table('tabWarehouse Users')
                        ->where('name', $user->name)
                        ->update(['last_login' => Carbon::now()->toDateTimeString()]);
            
                    return redirect('/');
                }
            }

            throw new Exception('<span class="blink_text">Incorrect Username or Password</span>');
        } catch (\Throwable $th) {
            return redirect()->back()->withInput($request->except('password'))->withErrors($th->getMessage());
        }
    }

    // public function login(Request $request){
    //     try {
    //         $rules = ['email' => 'required'];
    //         $email = $request->email;
        
    //         if (!strpos($email, '@fumaco.local')) {
    //             $email .= '@fumaco.local';
    //         }
        
    //         $validator = Validator::make($request->all(), $rules);
        
    //         if ($validator->fails()) {
    //             throw new Exception($validator->first());
    //         }
        
    //         $adldap = new adLDAP();
    //         $username = str_replace(['@fumaco.local', '@fumaco.com'], '', $email);
    //         $authUser = $adldap->user()->authenticate($username, $request->password);

    //         if (!$authUser) {
    //             throw new Exception('<span class="blink_text">Incorrect Username or Password</span>');
    //         }
        
    //         $user = DB::table('tabWarehouse Users')
    //             ->where('wh_user', $email)
    //             ->orWhere('wh_user', str_replace('@fumaco.local', '@fumaco.com', $email))
    //             ->first();
        
    //         if (!$user) {
    //             throw new Exception('<span class="blink_text">Incorrect Username or Password</span>');
    //         }
        
    //         if (!$user->enabled) {
    //             throw new Exception('<span class="blink_text">Your account is disabled.</span>');
    //         }
        
    //         if (Auth::loginUsingId($user->frappe_userid)) {
    //             if (!Auth::user()->api_key || !Auth::user()->api_secret) {
    //                 $api_credentials = $this->generate_api_credentials();
        
    //                 if (!$api_credentials['success']) {
    //                     Auth::logout();
    //                     throw new Exception($api_credentials['message']);
    //                 }
    //             }
        
    //             DB::table('tabWarehouse Users')
    //                 ->where('name', $user->name)
    //                 ->update(['last_login' => Carbon::now()->toDateTimeString()]);
        
    //             return redirect('/');
    //         }
    //         throw new Exception('<span class="blink_text">Login failed. Please try again.</span>');
    //     } catch (Exception $e) {
    //         throw $e;
    //         return redirect()->back()
    //             ->withInput($request->except('password'))
    //             ->withErrors($e->getMessage());
    //     }
    // }

    //     try {
    //         // validate the info, create rules for the inputs
    //         $rules = array(
    //             'email' => 'required'
    //         );

    //         $email = strpos($request->email, '@fumaco.local') ? $request->email : $request->email.'@fumaco.local';

    //         $validator = Validator::make($request->all(), $rules);

    //         // if the validator fails, redirect back to the form
    //         if ($validator->fails()) {
    //             return redirect()->back()->withErrors($validator)
    //                 ->withInput($request->except('password'));
    //         }else{
    //             $adldap = new adLDAP();
    //             $authUser = $adldap->user()->authenticate(str_replace('@fumaco.local', null, $email), $request->password);

    //             if($authUser == true){
    //                 $user = DB::table('tabWarehouse Users')->where('wh_user', $email)->first();
    //                 if (!$user) {
    //                     $user = DB::table('tabWarehouse Users')->where('wh_user', str_replace('@fumaco.local', '@fumaco.com', $email))->first();
    //                 }

    //                 if ($user) {
    //                     // attempt to do the login
    //                     if($user->enabled){
    //                         if(Auth::loginUsingId($user->frappe_userid)){
    //                             if(!Auth::user()->api_key || !Auth::user()->api_secret){
    //                                 $api_credentials = $this->generate_api_credentials();

    //                                 if(!$api_credentials['success']){
    //                                     Auth::logout();
    //                                     return redirect()->back()->withInput($request->except('password'))
    //                                     ->withErrors('<span class="blink_text">An error occured.<br>Please contact your system administrator.</span>');
    //                                 }
    //                             }

    //                             DB::table('tabWarehouse Users')->where('name', $user->name)->update(['last_login' => Carbon::now()->toDateTimeString()]);

    //                             return redirect('/');
    //                         } 
    //                     }else{
    //                         return redirect()->back()->withErrors('<span class="blink_text">Your account is disabled.</span>');
    //                     }
    //                 } else {        
    //                     // validation not successful, send back to form 
    //                     return redirect()->back()->withErrors('<span class="blink_text">Incorrect Username or Password</span>');
    //                 }
    //             }
                
    //             return redirect()->back()->withInput($request->except('password'))
    //                 ->withErrors('<span class="blink_text">Incorrect Username or Password</span>');
    //         }
    //     } catch (adLDAPException $e) {
    //         return redirect()->back()->withInput($request->except('password'))
    //             ->withErrors('<span class="blink_text">Cannot connect to LDAP.<br>Please contact your system administrator.</span>');
    //     }
    // }

    public function logout(){
        Auth::logout();
        return redirect('/login');
    }
}


