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
