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
        $itemAttrib = DB::table('tabItem Variant Attribute as tva')
            ->join('tabItem as ti', 'tva.parent', 'ti.name')
            ->where('tva.parent', $request->item_code)
            ->where('ti.is_stock_item', 1)->where('ti.has_variants', 0)->where('ti.disabled', 0)
            ->orderby('tva.idx', 'asc')
            ->get();
        
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
        
        return redirect()->back()->with('success','Attribute Updated!');
    }
}
