<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeginningInventoryItem extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $primaryKey = 'name';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $table = 'tabConsignment Beginning Inventory Item';

    public function defaultImage()
    {
        return $this->hasOne(ItemImages::class, 'parent', 'item_code');
    }
}
