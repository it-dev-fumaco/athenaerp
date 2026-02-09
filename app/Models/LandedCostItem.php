<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandedCostItem extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
    protected $table = 'tabLanded Cost Item';

    public function landedCostVoucher()
    {
        return $this->belongsTo(LandedCostVoucher::class, 'parent', 'name');
    }
}
