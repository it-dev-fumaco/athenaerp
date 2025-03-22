<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockReconciliationItem extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
    protected $table = 'tabStock Reconciliation Item';

    public function parent_doctype(){
        return $this->belongsTo(StockReconciliation::class, 'parent', 'name');
    }
}
