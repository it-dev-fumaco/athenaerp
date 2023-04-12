<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Validator;
use DB;

use App\LdapClasses\adLDAP;

class LoginController extends Controller
{
    public function view_login(){
        return view('login_v2');
    }

    public function login(Request $request){
        try {
            // validate the info, create rules for the inputs
            $rules = array(
                'email' => 'required'
            );

            $email = strpos($request->email, '@fumaco.local') ? $request->email : $request->email.'@fumaco.local';

            $validator = Validator::make($request->all(), $rules);

            // if the validator fails, redirect back to the form
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)
                    ->withInput($request->except('password'));
            }else{
                $adldap = new adLDAP();
                $authUser = $adldap->user()->authenticate(str_replace('@fumaco.local', null, $email), $request->password);

                if($authUser == true){
                    $user = DB::table('tabWarehouse Users')->where('wh_user', $email)->first();

                    if ($user) {
                        // attempt to do the login
                        if($user->enabled){
                            if(Auth::loginUsingId($user->frappe_userid)){
                                DB::table('tabWarehouse Users')->where('name', $user->name)->update(['last_login' => Carbon::now()->toDateTimeString()]);
                                return redirect('/');
                            } 
                        }else{
                            return redirect()->back()->withErrors('<span class="blink_text">Your account is disabled.</span>');
                        }
                    } else {        
                        // validation not successful, send back to form 
                        return redirect()->back()->withErrors('<span class="blink_text">Incorrect Username or Password</span>');
                    }
                }
                
                return redirect()->back()->withInput($request->except('password'))
                    ->withErrors('<span class="blink_text">Incorrect Username or Password</span>');
            }
        } catch (adLDAPException $e) {
            return redirect()->back()->withInput($request->except('password'))
                ->withErrors('<span class="blink_text">Cannot connect to LDAP.<br>Please contact your system administrator.</span>');
        }
    }

    public function logout(){
        Auth::logout();
        return redirect('/login');
    }
}


