<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AthenaTransaction extends Model
{
    use HasFactory;

    /** Display value when user (requested_by / issued_by) is unknown or empty. */
    public const EMPTY_USER_PLACEHOLDER = '-';

    protected $connection = 'mysql';

    protected $primaryKey = 'name';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $table = 'tabAthena Transactions';

    public function stockEntry()
    {
        return $this->belongsTo(StockEntry::class, 'reference_parent', 'name');
    }

    /**
     * Scope: join Packing Slip, Packing Slip Item, Delivery Note for issued-qty lookups (item profile stock levels, stock reservations pending).
     * Use with whereIn('at.item_code', ...) and whereIn('at.source_warehouse', ...) or equivalent. Indexes on reference_type/status/item_code and source_warehouse support this.
     */
    public function scopeJoinPackingSlipDeliveryNote(Builder $query): Builder
    {
        return $query->from('tabAthena Transactions as at')
            ->join('tabPacking Slip as ps', 'ps.name', 'at.reference_parent')
            ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
            ->join('tabDelivery Note as dr', 'ps.delivery_note', 'dr.name')
            ->whereIn('at.reference_type', ['Packing Slip', 'Picking Slip'])
            ->where('dr.docstatus', 0)
            ->where('ps.docstatus', '<', 2)
            ->where('psi.status', 'Issued')
            ->where('at.status', 'Issued')
            ->whereRaw('psi.item_code = at.item_code');
    }
}
