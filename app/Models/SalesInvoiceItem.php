<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoiceItem extends Model
{
    use HasFactory;
    protected $table = 'tabSales Invoice Item';
    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
    public function sales_order(){
        return $this->hasOne(SalesInvoice::class, 'parent', 'name');
    }
}
