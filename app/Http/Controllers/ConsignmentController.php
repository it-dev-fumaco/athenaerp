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

    }
}