<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockEntryDetail extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
    protected $table = 'tabStock Entry Detail';

    public function parent_doctype(){
        return $this->belongsTo(StockEntry::class, 'parent', 'name');
    }

    public function defaultImage(){
        return $this->hasOne(ItemImages::class, 'parent', 'item_code')->select('image_path', 'parent');
    }
}
