<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemAttributeValue extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'tabItem Attribute Value';

    protected $primaryKey = 'name';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'creation',
        'modified',
        'modified_by',
        'owner',
        'docstatus',
        'attribute_value',
        'abbr',
        'parent',
        'parentfield',
        'parenttype',
        'idx',
    ];

    /**
     * Scope for attribute parent.
     */
    public function scopeForAttribute($query, string $parent): Builder
    {
        return $query->where('parent', $parent);
    }

    /**
     * Scope for search by attribute value (supports multiple terms).
     */
    public function scopeSearchByValue($query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }
        $searchTerms = explode(' ', $search);

        return $query->where(function ($subQuery) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $subQuery->where('attribute_value', 'LIKE', "%{$term}%");
            }
        });
    }

    /**
     * Get attribute value by parent and value.
     */
    public static function findByAttributeAndValue(string $parent, string $attributeValue): ?self
    {
        return static::query()
            ->forAttribute($parent)
            ->where('attribute_value', $attributeValue)
            ->first();
    }
}
