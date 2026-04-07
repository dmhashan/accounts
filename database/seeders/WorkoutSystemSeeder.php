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
                    'BICEPS  (OVERALL)' => ['Barbell Curl', 'Ez Bar Curl', 'Alternating Dumbbell Curl', 'Standing Dumbbell Curl', 'Seated Dumbbell Curl', 'Concentration Curl', 'Cable Curl', 'Straight Bar Cable Curl', 'Single Arm Cable Curl'],
                    'BICEPS  (LONG HEAD BIAS)' => ['Close Grip Ez Bar Curl', 'Close Grip Cable Curl', 'Close Grip Barbell Curl', 'Incline Dumbbell Curl', 'Dumbbell Drag Curl', 'Barbell Drag Curl', 'Cable Drag Curl', 'Bayesian Cable Curl', 'Behind the Back Cable Curl'],
                    'BICEPS  (SHORT HEAD BIAS)' => ['Wide Grip Ez Bar Curl', 'Wide Grip Barbell Curl', 'Wide Grip Ez Bar Spider Curl', 'Wide Grip Dumbbell Spider Curl', 'Wide Grip Barbell Spider Curl', 'Wide Grip Cable Spider Curl', 'High Cable Curl', 'Wide Grip Cable Preacher Curl', 'Spider Curl', 'Preacher Curl', 'Cable Preacher Curl', 'Machine Preacher Curl'],
                    'BICEPS  (BRACHIALIS BIAS)' => ['Reverse Ez Bar Curl', 'Reverse Barbell Curl', 'Reverse Dumbbell Curl', 'Reverse Cable Curl', 'Standing Hammer Curl', 'Seated Hammer Curl', 'Incline Hammer Curl', 'Spider Hammer Curl', 'Spider Reverse Ez Bar Curl', 'Cross Body Hammer Curl', 'Rope Cable Curl', 'Zottman Curl'],
                    'TRICEPS (OVERALL BIAS)' => ['Cable Tricep Pushdown', 'Straight Bar Pushdown', 'V-Bar Pushdown', 'Rope Pushdown', 'Close Grip Bench Press', 'Bench Dips', 'Parallel Bar Dips', 'Skull Crushers', 'EZ Bar Skull Crushers', 'Dumbbell Skull Crushers', 'Single Arm Cable Pushdown', 'Machine Tricep Extension', 'Katana Extension', 'Katana Overhead Extension'],
                    'TRICEPS (LONG HEAD BIAS)' => ['Overhead Dumbbell Tricep Extension', 'Single Dumbbell Overhead Extension', 'Seated Overhead Dumbbell Extension', 'Standing Overhead Dumbbell Extension', 'Overhead Cable Tricep Extension', 'Rope Overhead Cable Extension', 'Single Arm Overhead Cable Extension', 'EZ Bar Overhead Extension', 'Barbell Overhead Extension', 'Incline Dumbbell Tricep Extension', 'Incline EZ Bar Skull Crushers', 'PJR Pullover', 'Dumbbell French Press', 'EZ Bar French Press'],
                    'TRICEPS (LATERAL HEAD BIAS)' => ['Rope Pushdown', 'Straight Bar Pushdown', 'V-Bar Pushdown', 'Reverse Grip Cable Pushdown', 'Cable Kickback', 'Dumbbell Kickback', 'Single Arm Pushdown', 'Bench Dips', 'Machine Pushdown', 'Close Grip Push-Up'],
                    'TRICEPS (MEDIAL HEAD BIAS)' => ['Reverse Grip Pushdown', 'Reverse Grip Straight Bar Pushdown', 'Single Arm Reverse Pushdown', 'Diamond Push-Ups', 'Close Grip Bench Press', 'Cable Pushdown (strict form)', 'EZ Bar Skull Crushers', 'Machine Tricep Press', 'Dips (lockout emphasis)'],
                    'CHEST (OVERALL)' => ['Barbell Bench Press', 'Dumbbell Bench Press', 'Machine Chest Press', 'Smith Machine Bench Press', 'Push-Ups', 'Weighted Push-Ups', 'Chest Press Machine', 'Cable Chest Press', 'Hammer Strength Chest Press', 'Plate Loaded Chest Press'],
                    'UPPER CHEST BIAS' => ['Incline Barbell Bench Press', 'Incline Dumbbell Press', 'Incline Smith Machine Press', 'Incline Machine Chest Press', 'Low to High Cable Fly', 'Incline Cable Fly', 'Incline Dumbbell Fly', 'Incline Hammer Strength Press', 'Reverse Grip Bench Press', 'Feet Elevated Push-Ups', 'Single Arm Low to High Cable Fly', 'Landmine Press'],
                    'MID CHEST BIAS' => ['Flat Barbell Bench Press', 'Flat Dumbbell Press', 'Machine Chest Press', 'Flat Dumbbell Fly', 'Cable Chest Fly', 'Pec Deck Fly', 'Smith Machine Flat Bench Press', 'Hammer Strength Flat Press', 'Push-Ups', 'Paused Bench Press'],
                    'LOWER CHEST BIAS' => ['Decline Barbell Bench Press', 'Decline Dumbbell Press', 'Decline Smith Machine Press', 'High to Low Cable Fly', 'Chest Dips', 'Decline Push-Ups', 'Decline Machine Chest Press', 'Single Arm High to Low Cable Fly', 'Leaning Forward Dips', 'Assisted Chest Dips'],
                    'INNER CHEST (SQUEEZE BIAS)' => ['Cable Fly', 'Pec Deck Fly', 'Dumbbell Fly', 'Svend Press', 'Hex Press / Dumbbell Crush Press', 'Close Grip Plate Press', 'Single Arm Cable Fly (cross body)', 'Standing Cable Fly', 'Mid Cable Fly with squeeze', 'Isometric Chest Squeeze Press'],
                    'OVERALL BACK' => ['Pull-Ups', 'Chin-Ups', 'Wide Grip Lat Pulldown', 'Close Grip Lat Pulldown', 'Seated Cable Row', 'Overhand Barbell Rows', 'Underhand Barbell Rows', 'Single Arm Dumbbell Rows', 'Both Arms Dumbbell Row', 'T-Bar Row', 'Landmine Row', 'Machine Row', 'Hammer Strength Row', 'Conventional Deadlift', 'Trap Bar Deadlift', 'Rack Pull'],
                    'UPPER BACK BIAS' => ['Barbell Shrugs', 'Dumbbell Shrugs', 'Smith Machine Shrugs', 'Upright Rows', 'Face Pulls', 'Reverse Pec Deck', 'Rear Delt Fly', 'High Cable Row (to upper chest/neck level)', 'Inverted Rows (Bodyweight, elevated feet)', 'Power Shrugs'],
                    'LATS / WIDTH BIAS' => ['Wide Grip Pull Ups', 'Assisted Pull Ups', 'Wide Grip Lat Pulldown', 'Straight Arm Pulldown', 'Close / Neutral Grip Pulldown', 'Dumbbell Pullovers', 'Single Arm Cable Pulldown'],
                    'MIDDLE BACK BIAS' => ['Overhand Barbell Rows', 'Underhand Barbell Rows', 'T-Bar Row', 'Seated Cable Row (Chest Supported or Upright)', 'Single Arm Dumbbell Rows', 'Both Arms Dumbbell Rows', 'Machine Row', 'Hammer Strength Row'],
                    'LOWER BACK BIAS' => ['Conventional Deadlift', 'Romanian Deadlift', 'Sumo Deadlift', 'Hyperextensions', 'Barbell Good Morning', 'Dumbbell Good Morning', 'Reverse Hyperextensions', 'Cable Pull-Throughs', 'Kettlebell Swings'],
                    'INNER BACK BIAS' => ['Close Grip Seated Row with Pause at Squeeze', 'Cable Row with Hold at Contraction', 'Reverse Pec Deck Fly (slow)', 'Dumbbell Rows with Squeeze at top', 'Isometric Scapular Retractions (hold 5–10 sec)'],
                    'OVERALL SHOULDER' => ['Standing Overhead Barbell Press', 'Seated Overhead Barbell Press', 'Seated Dumbbell Shoulder Press', 'Standing Dumbbell Shoulder Press', 'Arnold Press', 'Smith Machine Overhead Press', 'Machine Shoulder Press', 'Landmine Press', 'Barbell Push Press', 'Dumbbell Push Press'],
                    'ANTERIOR DELTOID BIAS' => ['Barbell Front Raise', 'Ez Bar Front Raise', 'Dumbbell Front Raise (Neutral / Overhand grip)', 'Plate Front Raise', 'Cable Front Raise (Neutral / Overhand grip)', 'Single Arm Cable Front Raise', 'Overhead Press', 'Arnold Press'],
                    'LATERAL DELTOID BIAS' => ['Dumbbell Lateral Raise', 'Single Arm Cable Lateral Raise', 'Both Arm Cable Lateral Raise', 'Machine Lateral Raise', 'Leaning Lateral Raise', 'Upright Row (wide grip, lighter weight)'],
                    'POSTERIOR DELTOID BIAS' => ['Reverse Pec Deck', 'Incline Rear Delt Fly', 'Bent Over Dumbbell Reverse Fly', 'Single Arm Cable Rear Delt Fly', 'Both Arm Cable Rear Delt Fly', 'Standard Rope Face Pulls', 'Seated Face Pulls', 'Low to High Face Pulls', 'High to Low Face Pulls', 'Face Pull with External Rotation', 'Single Arm Face Pulls', 'Incline Rows with Rear Delt Focus'],
                    'ROTATOR CUFF / STABILITY FOCUS' => ['External Rotation (Dumbbell / Cable)', 'Internal Rotation (Dumbbell / Cable)', 'Cuban Press', 'Scaption', 'Band Pull-Aparts', 'Y Raises'],
                    'OVERALL LEGS' => ['Barbell Back Squats', 'Barbell Front Squats', 'Dumbbell Goblet Squats', 'Dumbbell Front Squats', 'Leg Press', 'Walking Lunges', 'Stationary Lunges', 'Reverse Lunges', 'Side Lunges', 'Step-Ups', 'Bulgarian Split Squats', 'Conventional Deadlift', 'Romanian Deadlift', 'Sumo Deadlift', 'Hack Squat Machine', 'Smith Machine Front Squats', 'Smith Machine Back Squats', 'Pendulum Squats'],
                    'QUADRICEPS BIAS' => ['Barbell Front Squat', 'Hack Squat', 'Leg Extension Machine', 'Step Ups (emphasize front leg push)', 'Split Squat (lean forward slightly)', 'Bulgarian Split Squat (lean forward slightly)', 'Walking Lunges (focus on front leg)'],
                    'HAMSTRING BIAS' => ['Dumbbell Romanian Deadlift', 'Barbell Romanian Deadlift', 'Smith Machine Romanian Deadlift', 'Lying Leg Curl Machine', 'Seated Leg Curl Machine', 'Glute Ham Raise', 'Good Mornings', 'Dumbbell Stiff Leg Deadlift', 'Barbell Stiff Leg Deadlift'],
                    'GLUTES BIAS' => ['Barbell Hip Thrust', 'Dumbbell Hip Thrust', 'Glute Bridges', 'Cable Kickbacks', 'Step Ups (rear leg activation)', 'Bulgarian Split Squat (lean forward, back leg focus)', 'Sumo Deadlift', 'Frog Pumps', 'Machine Hip Thrust', 'Smith Machine Hip Thrust'],
                    'CALVES / LOWER LEG BIAS' => ['Standing Calf Raise', 'Seated Calf Raise', 'Donkey Calf Raise', 'Single Leg Calf Raise', 'Smith Machine Calf Raise', 'Leg Press Calf Raise', 'Body Weight Calf Raise', 'Toe In Calf Raise', 'Toe Out Calf Raise'],
                    'ADDUCTOR / ABDUCTOR BIAS' => ['Lateral Squats', 'Side Lunge', 'Lateral Lunge', 'Cable Hip Abduction', 'Cable Hip Adduction', 'Machine Hip Abduction', 'Machine Hip Adduction', 'Banded Side Walks'],
                    'ABS WORKOUT OVERALL CORE' => ['Front Plank', 'Side Plank', 'Dead Bug', 'Hollow Body Hold', 'Bird Dog', 'Stability Ball Rollout', 'Cable Anti-Rotation Press (Pallof Press)', 'Ab Wheel Rollout', 'Medicine Ball Slams', 'Hanging Knee Raise', 'Hanging Leg Raise', 'Farmer’s Carry', 'Suitcase Carry', 'Landmine Twist', 'Weighted Plank', 'Suitcase Deadlift Carry'],
                    'UPPER ABS BIAS' => ['Crunches (Bodyweight / Weighted)', 'Sit-Ups (Bodyweight / Weighted)', 'Cable Crunch', 'Machine Crunch', 'V-Up', 'Jackknife Sit-Up', 'Reverse Cable Crunch (focus upper contraction)', 'Stability Ball Crunch', 'Medicine Ball Crunch'],
                    'LOWER ABS BIAS' => ['Leg Raises (Hanging or Lying)', 'Flutter Kicks', 'Scissor Kicks', 'Reverse Crunch', 'Lying Knee Tucks', 'Hanging Knee Raise', 'Dragon Flag', 'Incline Bench Leg Raise', 'Swiss Ball Knee Tuck', 'Cable Low Pulley Leg Raise'],
                    'OBLIQUES / SIDE CORE BIAS' => ['Russian Twists (Weighted / Bodyweight)', 'Side Plank (Static or Hip Dip)', 'Oblique Crunch (Side-Lying)', 'Cable Side Crunch', 'Woodchopper (Cable / Medicine Ball)', 'Landmine Side Twist', 'Suitcase Carry', 'Side-to-Side Medicine Ball Slam', 'Windshield Wipers (Hanging or Lying)', 'Side Bend (Dumbbell / Plate / Cable)'],
                    'ROTATIONL / ANTI ROTATION CORE' => ['Pallof Press (Cable / Band)', 'Landmine Twist / Rotation', 'Cable Chop (High to Low / Low to High)', 'Medicine Ball Rotational Throw', 'Anti-Rotation Plank (Single Arm / Single Leg)', 'Swiss Ball Rotational Roll', 'Barbell / Plate Twist', 'Overhead Press Hold (Static Anti-Rotation)'],
                    'LOWER BACK / SPINAL EXTENSION' => ['Hyperextensions', 'Back Extension', 'Supermans', 'Bird Dog', 'Good Mornings', 'Reverse Hyperextensions', 'Kettlebell Swings (Dynamic Lower Back Engagement)', 'Deadlift (Proper Form, Core Activation)', 'Stability Ball Back Extension'],
                    'GENERAL WARM-UP (FULL BODY / CARDIOVASCULAR)' => ['Jumping Jacks', 'High Knees', 'Butt Kicks', 'Skipping Rope', 'Arm Circles (Forward / Backward)', 'Torso Twists', 'Jog in Place / Light Treadmill Jog', 'Mountain Climbers', 'Shadow Boxing', 'Bear Crawl', 'Inch Worm Walkouts'],
                    'DYNAMIC STRETCHING (PRE-WORKOUT) - NECK / SHOULDERS' => ['Neck Rotations (Clockwise / Counterclockwise)', 'Shoulder Rolls (Forward / Backward)', 'Arm Swings (Cross Body & Overhead)', 'Scapular Push-Ups', 'Band Pull-Aparts'],
                    'DYNAMIC STRETCHING (PRE-WORKOUT) - CHEST / BACK' => ['Standing Chest Opener', 'Cat-Cow (Spinal Flexion / Extension)', 'Thoracic Spine Rotation (Quadruped or Standing)', 'Wall Slides', 'Band Chest Pull'],
                    'DYNAMIC STRETCHING (PRE-WORKOUT) - ARMS / BICEPS / TRICEPS' => ['Arm Circles (Small / Large)', 'Elbow to Ceiling Stretch (Dynamic)', 'Cross-Body Arm Swings'],
                    'DYNAMIC STRETCHING (PRE-WORKOUT) - CORE / ABS' => ['Standing Side Bends', 'Torso Rotations', 'Hip Circles / Hula Movements', 'Dynamic Plank Walkouts', 'Standing Knee-to-Elbow Twists'],
                    'DYNAMIC STRETCHING (PRE-WORKOUT) - LEGS / HIPS' => ['Walking Lunges', 'Lunge with Twist', 'Side Lunges', 'Leg Swings (Front-Back / Side-to-Side)', 'Hip Circles', 'Knee Hugs / Quad Pulls (Dynamic)', 'Walking High Kicks', 'Carioca / Grapevine'],
                    'DYNAMIC STRETCHING (PRE-WORKOUT) - ANKLES / FEET' => ['Ankle Circles (Both Directions)', 'Heel-to-Toe Rock', 'Calf Raises (Bodyweight)', 'Toe Walking / Heel Walking'],
                    'STATIC STRETCHING (POST-WORKOUT / COOL DOWN) - NECK / SHOULDERS' => ['Neck Side Stretch', 'Neck Forward / Backward Stretch', 'Shoulder Stretch Across Chest', 'Triceps Overhead Stretch'],
                    'STATIC STRETCHING (POST-WORKOUT / COOL DOWN) - CHEST / BACK' => ['Chest Stretch on Wall or Door Frame', 'Seated or Standing Forward Fold', 'Cat-Cow Hold', 'Child’s Pose', 'Lat Stretch on Wall or Overhead'],
                    'STATIC STRETCHING (POST-WORKOUT / COOL DOWN) - ARMS / BICEPS / TRICEPS' => ['Overhead Triceps Stretch', 'Cross-Body Shoulder Stretch', 'Wall Biceps Stretch'],
                    'STATIC STRETCHING (POST-WORKOUT / COOL DOWN) - CORE / ABS' => ['Cobra Stretch', 'Side Stretch / Triangle Pose', 'Seated Side Reach', 'Supine Twist (Spinal Rotation)'],
                    'STATIC STRETCHING (POST-WORKOUT / COOL DOWN) - LEGS / HIPS' => ['Standing Quad Stretch', 'Hamstring Stretch (Seated or Standing)', 'Glute Stretch / Figure Four', 'Hip Flexor Stretch (Lunge Position)', 'Butterfly Stretch', 'Pigeon Pose'],
                    'STATIC STRETCHING (POST-WORKOUT / COOL DOWN) - CALVES / ANKLES / FEET' => ['Downward Dog', 'Standing Calf Stretch on Wall', 'Seated Toe Reach', 'Ankle Pull / Foot Stretch'],
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
            } // end foreach tenantIds

        });
    }
}
