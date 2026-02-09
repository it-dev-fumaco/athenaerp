<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemVariantAttribute extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'tabItem Variant Attribute';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';

    protected $fillable = [
        'attribute',
        'attribute_value',
        'parent',
        'parentfield',
        'parenttype',
        'idx',
        'modified',
        'modified_by',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'parent', 'name');
    }
}
