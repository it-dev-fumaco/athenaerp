<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
    protected $table = 'tabItem';
    
    public function bin(){
        return $this->hasMany(Bin::class, 'item_code', 'item_code');
    }

    public function images(){
        return $this->hasMany(ItemImages::class, 'parent', 'name');
    }

    public function defaultImage(){
        return $this->hasOne(ItemImages::class, 'parent', 'name');
    }
}
