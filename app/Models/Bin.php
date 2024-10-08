<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bin extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
    protected $table = 'tabBin';

    public function warehouses(){
        return $this->belongsTo(Warehouse::class, 'name', 'warehouse');
    }

    public function item(){
        return $this->belongsTo(Item::class, 'item_code', 'name');
    }

    public function defaultImage(){
        return $this->hasOne(ItemImages::class, 'parent', 'item_code')->select('image_path', 'parent');
    }
}
