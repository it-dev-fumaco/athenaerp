<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsignmentStockEntryDetail extends Model
{
    use HasFactory;
    protected $table = 'tabConsignment Stock Entry Detail';
    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';

    public function item_details(){
        return $this->hasOne(Item::class, 'item_code', 'item_code');
    }
}
