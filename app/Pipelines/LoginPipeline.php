<?php

namespace App\Pipelines;

use App\Pipelines\Pipes\EnsureApiCredentialsPipe;
use App\Pipelines\Pipes\FindUserPipe;
use App\Pipelines\Pipes\LoginUserPipe;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Redirector;

class LoginPipeline
{
    public function __construct(
        protected Pipeline $pipeline,
        protected Redirector $redirect
    ) {}

    /**
     * Run the login pipeline. Expects a validated FormRequest (LoginRequest).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function run($request)
    {
        return $this->pipeline
            ->send($request)
            ->through([
                FindUserPipe::class,
                EnsureApiCredentialsPipe::class,
                LoginUserPipe::class,
            ])
            ->then(fn ($passable) => $this->redirect->to('/'));
    }
}
