<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsignmentStockEntry extends Model
{
    use HasFactory;
    protected $table = 'tabConsignment Stock Entry';
    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';

    public function items(){
        return $this->hasMany(ConsignmentStockEntryDetail::class, 'parent', 'name');
    }

    public function stock_entry(){
        return $this->hasOne(StockEntry::class, 'name', 'references');
    }
}
