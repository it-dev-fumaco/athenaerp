<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Validator;
use Auth;
use DB;
use Session;
use App\LdapClasses\adLDAP;
use Illuminate\Support\Str;

class ItemAttributeController extends Controller
{
    public function update_login(){
        return view('item_attributes_updating.login');
    }

    public function login(Request $request){
        try {
            // validate the info, create rules for the inputs
            $rules = array(
                'email' => 'required'
            );

            $validator = Validator::make($request->all(), $rules);

            // if the validator fails, redirect back to the form
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)
                    ->withInput($request->except('password'));
            }else{
                $adldap = new adLDAP();
                $authUser = $adldap->user()->authenticate($request->email, $request->password);

                if($authUser == true){
                    $user = DB::table('tabWarehouse Users')->where('wh_user', $request->email . '@fumaco.local')->first();
                    
                    if ($user) {
                        // attempt to do the login
                        if(Auth::loginUsingId($user->frappe_userid)){
                            return redirect('/search');
                        } 
                    } else {        
                        // validation not successful, send back to form 
                        return redirect()->back()->withErrors('<span class="blink_text">Incorrect Username or Password</span>');
                    }
                }
                
                return redirect()->back()->withInput($request->except('password'))
                    ->withErrors('<span class="blink_text">Incorrect Username or Password</span>');
            }
        } catch (adLDAPException $e) {
            return $e;
        }
    }

    public function item_attribute_search(Request $request){
        $itemAttrib = DB::table('tabItem Variant Attribute as tva')
            ->join('tabItem as ti', 'tva.parent', 'ti.name')
            ->where('tva.parent', $request->item_code)
            ->where('ti.is_stock_item', 1)->where('ti.has_variants', 0)->where('ti.disabled', 0)
            ->orderby('tva.idx', 'asc')
            ->get();

        $itemDesc = DB::table('tabItem')->select('description', 'variant_of')
            ->where('name', $request->item_code)
            ->first();

        $parentItemCode = ($itemDesc) ? $itemDesc->variant_of : null ;

        $parentDesc = DB::table('tabItem')->select('description')
            ->where('name', $parentItemCode)
            ->first();
            
        return view('item_attributes_updating.item_attribute_search', compact('itemAttrib', 'itemDesc', 'parentDesc'));
    }

    public function update_attrib_form(Request $request){     
        $item_code = $request->u_item_code;

        if(!$item_code){
            return redirect('/search');
        }

        $itemAttrib = DB::table('tabItem Variant Attribute as tva')
            ->join('tabItem as ti', 'tva.parent', 'ti.name')
            ->where('tva.parent', $item_code)
            ->where('ti.is_stock_item', 1)->where('ti.has_variants', 0)->where('ti.disabled', 0)
            ->orderby('tva.idx', 'asc')
            ->get();

        $itemDesc = DB::table('tabItem')->select('description', 'variant_of')
            ->where('name', $item_code)
            ->first();
        
        $parentItemCode = ($itemDesc) ? $itemDesc->variant_of : redirect()->back()->with('notFound','Item code <b>'.$item_code.'</b> not found.') ;

        $parentDesc = DB::table('tabItem')->select('description')
            ->where('name', $parentItemCode)
            ->get();

        $attributes = [];
        $attribute_values = [];

        foreach($itemAttrib as $attrib){
            $c_attrib = DB::table('tabItem Variant Attribute')->select('parent', 'attribute', 'attribute_value')
                ->where('attribute', $attrib->attribute)
                ->where('attribute_value', $attrib->attribute_value)
                ->count();

            $getAbbr = DB::table('tabItem Attribute Value')
                ->where('parent', $attrib->attribute)
                ->where('attribute_value', $attrib->attribute_value)
                ->select('abbr')
                ->first();

            // $count = count($c_attrib);
            $attribute_values[] = [
                'attribute' => $attrib->attribute,
                'attribute_value' => $attrib->attribute_value,
                'abbr' => ($getAbbr) ? $getAbbr->abbr : null,
                'count' => $c_attrib
            ];
        }

        // return $attribute_values;

        return view('item_attributes_updating.item_attrib_update_form', compact('itemAttrib', 'item_code', 'itemDesc', 'parentDesc', 'attribute_values'));
    }

    public function add_attrib_form(Request $request, $item_code){
        $itemDetails = DB::table('tabItem')->where('name', strtoupper($item_code))->first();
        if(!$itemDetails) {
            return 'Item not found.';
        }
        
        $itemParent = DB::table('tabItem')->where('name', $itemDetails->variant_of)->first();

        $itemAttributes = DB::table('tabItem Variant Attribute')->where('parent', $itemDetails->variant_of)->orderBy('idx', 'asc')->pluck('attribute');

        $itemVariants = DB::table('tabItem')->where('is_stock_item', 1)->where('has_variants', 0)
            ->where('variant_of', $itemDetails->variant_of)
            ->select('item_code', 'disabled')->orderBy('creation', 'asc')->get();

        $itemVariantsArr = [];
        $itemsIncompleteAttr = [];
        foreach($itemVariants as $itemVariant) {
            $attributes = DB::table('tabItem Variant Attribute')->where('parent', $itemVariant->item_code)->orderBy('idx', 'asc')->get();

            if(count($itemAttributes) == count($attributes)){
                $itemVariantsArr[] = [
                    'item_code' => $itemVariant->item_code,
                    'disabled' => $itemVariant->disabled,
                    'attributes' => $attributes
                ];
            } else {
                $itemsIncompleteAttr[] = [
                    'item_code' => $itemVariant->item_code,
                    'disabled' => $itemVariant->disabled,
                    'attributes' => $attributes
                ];
            }
        }

        return view('item_attributes_updating.item_attrib_create_form', compact('itemVariantsArr', 'itemDetails', 'itemAttributes', 'itemParent', 'itemsIncompleteAttr'));
    }
    
    public function item_attribute_update(Request $request){
        DB::beginTransaction();
        try {
            $affectedRows = 0;

            $itemDetails = DB::table('tabItem')->where('name', $request->itemCode)->first();
            $getItemVariants = DB::table('tabItem as i')->join('tabItem Variant Attribute as d', 'i.name', 'd.parent')
                ->where('i.is_stock_item', 1)->where('i.has_variants', 0)->where('i.variant_of', $itemDetails->variant_of)->get();
            
            $attrArr = [];
            $item_code_with_same_attr = DB::table('tabItem as i')->join('tabItem Variant Attribute as d', 'i.name', 'd.parent')
                ->where('i.is_stock_item', 1)->where('i.has_variants', 0)->where('i.disabled', 0)
                ->where('i.variant_of', $itemDetails->variant_of)->pluck('item_code');
    
            $attribName = $request->attribName;
            $newAttrib = $request->attrib;
            $currentAttrib = $request->currentAttrib;
            for($i = 0; $i < count($newAttrib); $i++){
                $getItemVariants = DB::table('tabItem as i')->join('tabItem Variant Attribute as d', 'i.name', 'd.parent')
                    ->where('i.is_stock_item', 1)->where('i.has_variants', 0)->where('attribute', $attribName[$i])->where('attribute_value', $newAttrib[$i])
                    ->where('i.variant_of', $itemDetails->variant_of)->whereIn('i.name', $item_code_with_same_attr)->select('item_code', 'attribute_value')->get();

                $item_code_with_same_attr = array_column($getItemVariants->toArray(), 'item_code');

                $attrArr[] = [
                    'attribute_name' => $attribName[$i],
                    'attribute_value' => $newAttrib[$i],
                    'item_code_with_same_attr' => $item_code_with_same_attr
                ];

                if($currentAttrib[$i] != $request->attrib[$i]) {
                    $attribVal = [
                        'attribute_value' => $request->attrib[$i]
                    ];
                    
                    $affectedRows += DB::table('tabItem Variant Attribute')->where('attribute', $attribName[$i])
                        ->where('attribute_value', $currentAttrib[$i])->update($attribVal);
                }
            }

            $itemDuplicate = collect($attrArr)->min('item_code_with_same_attr');
            $itemDuplicate = ($itemDuplicate) ? $itemDuplicate[0] : null;

            if($itemDuplicate) {
                if(strtoupper($request->itemCode) != strtoupper($itemDuplicate)) {
                    return response()->json(['status' => 0, 'message' => 'Item Variant <b>' . $itemDuplicate . '</b> already exists with same attributes.']);
                }
            }

            for($h=0; $h < count($currentAttrib); $h++){
                if($currentAttrib[$h] != $request->attrib[$h]) {
                    $attribVal2 = [
                        'attribute_value' => $request->attrib[$h],
                        'abbr' => $request->abbr[$h]
                    ];
                    
                    $affectedRows += DB::table('tabItem Attribute Value')->where('parent', $attribName[$h])
                        ->where('attribute_value', $currentAttrib[$h])->update($attribVal2);
                }
            }

            $attribVal3 = [
                'item_name' => $this->generateItemDescription($request->itemCode)['item_name'],
                'description' => $this->generateItemDescription($request->itemCode)['description']
            ];
            
            DB::table('tabItem')->where('name', $request->itemCode)->update($attribVal3);

            DB::commit();

            // return response()->json(['status' => 1, 'message' => 'Item attribute has been updated. <br><b>' . $affectedRows . '</b> transactions updated.']);
            return redirect('/search?item_code='.$request->itemCode)->with('success', 'Attribute Updated! <b>' . $affectedRows . '</b> transactions updated.');
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status' => 0, 'message' => 'Error updating. Please try again.']);
        }
       
        // $attribName = $request->attribName;
        // $newAttrib = $request->attrib;

        // $currentAttrib = $request->currentAttrib;
        // for($i=0; $i < count($newAttrib); $i++){
        //     $attribVal = [
        //         'attribute_value' => $request->attrib[$i]
        //     ];
        //     $updateAttrib = DB::table('tabItem Variant Attribute')
        //         ->where('attribute', $attribName[$i])
        //         ->where('attribute_value', $currentAttrib[$i])->update($attribVal);
        // }

        // for($h=0; $h < count($currentAttrib); $h++){
        //     $attribVal2 = [
        //         'attribute_value' => $request->attrib[$h],
        //         'abbr' => $request->abbr[$h]
        //     ];

        //     $updateNewAttrib = DB::table('tabItem Attribute Value')
        //         ->where('parent', $attribName[$h])
        //         ->where('attribute_value', $currentAttrib[$h])
        //         ->update($attribVal2);
        // }
        // // return $attribVal2;

        // $strAbbr = implode("-",$request->abbr);

        // $Abbr = json_decode(json_encode($strAbbr), true);

        // $itemName = $request->parDesc."-".$Abbr;

        // $attribVal3 = [
        //     // 'description' => $request->item_description
        //     'item_name' => $itemName,
        //     'description' => $this->generateItemDescription($request->itemCode)
        // ];

        // $updateDesc = DB::table('tabItem')
        //     ->where('name', $request->itemCode)->update($attribVal3);
        // // Original Value = 4424, Recessed Mounted, Special T-Runner, CRS, 1220 x 737 x 0.5mm
        // // return redirect('/search')->with('success','Attribute Updated!');
        // return redirect()->back()->with('success','Attribute Updated!');
    }

    public function item_attribute_dropdown(Request $request){
        return $attribValSelect = DB::table('tabItem Attribute Value')
            ->select('attribute_value')->distinct()
            ->where('parent', $request->attribute_name)
            ->orderby('attribute_value', 'asc')->pluck('attribute_value');
    }

    public function item_attribute_insert(Request $request){
        DB::beginTransaction();
        try {
            $now = Carbon::now()->toDateTimeString();
            $data = [];

            $itemCodes = $request->data['itemCode'];
            $newAttrVals = (isset($request->data['newAttrVal'])) ? $request->data['newAttrVal'] : [];
            $message = 'Items have been updated.';
            $displayCount = 1;
            $affectedRows = 0;
            if(count($newAttrVals) > 0) {
                foreach ($newAttrVals as $x => $newAttrVal) {
                    $data[] = [
                        'name' => uniqid(),
                        'creation' => $now,
                        'modified' => $now,
                        'modified_by' => Auth::user()->wh_user,
                        'owner' => Auth::user()->wh_user,
                        'docstatus' => 0,
                        'parent' => $itemCodes[$x],
                        'parentfield' => 'attributes',
                        'parenttype' => 'Item',
                        'idx' => $request->idx + 1,
                        'from_range' => 0,
                        'numeric_values' => 0,
                        'attribute' => $request->attributeName,
                        'to_range' => 0,
                        'increment' => 0,
                        'attribute_value' => $request->data['newAttrVal'][$x]
                    ];
                }
    
                foreach (array_unique($newAttrVals) as $x => $newAttrVal) {
                     $data[] = [
                        'name' => uniqid(),
                        'creation' => $now,
                        'modified' => $now,
                        'modified_by' => Auth::user()->wh_user,
                        'owner' => Auth::user()->wh_user,
                        'docstatus' => 0,
                        'parent' => $request->parentItem,
                        'parentfield' => 'attributes',
                        'parenttype' => 'Item',
                        'idx' => $request->idx + 1,
                        'from_range' => 0,
                        'numeric_values' => 0,
                        'attribute' => $request->attributeName,
                        'to_range' => 0,
                        'increment' => 0,
                        'attribute_value' => null
                    ];
                }

                DB::table('tabItem Variant Attribute')->insert($data);
    
                $message = 'Attribute <b>'. $request->attributeName .'</b> has been added to <b>' . count($data). '</b> out of <b>' . count($data). '</b> item(s).';
                $displayCount = 0;
            } else {
                $message = 'Item(s) has been updated.';
                $affectedRows = count($itemCodes);
            }

            foreach ($itemCodes as $n => $itemCode) {
                DB::table('tabItem')->where('name', $itemCode)->update([
                    'modified' => Carbon::now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'item_name' => $this->generateItemDescription($itemCode)['item_name'],
                    'description' => $this->generateItemDescription($itemCode)['description']
                ]);
            }

            DB::commit();

            return response()->json(['status' => 1, 'message' => $message, 'count' => $affectedRows, 'displayCount' => $displayCount]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status' => 0, 'message' => 'Error updating. Please try again.']);
        }
    }

    public function signout(){
        Auth::logout();
        return redirect('/update');
    }

    public function getAttributes(Request $request){
        return DB::table('tabItem Attribute')
            ->when($request->q, function($q) use ($request){
                return $q->where('name', 'like', '%'.$request->q.'%');
            })
            ->select('name as id', 'name as text')
            ->orderBy('modified', 'desc')->limit(10)->get();
    }

    public function generateItemDescription($item_code) {
        // generate item description based on variant attributes
        $itemDetails = DB::table('tabItem')->where('name', $item_code)->where('is_stock_item', 1)->where('has_variants', 0)->where('disabled', 0)->first();
        if($itemDetails) {
            $parentItem = DB::table('tabItem')->where('name', $itemDetails->variant_of)->first();
            if($parentItem) {
                $attributes = DB::table('tabItem Variant Attribute')->where('parent', $itemDetails->name)->select('attribute', 'attribute_value')->orderBy('idx', 'asc')->get()->toArray();

                $attributeValues = array_column($attributes, 'attribute_value');
                $attributeValues = array_filter($attributeValues, function($v) { return strtolower($v) != "n/a"; });

                $parentItemName = strip_tags($parentItem->item_name);
                $abbrArr = [];
                foreach($attributes as $attr) {
                    $attributeAbbr = DB::table('tabItem Attribute Value')->where('parent', $attr->attribute)->where('attribute_value', $attr->attribute_value)->first();
                    $abbrArr[] = ($attributeAbbr) ? ($attributeAbbr->abbr) : null;
                }

                $itemName = array_filter($abbrArr, function($v) { return !in_array(strtolower($v), ["n/a", "-"]); });
                $itemName = strip_tags($parentItemName) . '-' . implode("-", $itemName);

                return [
                    'item_name' => Str::limit(strtoupper($itemName), 140, ''),
                    'description' => strip_tags($parentItem->description) . ', ' . implode(", ", $attributeValues)
                ];
            }
        }
    }
}