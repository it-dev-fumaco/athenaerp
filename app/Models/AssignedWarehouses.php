<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignedWarehouses extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
    protected $table = 'tabAssigned Consignment Warehouse';

    public function assigned_warehouse(){
        return $this->belongsTo(Warehouse::class, 'name', 'warehouse');
    }

    public function user(){
        return $this->belongsTo(User::class, 'parent', 'name');
    }
}
