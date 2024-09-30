<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bin extends Model
{
    use HasFactory;

    protected $table = 'tabBin';

    public function warehouses(){
        return $this->hasMany(Warehouse::class, 'name', 'warehouse');
    }
}
