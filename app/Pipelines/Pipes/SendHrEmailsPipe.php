<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;
use Illuminate\Support\Facades\Mail;

class SendHrEmailsPipe implements Pipe
{
    protected const RECEIVERS = ['hr@fumaco.local', 'consignment@fumaco.local'];

    public function handle(mixed $passable, Closure $next): mixed
    {
        if (! ($passable->shouldSendEmail ?? true) || empty($passable->emailData ?? [])) {
            return $next($passable);
        }

        foreach (self::RECEIVERS as $receiver) {
            try {
                Mail::mailer('local_mail')->send(
                    'mail_template.hr_promodiser_report',
                    $passable->emailData,
                    function ($message) use ($receiver) {
                        $message->to($receiver);
                        $message->subject('AthenaERP - Promodisers Monthly Report');
                    }
                );
            } catch (\Throwable $th) {
                // Log or rethrow if needed
            }
        }

        return $next($passable);
    }
}
