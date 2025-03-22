<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    use HasFactory;
    protected $table = 'tabSales Invoice';
    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
    public function items(){
        return $this->hasMany(SalesInvoiceItem::class, 'parent', 'name');
    }
}
