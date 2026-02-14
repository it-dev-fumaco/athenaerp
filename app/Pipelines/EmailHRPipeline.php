<?php

namespace App\Pipelines;

use App\Pipelines\Pipes\BuildHrMissingReportListPipe;
use App\Pipelines\Pipes\ComputeCutoffPeriodPipe;
use App\Pipelines\Pipes\LoadActivePromodisersPipe;
use App\Pipelines\Pipes\LoadCutoffSettingsPipe;
use App\Pipelines\Pipes\SendHrEmailsPipe;
use Illuminate\Pipeline\Pipeline;

class EmailHRPipeline
{
    public function __construct(
        protected Pipeline $pipeline
    ) {}

    /**
     * Run the HR email pipeline. Passable is a fresh object; pipes attach cutoff settings, period, promodisers, report list, and send emails.
     *
     * @param  object  $passable  Fresh object (e.g. (object) [])
     * @return mixed Return value from then() – typically not used; command returns SUCCESS
     */
    public function run(object $passable)
    {
        return $this->pipeline
            ->send($passable)
            ->through([
                LoadCutoffSettingsPipe::class,
                ComputeCutoffPeriodPipe::class,
                LoadActivePromodisersPipe::class,
                BuildHrMissingReportListPipe::class,
                SendHrEmailsPipe::class,
            ])
            ->then(fn ($p) => $p);
    }
}
