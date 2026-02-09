<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemBrochureImage extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'tabItem Brochure Image';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';

    public function item()
    {
        return $this->belongsTo(Item::class, 'parent', 'name');
    }
}
