<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
// use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
// use App\StockReservation;
// use Illuminate\Validation\Rule;
use Validator;
use Auth;
use DB;
use App\LdapClasses\adLDAP;

class ItemAttributeController extends Controller
{
    public function update_login(){
        return view('login');
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
            
        return view('item_attribute_search', compact('itemAttrib'));
    }

    public function update_attrib_form(Request $request){     
        $item_code = $request->query('u_item_code');

        $itemAttrib = DB::table('tabItem Variant Attribute as tva')
            ->join('tabItem as ti', 'tva.parent', 'ti.name')
            ->where('tva.parent', $item_code)
            ->where('ti.is_stock_item', 1)->where('ti.has_variants', 0)->where('ti.disabled', 0)
            ->orderby('tva.idx', 'asc')
            ->get();

        $itemDesc = DB::table('tabItem')
            ->where('name', $item_code)
            ->get();
        
        return view('item_attrib_update_form', compact('itemAttrib', 'item_code', 'itemDesc'));
    }

    public function add_attrib_form(Request $request, $item_code){
        $itemDetails = DB::table('tabItem')->where('name', $item_code)->first();
        if(!$itemDetails) {
            return 'Item not found.';
        }

        $itemParent = DB::table('tabItem')->where('name', $itemDetails->variant_of)->first();

        $itemAttributes = DB::table('tabItem Variant Attribute')->where('parent', $itemDetails->variant_of)->orderBy('idx', 'asc')->pluck('attribute');

        $itemVariants = DB::table('tabItem')->where('is_stock_item', 1)->where('has_variants', 0)->where('disabled', 0)->where('variant_of', $itemDetails->variant_of)->pluck('item_code');

        $itemVariantsArr = [];
        foreach($itemVariants as $itemVariant) {
            $attributes = DB::table('tabItem Variant Attribute')->where('parent', $itemVariant)->orderBy('idx', 'asc')->get();

            $itemVariantsArr[] = [
                'item_code' => $itemVariant,
                'attributes' => $attributes
            ];
        }

        // return $itemVariantsArr;


        return view('item_attrib_create_form', compact('itemVariantsArr', 'itemDetails', 'itemAttributes', 'itemParent'));

        $itemAttrib = DB::table('tabItem Variant Attribute as tva')
            ->join('tabItem as ti', 'tva.parent', 'ti.name')
            ->where('tva.parent', $item_code)
            ->where('ti.is_stock_item', 1)->where('ti.has_variants', 0)->where('ti.disabled', 0)
            ->orderby('tva.idx', 'asc')
            ->get();

        

        $attribSelect = DB::table('tabItem Attribute Value')->select('parent')->distinct()->orderby('parent', 'asc')->get();

        $idx = DB::table('tabItem Variant Attribute')->where('parent', $item_code)->orderby('creation', 'asc')->value('idx');

        return view('item_attrib_create_form', compact('attribSelect', 'idx', 'itemAttrib', 'item_code'));
    }
    
    // public function item_attribute_search(Request $request){
    //     $itemAttrib = DB::table('tabItem Variant Attribute as tva')
    //         ->join('tabItem as ti', 'tva.parent', 'ti.name')
    //         ->where('tva.parent', $request->item_code)
    //         ->where('ti.is_stock_item', 1)->where('ti.has_variants', 0)->where('ti.disabled', 0)
    //         ->orderby('tva.idx', 'asc')
    //         ->get();

    //     $attribSelect = DB::table('tabItem Attribute Value')->select('parent')->distinct()->orderby('parent', 'asc')->get();

    //     $idx = DB::table('tabItem Variant Attribute')->where('parent', $request->item_code)->orderby('creation', 'asc')->value('idx');
        
    //     return view('item_attribute', compact('itemAttrib', 'attribSelect', 'idx'));
    // }
    
    public function item_attribute_update(Request $request){
        $attribVal = [];
        $attribVal2 = [];
        $attribVal3 = [];
        $attribName = $request->attribName;
        $newAttrib = $request->attrib;

        $currentAttrib = $request->currentAttrib;
        for($i=0; $i < count($newAttrib); $i++){
            $attribVal = [
                'attribute_value' => $request->attrib[$i]
            ];
            $updateAttrib = DB::table('tabItem Variant Attribute')
                ->where('attribute', $attribName[$i])
                ->where('attribute_value', $currentAttrib[$i])->update($attribVal);
        }

        for($h=0; $h < count($currentAttrib); $h++){
            $attribVal2 = [
                'attribute_value' => $request->attrib[$h]
            ];

            $updateNewAttrib = DB::table('tabItem Attribute Value')
                ->where('parent', $attribName[$h])
                ->where('attribute_value', $currentAttrib[$h])
                ->update($attribVal2);
        }

        $attribVal3 = [
            'description' => $request->item_description
        ];

        $updateDesc = DB::table('tabItem')
            ->where('name', $request->itemCode)->update($attribVal3);
        // Original Value = 4424, Recessed Mounted, Special T-Runner, CRS, 1220 x 737 x 0.5mm
        // return redirect('/search')->with('success','Attribute Updated!');
        return redirect()->back()->with('success','Attribute Updated!');
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
            foreach ($request->newAttr as $x => $newAttr) {
                $data[] = [
                    'name' => uniqid(),
                    'creation' => $now,
                    'modified' => $now,
                    'modified_by' => Auth::user()->wh_user,
                    'owner' => Auth::user()->wh_user,
                    'docstatus' => 0,
                    'parent' => $request->itemCode[$x],
                    'parentfield' => 'attributes',
                    'parenttype' => 'Item',
                    'idx' => $request->idx[$x] + 1,
                    'from_range' => 0,
                    'numeric_values' => 0,
                    'attribute' => $newAttr,
                    'to_range' => 0,
                    'increment' => 0,
                    'attribute_value' => $request->newAttrVal[$x]
                ];
            }

            foreach (array_unique($request->newAttr) as $x => $newAttr) {
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
                    'idx' => $request->idx[$x] + 1,
                    'from_range' => 0,
                    'numeric_values' => 0,
                    'attribute' => $newAttr,
                    'to_range' => 0,
                    'increment' => 0,
                    'attribute_value' => null
                ];
            }

            DB::table('tabItem Variant Attribute')->insert($data);

            DB::commit();

            return redirect()->back()->with('message', 'Attribute <b>'. implode(", ", array_unique($request->newAttr)) .'</b> has been added.');
        } catch (Exception $e) {
            DB::rollback();

            return redirect()->back()->with('message', 'Error saving. Please try again.');
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

    public function getAttributeValues(Request $request){
        return DB::table('tabItem Attribute Value')
            ->where('parent', $request->attr)
            ->when($request->q, function($q) use ($request){
                return $q->where('name', 'like', '%'.$request->q.'%');
            })
            ->select('name as id', 'name as text')
            ->orderBy('modified', 'desc')->limit(10)->get();
    }
}