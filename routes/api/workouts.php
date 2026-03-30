<?php

use App\Http\Controllers\Api\ExerciseApiController;
use App\Http\Controllers\Api\WorkoutProgramApiController;
use Illuminate\Support\Facades\Route;

Route::get('/exercises', [ExerciseApiController::class, 'index'])->middleware(['auth', 'permission:workouts.manage']);
Route::post('/exercises', [ExerciseApiController::class, 'store'])->middleware(['auth', 'permission:workouts.manage']);
Route::get('/exercises/{exercise}', [ExerciseApiController::class, 'show'])->middleware(['auth', 'permission:workouts.manage']);
Route::put('/exercises/{exercise}', [ExerciseApiController::class, 'update'])->middleware(['auth', 'permission:workouts.manage']);
Route::delete('/exercises/{exercise}', [ExerciseApiController::class, 'destroy'])->middleware(['auth', 'permission:workouts.manage']);

Route::get('/workout-programs', [WorkoutProgramApiController::class, 'index'])->middleware(['auth', 'permission:workouts.manage']);
Route::post('/workout-programs', [WorkoutProgramApiController::class, 'store'])->middleware(['auth', 'permission:workouts.manage']);
Route::get('/workout-programs/{program}', [WorkoutProgramApiController::class, 'show'])->middleware(['auth', 'permission:workouts.manage']);
Route::put('/workout-programs/{program}', [WorkoutProgramApiController::class, 'update'])->middleware(['auth', 'permission:workouts.manage']);
Route::delete('/workout-programs/{program}', [WorkoutProgramApiController::class, 'destroy'])->middleware(['auth', 'permission:workouts.manage']);
Route::get('/workout-programs/{program}/customer-view', [WorkoutProgramApiController::class, 'customerView'])->middleware(['auth', 'permission:workouts.manage']);

Route::post('/workout-programs/{program}/days', [WorkoutProgramApiController::class, 'addDay'])->middleware(['auth', 'permission:workouts.manage']);
Route::put('/workout-program-days/{day}', [WorkoutProgramApiController::class, 'updateDay'])->middleware(['auth', 'permission:workouts.manage']);
Route::delete('/workout-program-days/{day}', [WorkoutProgramApiController::class, 'destroyDay'])->middleware(['auth', 'permission:workouts.manage']);

Route::post('/workout-program-days/{day}/exercises', [WorkoutProgramApiController::class, 'addDayExercise'])->middleware(['auth', 'permission:workouts.manage']);
Route::put('/workout-day-exercises/{dayExercise}', [WorkoutProgramApiController::class, 'updateDayExercise'])->middleware(['auth', 'permission:workouts.manage']);
Route::delete('/workout-day-exercises/{dayExercise}', [WorkoutProgramApiController::class, 'destroyDayExercise'])->middleware(['auth', 'permission:workouts.manage']);

Route::post('/workout-programs/{program}/extras', [WorkoutProgramApiController::class, 'addExtra'])->middleware(['auth', 'permission:workouts.manage']);
Route::put('/workout-program-extras/{extra}', [WorkoutProgramApiController::class, 'updateExtra'])->middleware(['auth', 'permission:workouts.manage']);
Route::delete('/workout-program-extras/{extra}', [WorkoutProgramApiController::class, 'destroyExtra'])->middleware(['auth', 'permission:workouts.manage']);
