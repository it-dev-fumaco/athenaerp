<?php

namespace App\Http\Controllers;

use App\Models\AthenaTransaction;
use App\Models\Brand;
use App\Models\Item;
use App\Models\ItemClassification;
use App\Models\ItemGroup;
use App\Models\StockEntryDetail;
use App\Models\StockLedgerEntry;
use App\Models\Warehouse;
use App\Models\WarehouseAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SelectFilterController extends Controller
{
    public function getSelectFilters(Request $request)
    {
        $warehouses = Warehouse::where('is_group', 0)
            ->where('disabled', 0)
            ->whereIn('category', ['Physical', 'Consigned'])
            ->when($request->q, function ($query) use ($request) {
                return $query
                    ->where('name', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('warehouse_name', 'LIKE', '%' . $request->q . '%');
            })
            ->selectRaw('name as id, name as text')
            ->orderBy('name', 'asc')
            ->get();

        $itemGroups = ItemGroup::query()
            ->where('is_group', 0)
            ->where('name', 'LIKE', '%' . $request->q . '%')
            ->where('show_in_erpinventory', 1)
            ->selectRaw('name as id, name as text')
            ->orderBy('name', 'asc')
            ->get();

        $itemClassification = ItemClassification::query()
            ->orderBy('name', 'asc')
            ->pluck('name');

        $itemClassFilter = ItemClassification::query()
            ->where('name', 'LIKE', '%' . $request->q . '%')
            ->selectRaw('name as id, name as text')
            ->orderBy('name', 'asc')
            ->get();

        $brandFilter = Brand::query()
            ->where('name', 'LIKE', '%' . $request->q . '%')
            ->selectRaw('name as id, name as text')
            ->orderBy('name', 'asc')
            ->get();

        $athenaWhUsers = AthenaTransaction::query()
            ->groupBy('warehouse_user')
            ->where('warehouse_user', 'LIKE', '%' . $request->q . '%')
            ->where('status', 'Issued')
            ->selectRaw('warehouse_user as id, warehouse_user as text')
            ->get();

        $athenaSrcWh = AthenaTransaction::query()
            ->groupBy('source_warehouse')
            ->where('source_warehouse', 'LIKE', '%' . $request->q . '%')
            ->where('status', 'Issued')
            ->where('source_warehouse', '!=', '')
            ->selectRaw('source_warehouse as id, source_warehouse as text')
            ->get();

        $athenaToWh = AthenaTransaction::query()
            ->groupBy('target_warehouse')
            ->where('target_warehouse', 'LIKE', '%' . $request->q . '%')
            ->where('status', 'Issued')
            ->where('target_warehouse', '!=', '')
            ->selectRaw('target_warehouse as id, target_warehouse as text')
            ->get();

        $erpWhUsers = StockEntryDetail::query()
            ->groupBy('session_user')
            ->where('session_user', 'LIKE', '%' . $request->q . '%')
            ->selectRaw('session_user as id, session_user as text')
            ->get();

        $erpWh = StockLedgerEntry::query()
            ->groupBy('warehouse')
            ->where('warehouse', 'LIKE', '%' . $request->q . '%')
            ->selectRaw('warehouse as id, warehouse as text')
            ->get();

        return response()->json([
            'warehouses' => $warehouses,
            'warehouse_users' => $athenaWhUsers,
            'source_warehouse' => $athenaSrcWh,
            'target_warehouse' => $athenaToWh,
            'warehouse' => $erpWh,
            'session_user' => $erpWhUsers,
            'item_groups' => $itemGroups,
            'item_class_filter' => $itemClassFilter,
            'item_classification' => $itemClassification,
            'brand' => $brandFilter
        ]);
    }

    public function getParentWarehouses()
    {
        $user = Auth::user()->frappe_userid;
        $query = WarehouseAccess::query()
            ->from('tabWarehouse Access as wa')
            ->join('tabWarehouse Users as wu', 'wa.parent', 'wu.name')
            ->where('wu.frappe_userid', $user)
            ->get();

        $list = [];
        foreach ($query as $w) {
            $list[] = [
                'name' => $w->warehouse_name,
                'user' => $w->wh_user,
                'frappe_userid' => $w->frappe_userid
            ];
        }

        return response()->json(['wh' => $list]);
    }

    public function getItems(Request $request)
    {
        return Item::query()
            ->enabled()
            ->leafVariants()
            ->stockItem()
            ->when($request->q, function ($query) use ($request) {
                return $query->where('name', 'like', '%' . $request->q . '%');
            })
            ->selectRaw('name as id, name as text, description, stock_uom')
            ->orderBy('modified', 'desc')
            ->limit(10)
            ->get();
    }

    public function getWarehouses(Request $request)
    {
        return Warehouse::query()
            ->where('disabled', 0)
            ->where('is_group', 0)
            ->when($request->q, function ($query) use ($request) {
                return $query->where('name', 'like', '%' . $request->q . '%');
            })
            ->select('name as id', 'name as text')
            ->orderBy('modified', 'desc')
            ->limit(10)
            ->get();
    }

    public function getProjects(Request $request)
    {
        return DB::table('tabProject')
            ->when($request->q, function ($query) use ($request) {
                return $query->where('name', 'like', '%' . $request->q . '%');
            })
            ->select('name as id', 'name as text')
            ->orderBy('modified', 'desc')
            ->limit(10)
            ->get();
    }

    public function getSalesPersons(Request $request)
    {
        return DB::table('tabSales Person')
            ->where('enabled', 1)
            ->where('is_group', 0)
            ->when($request->q, function ($query) use ($request) {
                return $query->where('name', 'like', '%' . $request->q . '%');
            })
            ->select('name as id', 'name as text')
            ->orderBy('modified', 'desc')
            ->limit(10)
            ->get();
    }
}
