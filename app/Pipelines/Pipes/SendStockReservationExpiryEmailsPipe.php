<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendStockReservationExpiryEmailsPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $emailJobs = collect($passable->emailJobs ?? []);

        foreach ($emailJobs as $job) {
            $reservation = $job->reservation;
            $daysUntilExpiry = $job->days_until_expiry;
            $recipients = $job->recipients;

            $emailData = [
                'dateReserve' => $reservation->creation,
                'salesPerson' => $reservation->sales_person,
                'reservedBy' => $reservation->created_by,
                'itemCode' => $reservation->item_code,
                'description' => $reservation->description,
                'validUntil' => $reservation->valid_until,
                'daysUntilExpiry' => $daysUntilExpiry,
                'sentAt' => now(),
            ];

            $dayLabel = $daysUntilExpiry === 1 ? '1 day' : "{$daysUntilExpiry} days";

            try {
                Mail::mailer('local_mail')->send(
                    'mail_template.stock_reservation_expiry',
                    $emailData,
                    function ($message) use ($recipients, $dayLabel) {
                        $message->to($recipients);
                        $message->subject("AthenaERP - Stock Reservation Alert ({$dayLabel} remaining)");
                    }
                );
            } catch (\Throwable $th) {
                Log::warning('Failed to send stock reservation expiry email', [
                    'reservation' => $reservation->name ?? null,
                    'recipients' => $recipients,
                    'error' => $th->getMessage(),
                ]);
            }
        }

        return $next($passable);
    }
}
