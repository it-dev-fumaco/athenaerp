<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackingSlipItem extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $primaryKey = 'name';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $table = 'tabPacking Slip Item';

    public function parentDoctype()
    {
        return $this->belongsTo(PackingSlip::class, 'parent', 'name');
    }

    public function packed()
    {
        return $this->hasOne(PackedItem::class, 'name', 'pi_detail');
    }

    public function defaultImage()
    {
        return $this->hasOne(ItemImages::class, 'parent', 'item_code')->select('image_path', 'parent');
    }
}
