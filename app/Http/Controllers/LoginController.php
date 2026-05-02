<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Models\UserLoginActivity;
use App\Pipelines\LoginPipeline;
use App\Services\UserLoginActivityLogger;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use GeneralTrait;

    public function __construct(
        protected LoginPipeline $loginPipeline,
        protected UserLoginActivityLogger $loginActivityLogger
    ) {}

    public function viewLogin()
    {
        if (Auth::check()) {
            return redirect('/');
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        try {
            return $this->loginPipeline->run($request);
        } catch (\Throwable $th) {
            $pipelineUser = $request->pipelineUser ?? null;
            $this->loginActivityLogger->record(
                $request,
                UserLoginActivity::STATUS_FAILED,
                (string) $request->input('email', ''),
                $pipelineUser instanceof User ? $pipelineUser : null
            );

            return redirect()->back()->withInput($request->except('password'))->withErrors($th->getMessage());
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
