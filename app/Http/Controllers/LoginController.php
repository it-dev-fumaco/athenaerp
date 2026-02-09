<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Exception;

class LoginController extends Controller
{
    use ERPTrait, GeneralTrait;

    public function viewLogin()
    {
        if (Auth::check()) {
            return redirect('/');
        }

        $backgroundImage1 = $this->base64Image('/img/img1.png');
        $backgroundImage2 = $this->base64Image('/img/img2.png');

        return view('login_v2', compact('backgroundImage1', 'backgroundImage2'));
    }

    public function login(LoginRequest $request)
    {
        try {
            $email = str_replace(['@fumaco.com', '@fumaco.local'], '', $request->email);
            $email = "$email@fumaco.com";

            $user = User::whereIn('wh_user', [$email, str_replace('@fumaco.com', '@fumaco.local', $email)])
                ->first();

            if (!$user) {
                throw new Exception('<span class="blink_text">Incorrect Username or Password</span>');
            }

            if (Auth::loginUsingId($user->frappe_userid)) {
                if (!Auth::user()->api_key || !Auth::user()->api_secret) {
                    $apiCredentials = $this->generateApiCredentials();

                    if (!$apiCredentials['success']) {
                        Auth::logout();
                        throw new Exception($apiCredentials['message']);
                    }
                }

                User::where('name', $user->name)->update(['last_login' => Carbon::now()->toDateTimeString()]);

                return redirect('/');
            }

            throw new Exception('<span class="blink_text">Login failed. Please try again.</span>');
        } catch (\Throwable $th) {
            return redirect()->back()->withInput($request->except('password'))->withErrors($th->getMessage());
        }
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }
}
