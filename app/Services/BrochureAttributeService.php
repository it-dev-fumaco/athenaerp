<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemAttribute;
use App\Models\ItemVariantAttribute;
use Illuminate\Support\Facades\Auth;

class BrochureAttributeService
{
    /**
     * Update brochure-related attributes: ItemAttribute (attr_name), ItemVariantAttribute
     * (brochure_idx, hide_in_brochure), and Item (item_brochure_remarks).
     *
     * @param  array<string, string>  $requestAttributes  New attribute display names keyed by attribute name
     * @param  array<string, string>  $currentAttributes  Current attribute names keyed by attribute name
     * @param  array<int, string>  $hiddenAttributes  Attribute names to hide in brochure
     */
    public function updateBrochureAttributes(
        string $itemCode,
        array $requestAttributes,
        array $currentAttributes,
        array $hiddenAttributes,
        ?string $remarks
    ): void {
        $transactionDate = now()->toDateTimeString();
        $modifiedBy = Auth::user()->wh_user;

        foreach ($requestAttributes as $attributeName => $newAttributeName) {
            $currentName = $currentAttributes[$attributeName] ?? null;
            if ($currentName !== null && $currentName != $newAttributeName) {
                ItemAttribute::where('name', $attributeName)->update([
                    'attr_name' => $newAttributeName,
                    'modified' => $transactionDate,
                    'modified_by' => $modifiedBy,
                ]);
            }
        }

        $idx = 0;
        foreach ($currentAttributes as $name => $attribute) {
            ItemVariantAttribute::where('parent', $itemCode)
                ->where('attribute', $attribute)
                ->update([
                    'brochure_idx' => $idx += 1,
                    'hide_in_brochure' => in_array($attribute, $hiddenAttributes, true) ? 1 : 0,
                    'modified_by' => $modifiedBy,
                    'modified' => $transactionDate,
                ]);
        }

        Item::where('name', $itemCode)->update([
            'item_brochure_remarks' => $remarks,
            'modified' => $transactionDate,
            'modified_by' => $modifiedBy,
        ]);
    }

    /**
     * Update Item brochure fields (item_brochure_description, item_brochure_name) for items
     * that have new description or name in the request. Used when adding items to brochure list.
     *
     * @param  array<string>  $itemCodes
     * @param  array<string, string|null>  $itemBrochureDescription  Item code => description
     * @param  array<string, string|null>  $itemBrochureName  Item code => brochure name
     */
    public function syncItemBrochureFields(
        array $itemCodes,
        array $itemBrochureDescription,
        array $itemBrochureName
    ): void {
        $transactionDate = now()->toDateTimeString();
        $modifiedBy = Auth::user()->wh_user;

        foreach ($itemCodes as $itemCode) {
            $hasDescription = isset($itemBrochureDescription[$itemCode]);
            $hasName = isset($itemBrochureName[$itemCode]);
            if (! $hasDescription && ! $hasName) {
                continue;
            }

            $update = [
                'modified' => $transactionDate,
                'modified_by' => $modifiedBy,
            ];
            if ($hasDescription) {
                $update['item_brochure_description'] = $itemBrochureDescription[$itemCode];
            }
            if ($hasName) {
                $update['item_brochure_name'] = $itemBrochureName[$itemCode];
            }

            Item::where('name', $itemCode)->update($update);
        }
    }

    /**
     * Update single item brochure name and description (e.g. before generating standard PDF).
     */
    public function updateItemBrochureFields(
        string $itemCode,
        string $itemName,
        ?string $description
    ): void {
        Item::where('name', $itemCode)->update([
            'item_brochure_name' => $itemName,
            'item_brochure_description' => $description,
            'modified' => now()->toDateTimeString(),
            'modified_by' => Auth::user()->wh_user,
        ]);
    }
}
