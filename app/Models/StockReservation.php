<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockReservation extends Model
{
    use HasFactory;

    /** @var string */
    protected $connection = 'mysql';

    /** @var string */
    protected $table = 'tabStock Reservation';

    /** @var string */
    protected $primaryKey = 'name';

    public $timestamps = false;

    /** @var string */
    protected $keyType = 'string';

    /** @var array<int, string> */
    protected $fillable = [
        'status', 'reserve_qty', 'consumed_qty', 'modified', 'modified_by',
    ];

    /** In-house reservation type. PHP 8.3: can use `public const string TYPE_IN_HOUSE`. */
    public const TYPE_IN_HOUSE = 'In-house';

    /** Active statuses for reservations. PHP 8.3: can use `public const array ACTIVE_STATUSES`. */
    public const ACTIVE_STATUSES = ['Active', 'Partially Issued'];

    /**
     * Scope for active reservations (Active or Partially Issued).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    /**
     * Scope for In-house type.
     */
    public function scopeInHouse(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_IN_HOUSE);
    }

    /**
     * Scope for item and warehouse.
     */
    public function scopeForItemAndWarehouse($query, string $itemCode, string $warehouse): Builder
    {
        return $query->where('item_code', $itemCode)->where('warehouse', $warehouse);
    }

    /**
     * Get total reserved qty for item/warehouse (In-house, Active).
     */
    public static function getActiveReservedQty(string $itemCode, string $warehouse): float
    {
        return (float) static::query()
            ->forItemAndWarehouse($itemCode, $warehouse)
            ->inHouse()
            ->where('status', self::ACTIVE_STATUSES[0])
            ->sum('reserve_qty');
    }
}
