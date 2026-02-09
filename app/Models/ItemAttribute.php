<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemAttribute extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'tabItem Attribute';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
}
