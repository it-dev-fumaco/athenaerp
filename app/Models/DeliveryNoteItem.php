<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryNoteItem extends Model
{
    use HasFactory;

    protected $table = 'tabDelivery Note Item';

    protected $connection = 'mysql';

    protected $primaryKey = 'name';

    public $timestamps = false;

    protected $keyType = 'string';

    public function deliveryNote()
    {
        return $this->belongsTo(DeliveryNote::class, 'parent', 'name');
    }

    public function packedItems()
    {
        return $this->belongsTo(PackedItem::class, 'parent_detail_docname', 'name');
    }
}
