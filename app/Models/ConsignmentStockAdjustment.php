<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsignmentStockAdjustment extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
    protected $table = 'tabConsignment Stock Adjustment';

    public function items(){
        return $this->hasMany(ConsignmentStockAdjustmentItem::class, 'parent', 'name');
    }
}
