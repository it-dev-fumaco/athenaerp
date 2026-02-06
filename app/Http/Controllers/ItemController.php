<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Auth;
use Carbon\Carbon;

class ItemController extends Controller
{

    public function index($item_code) {
        $item_details = DB::table('tabItem')->where('name', $item_code)->first();

        return view('update_item_form', compact('item_code', 'item_details'));
    }

    public function saveItem(Request $request) {
        DB::beginTransaction();
        try {
            if ($request->item_code) {
                $item_details = DB::table('tabItem')->where('name', $request->item_code)->first();
                if ($item_details) {
                    $updatedData = [];
                    if ($request->item_name != $item_details->item_name) {
                        $updatedData['item_name'] = $request->item_name;
                    }

                    $original_description = preg_replace('/\s+/', ' ', $item_details->description);
                    $request_description = preg_replace('/\s+/', ' ', $request->description);
                    if (strip_tags($original_description) != strip_tags($request_description)) {
                        $updatedData['description'] = $request_description;
                    }

                    if ($request->is_stock_item != $item_details->is_stock_item) {
                        $updatedData['is_stock_item'] = $request->is_stock_item ?? 0;
                    }

                    if ($updatedData) {
                        DB::table('tabItem')->where('name', $request->item_code)->update($updatedData);
                    }

                }
            }

            DB::commit();

            return response()->json(['message' => "Item $request->item_code has been updated."], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['message' => "An error occured. Please contact your system administrator."], 500);
        }
    }

    public function getAttributeValues($attributeName, Request $request) {
        $search_str = explode(' ', $request->search);

        $data = DB::table('tabItem Attribute Value')
            ->where('parent', $attributeName)
            ->when($request->search, function ($query) use ($search_str, $request) {
                return $query->where(function($q) use ($search_str, $request) {
                    foreach ($search_str as $str) {
                        $q->where('attribute_value', 'LIKE', "%$str%");
                    }
                });
            })
            ->orderBy('idx')->paginate(15)->onEachSide(1);;

        return view('item_attributes_values_list', compact('data'));
    }

    public function saveItemAttribute(Request $request) {
        DB::beginTransaction();
        try {
            $exists = DB::table('tabItem Attribute Value')->where('attribute_value', $request->attribute_value)
                ->where('parent', $request->attribute)->exists();

            if ($exists) {
                return response()->json(['message' => "Attribute value $request->attribute_value already exists."], 400);
            }

            $idx = DB::table('tabItem Attribute Value')->where('parent', $request->attribute)->max('idx');

            DB::table('tabItem Attribute Value')->insert([
                'name' => 'athena' . uniqid(),
                'creation' => Carbon::now()->toDateTimeString(),
                'modified' => Carbon::now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => $idx++,
                'attribute_value' => $request->attribute_value,
                'abbr' => $request->abbreviation,
                'parent' => $request->attribute,
                'parentfield' => 'item_attribute_values',
                'parenttype' => 'Item Attribute'
            ]);

            DB::commit();

            return response()->json(['message' => "Attribute value $request->attribute_value has been added."], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['message' => "An error occured. Please contact your system administrator."], 500);
        }
    }

    public function deleteItemAttribute(Request $request) {
        DB::beginTransaction();
        try {
            DB::table('tabItem Attribute Value')->where('name', $request->attribute_value_name)->delete();

            DB::commit();
            
            return response()->json(['message' => "Attribute value has been deleted."], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['message' => "An error occured. Please contact your system administrator."], 500);
        }
    }

    public function getItemAttributes($item_code) {
        $item_attributes = [];
        if ($item_code) {
            $item_attributes = DB::table('tabItem Variant Attribute')->where('parent', $item_code)
                ->select('name', 'attribute', 'attribute_value', 'idx')->orderBy('idx')->get();
        }

        return view('item_attributes_list', compact('item_attributes', 'item_code'));
    }

    public function updateItemVariant(Request $request) {
        DB::beginTransaction();
        try {
            $itemCode = $request->item_code;
            $oldVariantAttributes = DB::table('tabItem Variant Attribute')
                ->where('parent', $itemCode)->pluck('attribute_value', 'name')->toArray();

            $oldAttributeValue = $oldVariantAttributes[$request->name];
            $newAttributeValue = $request->attribute_value;

            if ($newAttributeValue == $oldAttributeValue) {
                return response()->json(['message' => "The selected attribute value is the same as the current."], 200);
            }

            // get item code parent template
            $templateItem = DB::table('tabItem')->where('name', $itemCode)->first()->variant_of;
            // get all variants
            $itemVariants = DB::table('tabItem as i')->join('tabItem Variant Attribute as iva', 'i.name', 'iva.parent')
                ->where('i.variant_of', $templateItem)
                ->select('i.name as item_code', 'attribute_value')->get()->groupBy('item_code');

            $itemVariants = collect($itemVariants)->mapWithKeys(function ($items, $key) {
                return [$key => collect($items)->pluck('attribute_value')->all()];
            });

            $oldVariantAttributes = array_values($oldVariantAttributes);
            $newVariantAttributes = $oldVariantAttributes;

            // remove old attribute value
            $oldAttrIndex = array_search($oldAttributeValue, $oldVariantAttributes);
            unset($oldVariantAttributes[$oldAttrIndex]);

            // add new attribute value
            array_push($newVariantAttributes, $newAttributeValue);

            // check for duplicate items with the same attribute
            $matchingKey = collect($itemVariants)->first(function ($attributes, $key) use ($newVariantAttributes) {
                return empty(array_diff($attributes, $newVariantAttributes)) && empty(array_diff($newVariantAttributes, $attributes));
            });
            
            if ($matchingKey) {
                $duplicateItem = array_search($matchingKey, $itemVariants->toArray());

                return response()->json(['message' => "Unable to update item attributes. Item variant $duplicateItem already exists with the same attributes."], 200);
            } 

            DB::table('tabItem Variant Attribute')->where('name', $request->name)->update([
                'attribute_value' => $request->attribute_value,
                'modified' => Carbon::now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
            ]);

            DB::commit();

            return response()->json(['message' => "Attribute value has been updated."], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['message' => "An error occured. Please contact your system administrator."], 500);
        }
    }
}
