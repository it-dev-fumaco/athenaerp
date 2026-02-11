<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MESOperation extends Model
{
    use HasFactory;

    protected $table = 'operation';

    protected $connection = 'mysql_mes';

    protected $primaryKey = 'operation_id';

    public $timestamps = false;

    protected $keyType = 'string';
}
