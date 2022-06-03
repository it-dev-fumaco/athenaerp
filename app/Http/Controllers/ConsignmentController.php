<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Auth;
use DB;

class ConsignmentController extends Controller
{
    public function viewCalendarMenu($branch){
        return view('consignment.calendar_menu', compact('branch'));
    }

    public function viewProductSoldForm($branch, $transaction_date) {
        $items = DB::table('tabConsignment Beginning Inventory as cb')
            ->join('tabConsignment Beginning Inventory Item as cbi', 'cb.name', 'cbi.parent')
            ->join('tabItem as i', 'i.name', 'cbi.item_code')
            ->where('cb.status', 'Approved')
            ->where('i.disabled', 0)->where('i.is_stock_item', 1)
            ->where('cb.branch_warehouse', $branch)->select('i.item_code', 'i.description')
            ->orderBy('i.description', 'asc')->get();

        $item_codes = collect($items)->pluck('item_code');

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->select('parent', 'image_path')->orderBy('idx', 'asc')->get();
        $item_images = collect($item_images)->groupBy('parent')->toArray();

        $existing_record = DB::table('tabConsignment Product Sold')->where('branch_warehouse', $branch)
            ->where('transaction_date', $transaction_date)->pluck('qty', 'item_code')->toArray();

        return view('consignment.product_sold_form', compact('branch', 'transaction_date', 'items', 'item_images', 'existing_record'));
    }

    public function submitProductSoldForm(Request $request) {
        $data = $request->all();

        DB::beginTransaction();
        try {
            $now = Carbon::now();
            $result = [];
            $no_of_items_updated = 0;
            foreach ($data['item'] as $item_code => $row) {
                $existing = DB::table('tabConsignment Product Sold')
                    ->where('item_code', $item_code)->where('branch_warehouse', $data['branch_warehouse'])
                    ->where('transaction_date', $data['transaction_date'])->first();
                if ($existing) {
                    // for update
                    $values = [
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'qty' => $row['qty'],
                    ];

                    $no_of_items_updated++;

                    DB::table('tabConsignment Product Sold')->where('name', $existing->name)->update($values);
                } else {
                    // for insert
                    $no_of_items_updated++;
                    $result[] = [
                        'name' => uniqid(),
                        'creation' => $now->toDateTimeString(),
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'owner' => Auth::user()->wh_user,
                        'docstatus' => 0,
                        'parent' => null,
                        'parentfield' => null,
                        'parenttype' => null,
                        'idx' => 0,
                        'transaction_date' => $data['transaction_date'],
                        'branch_warehouse' => $data['branch_warehouse'],
                        'item_code' => $item_code,
                        'description' => $row['description'],
                        'qty' => $row['qty'],
                        'promodiser' => Auth::user()->full_name
                    ];
                }
            }

            if (count($result) > 0) {
                DB::table('tabConsignment Product Sold')->insert($result);
            }

            DB::commit();

            return redirect('/product_sold_success')->with([
                'success' => 'Record successfully updated',
                'no_of_items_updated' => $no_of_items_updated,
                'branch' => $data['branch_warehouse'],
                'transaction_date' => $data['transaction_date']
            ]);
        } catch (Exception $e) {
            DB::rollback();

            return redirect()->back()->with('error', 'An error occured. Please contact your system administrator.');
        }
    }

    public function productSoldSuccess() {
        return view('consignment.success_page');
    }

    public function calendarData($branch) {
        $query = DB::table('tabConsignment Product Sold')->where('branch_warehouse', $branch)
            ->select('transaction_date')->groupBy('transaction_date')->get();

        $data = [];
        foreach ($query as $row) {
            $data[] = [
                'title' => '',
                'start' => $row->transaction_date,
                'backgroundColor' => '#00a65a', //green
                'borderColor' => '#00a65a', //green
                'allDay' => true,
                'display' => 'background'
            ];
        }

        return $data;
    }
}