<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockReconciliation extends Model
{
    use HasFactory;
    protected $table = 'tabStock Reconciliation';
    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
    
    public function items(){
        return $this->hasMany(StockReconciliationItem::class, 'parent', 'name');
    }

}
