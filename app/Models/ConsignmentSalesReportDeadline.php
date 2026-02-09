<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsignmentSalesReportDeadline extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'tabConsignment Sales Report Deadline';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
}
