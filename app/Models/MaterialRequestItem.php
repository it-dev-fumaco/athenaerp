<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialRequestItem extends Model
{
    use HasFactory;
    protected $table = 'tabMaterial Request Item';
    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';

    public function parent_doctype(){
        return $this->belongsTo(MaterialRequest::class, 'parent', 'name');
    }

    public function defaultImage(){
        return $this->hasOne(ItemImages::class, 'parent', 'item_code')->select('image_path', 'parent');
    }
}
