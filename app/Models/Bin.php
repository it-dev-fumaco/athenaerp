<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bin extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $primaryKey = 'name';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $table = 'tabBin';

    protected $fillable = [
        'actual_qty', 'stock_value', 'valuation_rate', 'modified', 'modified_by',
        'location', 'consigned_qty', 'reserved_qty', 'projected_qty', 'ordered_qty',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse', 'name');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_code', 'name');
    }

    public function defaultImage()
    {
        return $this->hasOne(ItemImages::class, 'parent', 'item_code')->select('image_path', 'parent');
    }

    /**
     * Scope for item and warehouse.
     */
    public function scopeForItemAndWarehouse($query, string $itemCode, string $warehouse)
    {
        return $query->where('item_code', $itemCode)->where('warehouse', $warehouse);
    }

    /**
     * Update location for an item in multiple warehouses.
     *
     * @param  array<string, string>  $warehouseToLocation  [warehouse => location]
     */
    public static function updateLocationsForItem(string $itemCode, array $warehouseToLocation): void
    {
        foreach ($warehouseToLocation as $warehouse => $location) {
            static::query()
                ->where('warehouse', $warehouse)
                ->where('item_code', $itemCode)
                ->update(['location' => strtoupper($location)]);
        }
    }

    /**
     * Get available quantity (actual - reserved - website_reserved).
     */
    public function getAvailableQty(): float
    {
        $reservedQty = StockReservation::query()
            ->where('item_code', $this->item_code)
            ->where('warehouse', $this->warehouse)
            ->where('type', 'In-house')
            ->where('status', 'Active')
            ->sum('reserve_qty');

        return max(0, $this->actual_qty - $reservedQty - ($this->website_reserved_qty ?? 0));
    }
}
