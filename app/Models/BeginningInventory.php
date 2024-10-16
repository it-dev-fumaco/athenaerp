<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeginningInventory extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';

    protected $table = 'tabConsignment Beginning Inventory';

    public function items(){
        return $this->hasMany(BeginningInventoryItem::class, 'parent', 'name');
    }
}