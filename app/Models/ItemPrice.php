<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemPrice extends Model
{
    use HasFactory;

    /**
     * ERPNext selling price list for the director-edited Fix Standard Selling Price.
     */
    public const ATHENA_DISPLAY_PRICE_LIST = 'Standard Price List - Athena Display';

    protected $connection = 'mysql';

    protected $primaryKey = 'name';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $table = 'tabItem Price';
}
