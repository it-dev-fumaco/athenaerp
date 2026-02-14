<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use App\Models\User;
use Closure;
use Exception;

class FindUserPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $email = str_replace(['@fumaco.com', '@fumaco.local'], '', $passable->email);
        $email = "{$email}@fumaco.com";

        $user = User::whereIn('wh_user', [
            $email,
            str_replace('@fumaco.com', '@fumaco.local', $email),
        ])->first();

        if (! $user) {
            throw new Exception('<span class="blink_text">Incorrect Username or Password</span>');
        }

        $passable->pipelineUser = $user;

        return $next($passable);
    }
}
