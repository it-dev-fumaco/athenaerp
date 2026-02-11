<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MESProductionOrder extends Model
{
    use HasFactory;

    protected $table = 'production_order';

    protected $connection = 'mysql_mes';

    protected $primaryKey = 'production_order';

    public $timestamps = false;

    protected $keyType = 'string';
}
