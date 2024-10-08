<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        if(Auth::check()){
            return redirect('/');
            
        }

        $bg1 = $this->base64_image('/img/img1.png');
        $bg2 = $this->base64_image('/img/img2.png');

        return view('login_v2', compact('bg1', 'bg2'));
    }

    public function login(Request $request){
        try {
            $rules = ['email' => 'required'];
            $email = $request->email;
        
            if (!strpos($email, '@fumaco.local')) {
                $email .= '@fumaco.local';
            }
        
            $validator = Validator::make($request->all(), $rules);
        
            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput($request->except('password'));
            }
        
            $adldap = new adLDAP();
            $username = str_replace(['@fumaco.local', '@fumaco.com'], '', $email);
            $authUser = $adldap->user()->authenticate($username, $request->password);

            if (!$authUser) {
                return redirect()->back()
                    ->withInput($request->except('password'))
                    ->withErrors('<span class="blink_text">Incorrect Username or Password</span>');
            }
        
            $user = DB::table('tabWarehouse Users')
                ->where('wh_user', $email)
                ->orWhere('wh_user', str_replace('@fumaco.local', '@fumaco.com', $email))
                ->first();
        
            if (!$user) {
                return redirect()->back()
                    ->withErrors('<span class="blink_text">Incorrect Username or Password</span>');
            }
        
            if (!$user->enabled) {
                return redirect()->back()
                    ->withErrors('<span class="blink_text">Your account is disabled.</span>');
            }
        
            if (Auth::loginUsingId($user->frappe_userid)) {
                if (!Auth::user()->api_key || !Auth::user()->api_secret) {
                    $api_credentials = $this->generate_api_credentials();
        
                    if (!$api_credentials['success']) {
                        Auth::logout();
                        return redirect()->back()
                            ->withInput($request->except('password'))
                            ->withErrors('<span class="blink_text">An error occurred.<br>Please contact your system administrator.</span>');
                    }
                }
        
                DB::table('tabWarehouse Users')
                    ->where('name', $user->name)
                    ->update(['last_login' => Carbon::now()->toDateTimeString()]);
        
                return redirect('/');
            }
        
            return redirect()->back()
                ->withErrors('<span class="blink_text">Login failed. Please try again.</span>');
        } catch (adLDAPException $e) {
            return redirect()->back()
                ->withInput($request->except('password'))
                ->withErrors('<span class="blink_text">Cannot connect to LDAP.<br>Please contact your system administrator.</span>');
        }
    }

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


