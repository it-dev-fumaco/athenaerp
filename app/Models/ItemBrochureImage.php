<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemBrochureImage extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'tabItem Brochure Image';

    protected $primaryKey = 'name';

    public $timestamps = false;

    protected $keyType = 'string';

    /**
     * Allow mass assignment for the columns we update in brochure uploads.
     *
     * NOTE: This table mirrors ERPNext-style columns, so we explicitly whitelist
     * instead of unguarding everything.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'creation',
        'modified',
        'modified_by',
        'owner',
        'parent',
        'idx',
        'image_filename',
        'image_path',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'parent', 'name');
    }
}
