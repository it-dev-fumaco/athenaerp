<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockReservation extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tabStock Reservation';
    protected $primaryKey = 'name';
    public $timestamps = false;
}
