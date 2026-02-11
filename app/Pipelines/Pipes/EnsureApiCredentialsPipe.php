<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use App\Traits\ERPTrait;
use Closure;
use Exception;
use Illuminate\Support\Facades\Auth;

class EnsureApiCredentialsPipe implements Pipe
{
    use ERPTrait;

    public function handle(mixed $passable, Closure $next): mixed
    {
        $user = $passable->pipelineUser;

        if (! $user->api_key || ! $user->api_secret) {
            $apiCredentials = $this->generateApiCredentials();

            if (! $apiCredentials['success']) {
                Auth::logout();
                throw new Exception($apiCredentials['message']);
            }
        }

        return $next($passable);
    }
}
