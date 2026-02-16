<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Pipelines\LoginPipeline;
use App\Traits\GeneralTrait;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use GeneralTrait;

    public function __construct(
        protected LoginPipeline $loginPipeline
    ) {}

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
            return $this->loginPipeline->run($request);
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
