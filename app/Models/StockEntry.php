<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockEntry extends Model
{
    use HasFactory;

    protected $table = 'tabStock Entry';
    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';

    protected $fillable = [
        'item_status', 'docstatus', 'modified', 'modified_by', 'fg_completed_qty',
        'posting_date', 'posting_time', 'total_amount', 'total_outgoing_value', 'total_incoming_value',
    ];

    public function ledger(){
        return $this->hasMany(StockLedgerEntry::class, 'voucher_no', 'name');
    }

    public function items(){
        return $this->hasMany(StockEntryDetail::class, 'parent', 'name');
    }

    public function mreq(){
        return $this->belongsTo(MaterialRequest::class, 'material_request', 'name');
    }
}
