<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WorkoutScheduleController extends Controller
{
    public function index()
    {
        return view('workout-schedule.index');
    }
}
