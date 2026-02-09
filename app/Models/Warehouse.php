<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';

    protected $table = 'tabWarehouse';

    public function bin()
    {
        return $this->hasMany(Bin::class, 'warehouse', 'name');
    }

    /**
     * Scope to get warehouses allowed for a user (by frappe_userid).
     *
     * @param Builder $query
     */
    public function scopeForUser($query, string $frappeUserid)
    {
        $parentWarehouses = WarehouseAccess::query()
            ->where('parent', $frappeUserid)
            ->pluck('warehouse');

        return $query->where('disabled', 0)
            ->whereIn('parent_warehouse', $parentWarehouses);
    }
}
