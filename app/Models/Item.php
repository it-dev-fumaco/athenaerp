<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Item extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
    protected $table = 'tabItem';

    protected $fillable = [
        'custom_item_cost', 'description', 'item_name', 'item_group', 'stock_uom',
        'item_classification', 'disabled', 'has_variants', 'variant_of', 'brand',
    ];

    public function bin()
    {
        return $this->hasMany(Bin::class, 'item_code', 'name');
    }

    public function images()
    {
        return $this->hasMany(ItemImages::class, 'parent', 'name');
    }

    public function defaultImage()
    {
        return $this->hasOne(ItemImages::class, 'parent', 'name')->select('image_path', 'parent');
    }

    public function itemDefault()
    {
        return $this->hasMany(ItemDefault::class, 'parent', 'name');
    }

    public function itemReorder()
    {
        return $this->hasOne(ItemReorder::class, 'parent', 'name');
    }

    public function variantAttributes()
    {
        return $this->hasMany(ItemVariantAttribute::class, 'parent', 'name');
    }

    public function parentItem()
    {
        return $this->belongsTo(Item::class, 'variant_of', 'name');
    }

    public function variants()
    {
        return $this->hasMany(Item::class, 'variant_of', 'name');
    }

    /**
     * Scope for enabled items.
     */
    public function scopeEnabled($query): Builder
    {
        return $query->where('disabled', 0);
    }

    /**
     * Scope for stock items.
     */
    public function scopeStockItem($query): Builder
    {
        return $query->where('is_stock_item', 1);
    }

    /**
     * Scope for non-variant items (leaf variants).
     */
    public function scopeLeafVariants($query): Builder
    {
        return $query->where('has_variants', 0);
    }

    /**
     * Scope for items in item group or any level.
     */
    public function scopeInItemGroup($query, string $group): Builder
    {
        return $query->where(function ($q) use ($group) {
            $q->where('item_group', $group)
                ->orWhere('item_group_level_1', $group)
                ->orWhere('item_group_level_2', $group)
                ->orWhere('item_group_level_3', $group)
                ->orWhere('item_group_level_4', $group)
                ->orWhere('item_group_level_5', $group);
        });
    }

    /**
     * Scope for variant siblings (same parent).
     */
    public function scopeVariantSiblings($query, string $variantOf, ?string $excludeName = null): Builder
    {
        $q = $query->where('variant_of', $variantOf);
        if ($excludeName) {
            $q->where('name', '!=', $excludeName);
        }
        return $q;
    }

    /**
     * Scope for inventory search (description terms, name, item_group, classification, UOM, supplier part no).
     */
    public function scopeSearch($query, ?string $searchString): Builder
    {
        if (!$searchString) {
            return $query;
        }

        $searchTerms = explode(' ', $searchString);

        return $query->where(function ($subQuery) use ($searchTerms, $searchString) {
            foreach ($searchTerms as $term) {
                $subQuery->where('tabItem.description', 'LIKE', "%{$term}%");
            }
            $subQuery->orWhere('tabItem.name', 'LIKE', "%{$searchString}%")
                ->orWhere('tabItem.item_group', 'LIKE', "%{$searchString}%")
                ->orWhere('tabItem.item_classification', 'LIKE', "%{$searchString}%")
                ->orWhere('tabItem.stock_uom', 'LIKE', "%{$searchString}%")
                ->orWhere(DB::raw('(SELECT GROUP_CONCAT(DISTINCT supplier_part_no SEPARATOR "; ") FROM `tabItem Supplier` WHERE parent = `tabItem`.name)'), 'LIKE', "%{$searchString}%");
        });
    }
}
