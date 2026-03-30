<?php

namespace App\Http\Controllers;

class PhaseOutController extends Controller
{
    public function dashboard()
    {
        return view('phase_out.dashboard');
    }

    public function items()
    {
        return view('phase_out.items');
    }
}
