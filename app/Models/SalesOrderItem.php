<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    use HasFactory;
    protected $table = 'tabSales Order Item';
    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
    public function sales_order(){
        return $this->hasOne(SalesOrder::class, 'parent', 'name');
    }
}
