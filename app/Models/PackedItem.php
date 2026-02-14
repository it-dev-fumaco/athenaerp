<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackedItem extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $primaryKey = 'name';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $table = 'tabPacked Item';
}
