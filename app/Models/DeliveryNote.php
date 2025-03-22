<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    use HasFactory;
    protected $table = 'tabDelivery Note';
    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
    public function items(){
        return $this->hasMany(DeliveryNoteItem::class, 'parent', 'name');
    }

    public function picking_slip(){
        return $this->hasMany(PackingSlip::class, 'delivery_note', 'name');
    }

    public function packed_item(){
        return $this->hasMany(PackedItem::class, 'parent', 'name');
    }
}
