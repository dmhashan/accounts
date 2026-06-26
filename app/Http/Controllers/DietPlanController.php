<?php

namespace App\Http\Controllers;

class DietPlanController extends Controller
{
    public function index()
    {
        return view('diet-plan.index');
    }
}
