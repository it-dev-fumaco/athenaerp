<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $table = 'tabItem';
    
    public function bin(){
        return $this->hasMany(Bin::class, 'item_code', 'item_code');
    }
}
