<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockReservation extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'tabStock Reservation';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';

    protected $fillable = [
        'status', 'reserve_qty', 'consumed_qty', 'modified', 'modified_by',
    ];

    /**
     * Scope for active reservations (Active or Partially Issued).
     */
    public function scopeActive($query): Builder
    {
        return $query->whereIn('status', ['Active', 'Partially Issued']);
    }

    /**
     * Scope for In-house type.
     */
    public function scopeInHouse($query): Builder
    {
        return $query->where('type', 'In-house');
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
            ->where('status', 'Active')
            ->sum('reserve_qty');
    }
}
