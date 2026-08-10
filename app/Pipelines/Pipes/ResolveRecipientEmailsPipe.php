<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use App\Models\User;
use Closure;
use Illuminate\Support\Str;

class ResolveRecipientEmailsPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $reservations = collect($passable->reservations ?? []);

        if ($reservations->isEmpty()) {
            $passable->emailJobs = collect();

            return $next($passable);
        }

        $salesPersonNames = $reservations
            ->pluck('sales_person')
            ->filter()
            ->unique()
            ->values();

        $salesPersonEmails = User::query()
            ->whereIn('full_name', $salesPersonNames)
            ->where('enabled', 1)
            ->get(['full_name', 'wh_user'])
            ->keyBy('full_name')
            ->map(fn (User $user) => $this->toEmail($user->wh_user));

        $passable->emailJobs = $reservations->map(function ($reservation) use ($salesPersonEmails) {
            $recipients = collect([
                $this->toEmail($reservation->owner),
                $salesPersonEmails->get($reservation->sales_person),
            ])
                ->filter(fn ($email) => filled($email) && Str::contains($email, '@'))
                ->unique()
                ->values()
                ->all();

            return (object) [
                'reservation' => $reservation,
                'recipients' => $recipients,
                'days_until_expiry' => $reservation->days_until_expiry,
            ];
        })->filter(fn ($job) => ! empty($job->recipients))->values();

        return $next($passable);
    }

    protected function toEmail(?string $whUser): ?string
    {
        if (! filled($whUser)) {
            return null;
        }

        return str_replace('.local', '.com', $whUser);
    }
}
