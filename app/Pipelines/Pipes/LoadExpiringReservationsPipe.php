<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use App\Models\StockReservation;
use Carbon\Carbon;
use Closure;

class LoadExpiringReservationsPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $inOneDay = now()->addDay()->toDateString();
        $inTwoDays = now()->addDays(2)->toDateString();

        $passable->reservations = StockReservation::query()
            ->where('status', 'Active')
            ->where('type', StockReservation::TYPE_IN_HOUSE)
            ->where(function ($query) use ($inOneDay, $inTwoDays) {
                $query->whereDate('valid_until', $inOneDay)
                    ->orWhereDate('valid_until', $inTwoDays);
            })
            ->select([
                'name',
                'creation',
                'created_by',
                'owner',
                'sales_person',
                'item_code',
                'description',
                'valid_until',
                'reserve_qty',
                'warehouse',
            ])
            ->get()
            ->map(function ($reservation) use ($inOneDay, $inTwoDays) {
                $validUntilDate = Carbon::parse($reservation->valid_until)->toDateString();
                $reservation->days_until_expiry = $validUntilDate === $inOneDay ? 1 : 2;

                return $reservation;
            });

        return $next($passable);
    }
}
