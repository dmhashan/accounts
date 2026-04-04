<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkoutSystemSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | RESET TABLES
        |--------------------------------------------------------------------------
        */
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('workout_day_exercises')->truncate();
        DB::table('workout_program_days')->truncate();
        DB::table('workout_programs')->truncate();
        DB::table('exercise_variations')->truncate();
        DB::table('exercises')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::transaction(function () {

            $tenantIds = DB::table('tenants')->pluck('id');

            foreach ($tenantIds as $tenantId) {

                /*
            |--------------------------------------------------------------------------
            | EXERCISES + VARIATIONS
            |--------------------------------------------------------------------------
            */

                $exerciseMap = [];

                $exercises = [

                    'Squat' => ['Bodyweight Squat', 'Goblet Squat', 'Dumbbell Squat'],
                    'Deadlift' => ['Dumbbell Deadlift', 'Romanian Deadlift'],
                    'Chest Press' => ['Push-up', 'Incline Push-up', 'Dumbbell Chest Press', 'Bench Press'],
                    'Row' => ['Seated Row', 'Cable Row'],
                    'Shoulder Press' => ['Dumbbell Shoulder Press', 'Machine Shoulder Press'],
                    'Plank' => ['Side Plank'],
                    'Glute Bridge' => ['Marching Glute Bridge'],
                    'Hip Thrust' => ['Barbell Hip Thrust'],
                    'Step Up' => ['Step-ups'],
                    'Calf Raise' => ['Standing Calf Raise'],
                    'Kickback' => ['Glute Kickback', 'Cable Kickback'],
                    'Lat Pulldown' => ['Lat Pulldown'],
                    'Bicep Curl' => ['Dumbbell Curl', 'Barbell Curl', 'Hammer Curl'],
                    'Tricep' => ['Bench Dips', 'Cable Pushdown', 'Overhead Extension'],
                    'Core' => ['Dead Bug', 'Reverse Crunch', 'Side Plank'],
                    'HIIT' => ['Jumping Jacks', 'Mountain Climbers', 'High Knees', 'Burpees'],
                    'Leg Curl' => ['Prone Leg Curl'],
                    'Leg Extension' => ['Leg Extension'],
                    'Leg Press' => ['Leg Press'],
                    'Lunge' => ['Walking Lunges', 'Bulgarian Split Squat'],
                    'Face Pull' => ['Face Pull'],
                    'Lateral Raise' => ['Lateral Raise', 'Dumbbell Lateral Raise'],
                    'Cardio' => ['Cycling', 'Treadmill', 'Walking'],
                ];

                foreach ($exercises as $name => $variations) {

                    $id = DB::table('exercises')->insertGetId([
                        'tenant_id' => $tenantId,
                        'name' => $name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $exerciseMap[$name] = $id;

                    foreach ($variations as $variation) {
                        DB::table('exercise_variations')->insert([
                            'exercise_id' => $id,
                            'variation_name' => $variation,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                /*
            |--------------------------------------------------------------------------
            | HELPER
            |--------------------------------------------------------------------------
            */

                $ex = fn($name) => $exerciseMap[$name];

                $createProgram = function ($name, $days) use ($ex, $tenantId) {

                    $programId = DB::table('workout_programs')->insertGetId([
                        'tenant_id' => $tenantId,
                        'title' => $name,
                        'duration_weeks' => 4,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    foreach ($days as $dayIndex => $day) {

                        $dayId = DB::table('workout_program_days')->insertGetId([
                            'program_id' => $programId,
                            'title' => $day['name'],
                            'day_number' => $dayIndex + 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        foreach ($day['exercises'] as $i => $e) {

                            DB::table('workout_day_exercises')->insert([
                                'day_id' => $dayId,
                                'exercise_id' => $ex($e['name']),
                                'w1_w3_exercise' => $e['name'],
                                'w2_w4_exercise' => $e['name'],
                                'sets' => $e['sets'] ?? 1,
                                'reps' => (string) ($e['reps'] ?? 10),
                                'tempo' => '3-1-1-0',
                                'exercise_order' => $i + 1,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                };

                /*
            |--------------------------------------------------------------------------
            | WORKOUT PLAN 01
            |--------------------------------------------------------------------------
            */

                $createProgram('Workout Plan 01', [

                    [
                        'name' => 'Day 1 – Full Body + Core',
                        'exercises' => [
                            ['name' => 'Squat', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Deadlift', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Chest Press', 'sets' => 3, 'reps' => 10],
                            ['name' => 'Row', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Shoulder Press', 'sets' => 3, 'reps' => 10],
                            ['name' => 'Plank', 'sets' => 3, 'duration' => 30],
                            ['name' => 'Glute Bridge', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Cardio', 'duration' => 600],
                        ]
                    ],

                    [
                        'name' => 'Day 2 – Lower Body',
                        'exercises' => [
                            ['name' => 'Squat', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Hip Thrust', 'sets' => 3, 'reps' => 15],
                            ['name' => 'Step Up', 'sets' => 3, 'reps' => 10],
                            ['name' => 'Deadlift', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Calf Raise', 'sets' => 3, 'reps' => 15],
                            ['name' => 'Kickback', 'sets' => 3, 'reps' => 12],
                        ]
                    ],

                    [
                        'name' => 'Day 4 – Upper Body + Core',
                        'exercises' => [
                            ['name' => 'Chest Press', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Lat Pulldown', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Shoulder Press', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Bicep Curl', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Tricep', 'sets' => 3, 'reps' => 10],
                            ['name' => 'Core', 'sets' => 3, 'reps' => 12],
                        ]
                    ],

                    [
                        'name' => 'Day 5 – HIIT',
                        'exercises' => [
                            ['name' => 'HIIT', 'sets' => 3, 'duration' => 60],
                        ]
                    ],

                ]);

                /*
            |--------------------------------------------------------------------------
            | WORKOUT PLAN 02
            |--------------------------------------------------------------------------
            */

                $createProgram('Workout Plan 02', [

                    [
                        'name' => 'Full Body',
                        'exercises' => [
                            ['name' => 'Cardio', 'duration' => 600],
                            ['name' => 'Squat', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Squat', 'sets' => 2, 'reps' => 12],
                            ['name' => 'Leg Curl', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Lat Pulldown', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Chest Press', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Shoulder Press', 'sets' => 2, 'reps' => 12],
                            ['name' => 'Bicep Curl', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Tricep', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Calf Raise', 'sets' => 3, 'reps' => 15],
                        ]
                    ]

                ]);

                /*
            |--------------------------------------------------------------------------
            | WORKOUT PLAN 03 (PPL)
            |--------------------------------------------------------------------------
            */

                $createProgram('Workout Plan 03', [

                    [
                        'name' => 'Push',
                        'exercises' => [
                            ['name' => 'Chest Press', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Shoulder Press', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Lateral Raise', 'sets' => 3, 'reps' => 15],
                            ['name' => 'Tricep', 'sets' => 3, 'reps' => 15],
                        ]
                    ],

                    [
                        'name' => 'Pull',
                        'exercises' => [
                            ['name' => 'Lat Pulldown', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Row', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Face Pull', 'sets' => 3, 'reps' => 15],
                            ['name' => 'Bicep Curl', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Plank', 'duration' => 60],
                        ]
                    ],

                    [
                        'name' => 'Legs',
                        'exercises' => [
                            ['name' => 'Leg Press', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Leg Extension', 'sets' => 3, 'reps' => 15],
                            ['name' => 'Leg Curl', 'sets' => 3, 'reps' => 15],
                            ['name' => 'Calf Raise', 'sets' => 4, 'reps' => 15],
                            ['name' => 'HIIT', 'duration' => 1200],
                        ]
                    ],

                ]);

                /*
            |--------------------------------------------------------------------------
            | WORKOUT PLAN 04
            |--------------------------------------------------------------------------
            */

                $createProgram('Workout Plan 04', [

                    [
                        'name' => 'Day 01',
                        'exercises' => [
                            ['name' => 'Hip Thrust', 'sets' => 4, 'reps' => 12],
                            ['name' => 'Lunge', 'sets' => 3, 'reps' => 10],
                            ['name' => 'Squat', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Leg Press', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Lunge', 'sets' => 2, 'reps' => 12],
                            ['name' => 'Kickback', 'sets' => 3, 'reps' => 15],
                            ['name' => 'Calf Raise', 'sets' => 3, 'reps' => 20],
                        ]
                    ],

                    [
                        'name' => 'Day 02',
                        'exercises' => [
                            ['name' => 'Lat Pulldown', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Row', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Shoulder Press', 'sets' => 3, 'reps' => 10],
                            ['name' => 'Lateral Raise', 'sets' => 3, 'reps' => 15],
                            ['name' => 'Face Pull', 'sets' => 3, 'reps' => 15],
                            ['name' => 'Chest Press', 'sets' => 3, 'reps' => 10],
                            ['name' => 'Bicep Curl', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Tricep', 'sets' => 3, 'reps' => 12],
                        ]
                    ],

                    [
                        'name' => 'Day 03',
                        'exercises' => [
                            ['name' => 'Deadlift', 'sets' => 4, 'reps' => 12],
                            ['name' => 'Hip Thrust', 'sets' => 4, 'reps' => 12],
                            ['name' => 'Step Up', 'sets' => 3, 'reps' => 10],
                            ['name' => 'Leg Curl', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Deadlift', 'sets' => 3, 'reps' => 12],
                            ['name' => 'Kickback', 'sets' => 3, 'reps' => 20],
                        ]
                    ],

                ]);
            } // end foreach tenantIds

        });
    }
}
