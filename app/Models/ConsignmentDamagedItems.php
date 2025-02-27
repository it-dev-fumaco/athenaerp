<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsignmentDamagedItems extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
    protected $table = 'tabConsignment Damaged Item';

    public function item(){
        return $this->hasOne(Item::class, 'name', 'item_code');
    }
}
