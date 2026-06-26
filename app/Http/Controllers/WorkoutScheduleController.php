<?php

namespace App\Http\Controllers;

class WorkoutScheduleController extends Controller
{
    public function index()
    {
        return view('workout-schedule.index');
    }
}
