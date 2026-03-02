<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AthenaTransaction extends Model
{
    use HasFactory;

    /** Display value when user (requested_by / issued_by) is unknown or empty. */
    public const EMPTY_USER_PLACEHOLDER = '-';

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
