<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Item resource - Frappe-compatible format (data wrapper).
 * @see https://docs.frappe.io/framework/user/en/api/rest
 */
class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'item_name' => $this->item_name ?? $this->name,
            'item_group' => $this->item_group,
            'stock_uom' => $this->stock_uom,
            'custom_item_cost' => $this->custom_item_cost,
            'item_classification' => $this->item_classification,
            'disabled' => $this->disabled,
            'has_variants' => $this->has_variants,
            'variant_of' => $this->variant_of,
            'brand' => $this->brand,
        ];
    }
}
