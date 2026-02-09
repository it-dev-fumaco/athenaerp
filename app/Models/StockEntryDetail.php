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

    protected $fillable = [
        'qty', 'transfer_qty', 'basic_rate', 'valuation_rate', 'status', 'issued_qty',
        'modified', 'modified_by', 'validate_item_code', 'session_user', 'date_modified', 'remarks',
    ];

    public function parentDoctype()
    {
        return $this->belongsTo(StockEntry::class, 'parent', 'name');
    }

    public function defaultImage(){
        return $this->hasOne(ItemImages::class, 'parent', 'item_code')->select('image_path', 'parent');
    }
}
