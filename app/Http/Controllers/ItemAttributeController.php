<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use App\StockReservation;
use Auth;
use DB;

class ItemAttributeController extends Controller
{
    public function item_attribute_search(Request $request){
        $itemAttrib = DB::table('tabItem Variant Attribute')->where('parent', $request->item_code)->orderby('idx', 'asc')->get();

        return view('item_attribute', compact('itemAttrib'));
    }
    
    public function item_attribute_update(Request $request){

        $attribVal = [];
        $attribName = $request->attribName;
        $newAttrib = $request->attrib;
        for($i=0; $i < count($newAttrib); $i++){
            $attribVal = [
                'attribute_value' => $request->attrib[$i],
                'attribute' => $request->attribName[$i]
            ];

            $updateAttrib = DB::table('tabItem Variant Attribute')->where('parent', $request->itemCode)->where('attribute', $attribName[$i])->update($attribVal);

        }
        
        return view('item_attribute_NNN');
    }
}
