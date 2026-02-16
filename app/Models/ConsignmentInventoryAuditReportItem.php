<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsignmentInventoryAuditReportItem extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'tabConsignment Inventory Audit Report Item';

    protected $primaryKey = 'name';

    public $timestamps = false;

    protected $keyType = 'string';

    public function parentReport()
    {
        return $this->belongsTo(ConsignmentInventoryAuditReport::class, 'parent', 'name');
    }
}
