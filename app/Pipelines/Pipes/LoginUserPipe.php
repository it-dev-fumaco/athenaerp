<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use App\Models\User;
use Closure;
use Exception;
use Illuminate\Support\Facades\Auth;

class LoginUserPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $user = $passable->pipelineUser;

        if (! Auth::loginUsingId($user->frappe_userid)) {
            throw new Exception('<span class="blink_text">Login failed. Please try again.</span>');
        }

        User::where('name', $user->name)->update(['last_login' => now()->toDateTimeString()]);

        return $next($passable);
    }
}
