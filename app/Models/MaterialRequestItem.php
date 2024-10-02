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

    public function items(){
        return $this->belongsTo(MaterialRequest::class, 'parent', 'name');
    }
}
