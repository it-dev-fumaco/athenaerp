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

    public function add_attrib_form(Request $request){
        // $item_code = $request->C_item_code;
        $item_code = $request->query('c_item_code');

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
        $now = Carbon::now();
        $email = Auth::user()->wh_user;

        $insertAttrib = [
            'name' => uniqid(),
            'creation' => $now,
            'modified' => $now,
            'modified_by' => $email,
            'owner' => $email,
            'docstatus' => 0,
            'parent' => $request->item_code,
            'parentfield' => 'attributes -test',
            'parenttype' => 'Item',
            'idx' => $request->new_idx,
            'from_range' => 0,
            'numeric_values' => 0,
            'attribute' => $request->selected_attribute_name,
            'to_range' => 0,
            'increment' => 0,
            'attribute_value' => $request->selected_attribute_value
        ];

        $checkAttrib = DB::table('tabItem Variant Attribute')
            ->where('parent', $request->item_code)
            // ->where('attribute_value', $request->selected_attribute_value)
            ->where('attribute', $request->selected_attribute_name)
            ->get();

        if(count($checkAttrib) == 0){
            $insert = DB::table('tabItem Variant Attribute')->insert($insertAttrib);
            return redirect()->back()->with('insertSuccess','Attribute Added!');
        }elseif(count($checkAttrib) > 0){
            $duplicate = $request->selected_attribute_name;
            // $CCItem = $request->selected_attribute_name;
            return redirect()->back()->with('duplicateValue', '<b>'.$request->selected_attribute_name.'</b>'.' attribute already exists!');
        }
        
        // $insert = DB::table('tabItem Variant Attribute')->insert($insertAttrib);

        // return redirect()->back()->with('insertSuccess','Attribute Added!');
    }

    public function signout(){
        Auth::logout();
        return redirect('/update');
    }
}