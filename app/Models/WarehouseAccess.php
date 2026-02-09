<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseAccess extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'tabWarehouse Access';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
}
