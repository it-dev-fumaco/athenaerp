<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsignmentInventoryAuditReport extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'tabConsignment Inventory Audit Report';

    protected $primaryKey = 'name';

    public $timestamps = false;

    protected $keyType = 'string';

    public function items()
    {
        return $this->hasMany(ConsignmentInventoryAuditReportItem::class, 'parent', 'name');
    }
}
