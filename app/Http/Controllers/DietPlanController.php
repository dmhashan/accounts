<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DietPlanController extends Controller
{
    public function index()
    {
        return view('diet-plan.index');
    }
}
