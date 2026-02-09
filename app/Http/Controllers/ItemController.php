<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemAttributeValue;
use App\Models\ItemVariantAttribute;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    public function index($itemCode)
    {
        $itemDetails = Item::find($itemCode);

        return view('update_item_form', compact('itemCode', 'itemDetails'));
    }

    public function saveItem(Request $request)
    {
        DB::beginTransaction();
        try {
            if ($request->item_code) {
                $itemDetails = Item::find($request->item_code);
                if ($itemDetails) {
                    $updatedData = [];
                    if ($request->item_name != $itemDetails->item_name) {
                        $updatedData['item_name'] = $request->item_name;
                    }

                    $originalDescription = preg_replace('/\s+/', ' ', $itemDetails->description);
                    $requestDescription = preg_replace('/\s+/', ' ', $request->description);
                    if (strip_tags($originalDescription) != strip_tags($requestDescription)) {
                        $updatedData['description'] = $requestDescription;
                    }

                    if ($request->is_stock_item != $itemDetails->is_stock_item) {
                        $updatedData['is_stock_item'] = $request->is_stock_item ?? 0;
                    }

                    if ($updatedData) {
                        $itemDetails->update($updatedData);
                    }
                }
            }

            DB::commit();

            return response()->json(['message' => "Item $request->item_code has been updated."], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['message' => 'An error occurred. Please contact your system administrator.'], 500);
        }
    }

    public function getAttributeValues($attributeName, Request $request)
    {
        $searchTerms = explode(' ', $request->search);

        $data = ItemAttributeValue::query()
            ->forAttribute($attributeName)
            ->searchByValue($request->search)
            ->orderBy('idx')
            ->paginate(15)
            ->onEachSide(1);

        return view('item_attributes_values_list', compact('data'));
    }

    public function saveItemAttribute(Request $request)
    {
        DB::beginTransaction();
        try {
            $exists = ItemAttributeValue::query()
                ->forAttribute($request->attribute)
                ->where('attribute_value', $request->attribute_value)
                ->exists();

            if ($exists) {
                return response()->json(['message' => "Attribute value $request->attribute_value already exists."], 400);
            }

            $maxIndex = ItemAttributeValue::forAttribute($request->attribute)->max('idx') ?? 0;

            ItemAttributeValue::create([
                'name' => 'athena' . uniqid(),
                'creation' => Carbon::now()->toDateTimeString(),
                'modified' => Carbon::now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => $maxIndex + 1,
                'attribute_value' => $request->attribute_value,
                'abbr' => $request->abbreviation,
                'parent' => $request->attribute,
                'parentfield' => 'item_attribute_values',
                'parenttype' => 'Item Attribute',
            ]);

            DB::commit();

            return response()->json(['message' => "Attribute value $request->attribute_value has been added."], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['message' => 'An error occurred. Please contact your system administrator.'], 500);
        }
    }

    public function deleteItemAttribute(Request $request)
    {
        DB::beginTransaction();
        try {
            ItemAttributeValue::where('name', $request->attribute_value_name)->delete();

            DB::commit();

            return response()->json(['message' => 'Attribute value has been deleted.'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['message' => 'An error occurred. Please contact your system administrator.'], 500);
        }
    }

    public function getItemAttributes($itemCode)
    {
        $itemAttributes = [];
        if ($itemCode) {
            $itemAttributes = ItemVariantAttribute::where('parent', $itemCode)
                ->select('name', 'attribute', 'attribute_value', 'idx')
                ->orderBy('idx')
                ->get();
        }

        return view('item_attributes_list', compact('itemAttributes', 'itemCode'));
    }

    public function updateItemVariant(Request $request)
    {
        DB::beginTransaction();
        try {
            $itemCode = $request->item_code;
            $oldVariantAttributes = ItemVariantAttribute::where('parent', $itemCode)
                ->pluck('attribute_value', 'name')
                ->toArray();

            $oldAttributeValue = $oldVariantAttributes[$request->name];
            $newAttributeValue = $request->attribute_value;

            if ($newAttributeValue == $oldAttributeValue) {
                return response()->json(['message' => 'The selected attribute value is the same as the current.'], 200);
            }

            $item = Item::find($itemCode);
            $templateItem = $item->variant_of;

            $itemVariants = Item::where('variant_of', $templateItem)
                ->with('variantAttributes')
                ->get()
                ->mapWithKeys(fn($i) => [$i->name => $i->variantAttributes->pluck('attribute_value')->all()]);

            $oldVariantAttributes = array_values($oldVariantAttributes);
            $newVariantAttributes = $oldVariantAttributes;

            $oldAttrIndex = array_search($oldAttributeValue, $oldVariantAttributes);
            unset($oldVariantAttributes[$oldAttrIndex]);

            array_push($newVariantAttributes, $newAttributeValue);

            $duplicateItem = $itemVariants->filter(function ($attributes) use ($newVariantAttributes) {
                return empty(array_diff($attributes, $newVariantAttributes)) && empty(array_diff($newVariantAttributes, $attributes));
            })->keys()->first();

            if ($duplicateItem) {
                return response()->json(['message' => "Unable to update item attributes. Item variant $duplicateItem already exists with the same attributes."], 200);
            }

            ItemVariantAttribute::where('name', $request->name)->update([
                'attribute_value' => $request->attribute_value,
                'modified' => Carbon::now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
            ]);

            DB::commit();

            return response()->json(['message' => 'Attribute value has been updated.'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['message' => 'An error occurred. Please contact your system administrator.'], 500);
        }
    }
}
