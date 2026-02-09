<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AthenaTransaction extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
    protected $table = 'tabAthena Transactions';

    public function stockEntry()
    {
        return $this->belongsTo(StockEntry::class, 'reference_parent', 'name');
    }
}
