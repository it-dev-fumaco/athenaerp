<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsignmentMonthlySalesReport extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'tabConsignment Monthly Sales Report';

    protected $primaryKey = 'name';

    public $timestamps = false;

    protected $keyType = 'string';
}
