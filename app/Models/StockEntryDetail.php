<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockEntryDetail extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $primaryKey = 'name';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $table = 'tabStock Entry Detail';

    protected $fillable = [
        'qty', 'transfer_qty', 'basic_rate', 'valuation_rate', 'status', 'issued_qty',
        'modified', 'modified_by', 'validate_item_code', 'session_user', 'date_modified', 'remarks',
    ];

    public function parentDoctype()
    {
        return $this->belongsTo(StockEntry::class, 'parent', 'name');
    }

    /**
     * Issued child lines whose parent Stock Entry is still draft.
     * Child docstatus can stay 0 after submit, so pending-issued qty must use the parent.
     */
    public function scopeIssuedOnDraftStockEntry(Builder $query): Builder
    {
        return $query->join('tabStock Entry as ste', 'ste.name', '=', $this->getTable().'.parent')
            ->where('ste.docstatus', 0)
            ->where($this->getTable().'.status', 'Issued');
    }

    public function defaultImage()
    {
        return $this->hasOne(ItemImages::class, 'parent', 'item_code')->select('image_path', 'parent');
    }
}
