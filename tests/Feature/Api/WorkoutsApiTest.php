<?php

namespace Tests\Feature\Api;

use App\Models\Exercise;
use App\Models\WorkoutDayExercise;
use App\Models\WorkoutProgram;
use App\Models\WorkoutProgramAssignment;
use App\Models\WorkoutProgramDay;
use App\Models\WorkoutProgramExtra;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class WorkoutsApiTest extends ApiRouteTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createExercise(array $attributes = []): Exercise
    {
        static $seq = 0;
        $seq++;

        return Exercise::create(array_merge([
            'name' => 'Exercise ' . $seq,
            'status' => 'active',
            'default_sets' => 3,
            'default_reps' => '10',
            'default_tempo' => '2-1-2',
            'default_rest' => 60,
        ], $attributes));
    }

    private function createProgram(array $attributes = []): WorkoutProgram
    {
        static $seq = 0;
        $seq++;

        return WorkoutProgram::create(array_merge([
            'title' => 'Program ' . $seq,
            'description' => 'Test program',
            'duration_weeks' => 4,
        ], $attributes));
    }

    private function createDay(WorkoutProgram $program, array $attributes = []): WorkoutProgramDay
    {
        static $seq = 0;
        $seq++;

        return WorkoutProgramDay::create(array_merge([
            'program_id' => $program->id,
            'day_number' => $seq,
            'title' => 'Day ' . $seq,
        ], $attributes));
    }

    private function createDayExercise(WorkoutProgramDay $day, Exercise $exercise, array $attributes = []): WorkoutDayExercise
    {
        return WorkoutDayExercise::create(array_merge([
            'day_id' => $day->id,
            'exercise_id' => $exercise->id,
            'w1_w3_exercise' => '',
            'w2_w4_exercise' => '',
            'sets' => 3,
            'reps' => '10',
            'tempo' => '2-1-2',
            'rest_seconds' => 60,
            'exercise_order' => 1,
        ], $attributes));
    }

    private function createExtra(WorkoutProgram $program, array $attributes = []): WorkoutProgramExtra
    {
        return WorkoutProgramExtra::create(array_merge([
            'program_id' => $program->id,
            'type' => 'core',
            'exercise_name' => 'Plank',
            'sets' => 3,
            'reps_or_time' => '30s',
            'rest' => '30s',
        ], $attributes));
    }

    private function createAssignment(WorkoutProgram $program, array $attributes = []): WorkoutProgramAssignment
    {
        $member = $this->createMember();

        return WorkoutProgramAssignment::create(array_merge([
            'member_id' => $member->id,
            'source_program_id' => $program->id,
            'assigned_program_id' => $program->id,
            'effective_date' => now()->toDateString(),
        ], $attributes));
    }

    // -------------------------------------------------------------------------
    // Exercises
    // -------------------------------------------------------------------------

    public function testExercisesIndexReturnsPaginatedList(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $exercise = $this->createExercise();

        $this->getJson('/api/exercises')
            ->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonFragment(['id' => $exercise->id]);
    }

    public function testExercisesStoreCreatesExerciseWithVariations(): void
    {
        $this->actingAsUser(['workouts.manage']);

        $response = $this->postJson('/api/exercises', [
            'name' => 'Bench Press',
            'status' => 'active',
            'default_sets' => 4,
            'default_reps' => '8',
            'default_tempo' => '3-1-1',
            'default_rest' => 90,
            'variations' => [
                ['variation_name' => 'Close Grip'],
                ['variation_name' => 'Wide Grip'],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Exercise created successfully.');

        $this->assertDatabaseHas('exercises', [
            'name' => 'Bench Press',
            'status' => 'active',
        ]);
    }

    public function testExercisesStoreReturns422ForMissingRequiredFields(): void
    {
        $this->actingAsUser(['workouts.manage']);

        $this->postJson('/api/exercises', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'status', 'default_sets', 'default_reps', 'default_tempo', 'default_rest']);
    }

    public function testExercisesShowReturnsExercise(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $exercise = $this->createExercise(['name' => 'Squat']);

        $this->getJson('/api/exercises/' . $exercise->id)
            ->assertOk()
            ->assertJsonPath('data.id', $exercise->id)
            ->assertJsonPath('data.name', 'Squat');
    }

    public function testExercisesUpdateUpdatesExercise(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $exercise = $this->createExercise(['name' => 'Deadlift']);

        $this->putJson('/api/exercises/' . $exercise->id, [
            'name' => 'Romanian Deadlift',
            'status' => 'active',
            'default_sets' => 3,
            'default_reps' => '12',
            'default_tempo' => '2-0-2',
            'default_rest' => 75,
        ])->assertOk()->assertJsonPath('message', 'Exercise updated successfully.');

        $this->assertDatabaseHas('exercises', [
            'id' => $exercise->id,
            'name' => 'Romanian Deadlift',
        ]);
    }

    public function testExercisesDestroyDeletesExercise(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $exercise = $this->createExercise();

        $this->deleteJson('/api/exercises/' . $exercise->id)
            ->assertOk()
            ->assertJsonPath('message', 'Exercise deleted successfully.');

        $this->assertDatabaseMissing('exercises', ['id' => $exercise->id]);
    }

    public function testExercisesRequireWorkoutsManagePermission(): void
    {
        $this->actingAsUser([]);

        $this->getJson('/api/exercises')->assertForbidden();
        $this->postJson('/api/exercises', [])->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Workout Programs
    // -------------------------------------------------------------------------

    public function testWorkoutProgramsIndexReturnsPaginatedList(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $program = $this->createProgram();

        $this->getJson('/api/workout-programs')
            ->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonFragment(['id' => $program->id]);
    }

    public function testWorkoutProgramsStoreCreatesProgram(): void
    {
        $this->actingAsUser(['workouts.manage']);

        $response = $this->postJson('/api/workout-programs', [
            'title' => 'Strength Builder',
            'description' => '12-week strength program',
            'duration_weeks' => 12,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Workout program created successfully.');

        $this->assertDatabaseHas('workout_programs', [
            'title' => 'Strength Builder',
            'duration_weeks' => 12,
        ]);
    }

    public function testWorkoutProgramsStoreReturns422ForInvalidData(): void
    {
        $this->actingAsUser(['workouts.manage']);

        $this->postJson('/api/workout-programs', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'duration_weeks']);
    }

    public function testWorkoutProgramsShowReturnsFullProgram(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $program = $this->createProgram(['title' => 'Full Body']);

        $this->getJson('/api/workout-programs/' . $program->id)
            ->assertOk()
            ->assertJsonPath('data.id', $program->id)
            ->assertJsonPath('data.title', 'Full Body');
    }

    public function testWorkoutProgramsUpdateUpdatesProgram(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $program = $this->createProgram(['title' => 'Old Title']);

        $this->putJson('/api/workout-programs/' . $program->id, [
            'title' => 'New Title',
            'description' => 'Updated description',
            'duration_weeks' => 8,
        ])->assertOk()->assertJsonPath('message', 'Workout program updated successfully.');

        $this->assertDatabaseHas('workout_programs', [
            'id' => $program->id,
            'title' => 'New Title',
            'duration_weeks' => 8,
        ]);
    }

    public function testWorkoutProgramsDestroyDeletesProgram(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $program = $this->createProgram();

        $this->deleteJson('/api/workout-programs/' . $program->id)
            ->assertOk()
            ->assertJsonPath('message', 'Workout program deleted successfully.');

        $this->assertDatabaseMissing('workout_programs', ['id' => $program->id]);
    }

    public function testWorkoutProgramCustomerViewReturnsStructuredData(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $program = $this->createProgram(['title' => 'Customer View Program']);
        $day = $this->createDay($program, ['day_number' => 1, 'title' => 'Push Day']);
        $exercise = $this->createExercise(['name' => 'Push Up']);
        $this->createDayExercise($day, $exercise);

        $this->getJson('/api/workout-programs/' . $program->id . '/customer-view')
            ->assertOk()
            ->assertJsonPath('data.programTitle', 'Customer View Program')
            ->assertJsonStructure(['data' => ['programTitle', 'duration', 'days', 'core', 'cardio']]);
    }

    // -------------------------------------------------------------------------
    // Program Days
    // -------------------------------------------------------------------------

    public function testWorkoutProgramDaysAddUpdateDestroy(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $program = $this->createProgram();

        // Add day
        $addResponse = $this->postJson('/api/workout-programs/' . $program->id . '/days', [
            'day_number' => 1,
            'title' => 'Leg Day',
        ]);

        $addResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Workout day added successfully.');

        $dayId = (int) $addResponse->json('data.id');

        $this->assertDatabaseHas('workout_program_days', [
            'id' => $dayId,
            'program_id' => $program->id,
            'title' => 'Leg Day',
        ]);

        // Update day
        $this->putJson('/api/workout-program-days/' . $dayId, [
            'day_number' => 1,
            'title' => 'Leg & Glute Day',
        ])->assertOk()->assertJsonPath('message', 'Workout day updated successfully.');

        $this->assertDatabaseHas('workout_program_days', [
            'id' => $dayId,
            'title' => 'Leg & Glute Day',
        ]);

        // Destroy day
        $this->deleteJson('/api/workout-program-days/' . $dayId)
            ->assertOk()
            ->assertJsonPath('message', 'Workout day deleted successfully.');

        $this->assertDatabaseMissing('workout_program_days', ['id' => $dayId]);
    }

    public function testAddDayReturns422ForDuplicateDayNumber(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $program = $this->createProgram();

        $this->postJson('/api/workout-programs/' . $program->id . '/days', [
            'day_number' => 1,
            'title' => 'Day One',
        ])->assertCreated();

        $this->postJson('/api/workout-programs/' . $program->id . '/days', [
            'day_number' => 1,
            'title' => 'Day One Again',
        ])->assertStatus(422)->assertJsonValidationErrors(['day_number']);
    }

    // -------------------------------------------------------------------------
    // Day Exercises
    // -------------------------------------------------------------------------

    public function testWorkoutDayExercisesAddUpdateDestroy(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $program = $this->createProgram();
        $day = $this->createDay($program, ['day_number' => 1]);
        $exercise = $this->createExercise(['name' => 'Pull Up']);

        // Add exercise to day
        $addResponse = $this->postJson('/api/workout-program-days/' . $day->id . '/exercises', [
            'exercise_id' => $exercise->id,
            'sets' => 4,
            'reps' => '8',
            'tempo' => '3-1-1',
            'rest_seconds' => 90,
            'exercise_order' => 1,
        ]);

        $addResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Workout day exercise added successfully.');

        $dayExerciseId = (int) $addResponse->json('data.id');

        $this->assertDatabaseHas('workout_day_exercises', [
            'id' => $dayExerciseId,
            'day_id' => $day->id,
            'exercise_id' => $exercise->id,
            'sets' => 4,
        ]);

        // Update day exercise
        $this->putJson('/api/workout-day-exercises/' . $dayExerciseId, [
            'exercise_id' => $exercise->id,
            'sets' => 5,
            'reps' => '6',
            'tempo' => '4-1-1',
            'rest_seconds' => 120,
            'exercise_order' => 1,
        ])->assertOk()->assertJsonPath('message', 'Workout day exercise updated successfully.');

        $this->assertDatabaseHas('workout_day_exercises', [
            'id' => $dayExerciseId,
            'sets' => 5,
        ]);

        // Destroy day exercise
        $this->deleteJson('/api/workout-day-exercises/' . $dayExerciseId)
            ->assertOk()
            ->assertJsonPath('message', 'Workout day exercise deleted successfully.');

        $this->assertDatabaseMissing('workout_day_exercises', ['id' => $dayExerciseId]);
    }

    public function testAddDayExerciseReturns422ForMissingFields(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $program = $this->createProgram();
        $day = $this->createDay($program, ['day_number' => 1]);

        $this->postJson('/api/workout-program-days/' . $day->id . '/exercises', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['exercise_id', 'sets', 'reps', 'tempo', 'rest_seconds', 'exercise_order']);
    }

    // -------------------------------------------------------------------------
    // Extras (core & cardio)
    // -------------------------------------------------------------------------

    public function testWorkoutProgramCoreExtraAddUpdateDestroy(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $program = $this->createProgram();

        // Add core extra
        $addResponse = $this->postJson('/api/workout-programs/' . $program->id . '/extras', [
            'type' => 'core',
            'exercise_name' => 'Plank',
            'sets' => 3,
            'reps_or_time' => '30s',
            'rest' => '20s',
            'notes' => 'Keep body straight',
        ]);

        $addResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Workout program extra added successfully.');

        $extraId = (int) $addResponse->json('data.id');

        $this->assertDatabaseHas('workout_program_extras', [
            'id' => $extraId,
            'program_id' => $program->id,
            'type' => 'core',
            'exercise_name' => 'Plank',
        ]);

        // Update extra
        $this->putJson('/api/workout-program-extras/' . $extraId, [
            'type' => 'core',
            'exercise_name' => 'Side Plank',
            'sets' => 4,
            'reps_or_time' => '20s',
            'rest' => '15s',
        ])->assertOk()->assertJsonPath('message', 'Workout program extra updated successfully.');

        $this->assertDatabaseHas('workout_program_extras', [
            'id' => $extraId,
            'exercise_name' => 'Side Plank',
        ]);

        // Destroy extra
        $this->deleteJson('/api/workout-program-extras/' . $extraId)
            ->assertOk()
            ->assertJsonPath('message', 'Workout program extra deleted successfully.');

        $this->assertDatabaseMissing('workout_program_extras', ['id' => $extraId]);
    }

    public function testWorkoutProgramCardioExtraAddAndUpdate(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $program = $this->createProgram();

        $addResponse = $this->postJson('/api/workout-programs/' . $program->id . '/extras', [
            'type' => 'cardio',
            'frequency_per_week' => 3,
            'duration_minutes' => 30,
            'cardio_type' => 'Cycling',
            'notes' => 'Moderate intensity',
        ]);

        $addResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Workout program extra added successfully.');

        $extraId = (int) $addResponse->json('data.id');

        $this->assertDatabaseHas('workout_program_extras', [
            'id' => $extraId,
            'type' => 'cardio',
            'cardio_type' => 'Cycling',
        ]);

        $this->putJson('/api/workout-program-extras/' . $extraId, [
            'type' => 'cardio',
            'frequency_per_week' => 4,
            'duration_minutes' => 45,
            'cardio_type' => 'Running',
        ])->assertOk()->assertJsonPath('message', 'Workout program extra updated successfully.');

        $this->assertDatabaseHas('workout_program_extras', [
            'id' => $extraId,
            'cardio_type' => 'Running',
        ]);
    }

    public function testAddExtraReturns422ForInvalidType(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $program = $this->createProgram();

        $this->postJson('/api/workout-programs/' . $program->id . '/extras', [
            'type' => 'invalid',
        ])->assertStatus(422)->assertJsonValidationErrors(['type']);
    }

    // -------------------------------------------------------------------------
    // Assignments
    // -------------------------------------------------------------------------

    public function testWorkoutAssignmentIndexReturnsPaginatedList(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $program = $this->createProgram();
        $assignment = $this->createAssignment($program);

        $this->getJson('/api/workout-program-assignments')
            ->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonFragment(['id' => $assignment->id]);
    }

    public function testWorkoutAssignmentMembersRouteReturnsSearchableList(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $this->createMember(null, ['name' => 'Assignable Member']);

        $this->getJson('/api/workout-program-assignment-members')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function testWorkoutAssignmentStoreCreatesAssignmentForMembers(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $program = $this->createProgram(['title' => 'Assignment Plan']);
        $memberA = $this->createMember();
        $memberB = $this->createMember();

        $response = $this->postJson('/api/workout-program-assignments', [
            'program_id' => $program->id,
            'member_ids' => [$memberA->id, $memberB->id],
            'effective_date' => now()->toDateString(),
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Workout program assignments created successfully.')
            ->assertJsonPath('data.count', 2);

        $this->assertDatabaseHas('workout_program_assignments', [
            'member_id' => $memberA->id,
            'source_program_id' => $program->id,
        ]);

        $this->assertDatabaseHas('workout_program_assignments', [
            'member_id' => $memberB->id,
        ]);
    }

    public function testWorkoutAssignmentStoreReturns422ForMissingFields(): void
    {
        $this->actingAsUser(['workouts.manage']);

        $this->postJson('/api/workout-program-assignments', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['program_id', 'member_ids', 'effective_date']);
    }

    public function testWorkoutAssignmentUpdateUpdatesAssignment(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $program = $this->createProgram();
        $member = $this->createMember();
        $assignment = WorkoutProgramAssignment::create([
            'member_id' => $member->id,
            'source_program_id' => $program->id,
            'assigned_program_id' => $program->id,
            'effective_date' => now()->subDays(5)->toDateString(),
        ]);

        $newProgram = $this->createProgram(['title' => 'Updated Plan']);
        $newDate = now()->toDateString();

        $this->putJson('/api/workout-program-assignments/' . $assignment->id, [
            'program_id' => $newProgram->id,
            'member_id' => $member->id,
            'effective_date' => $newDate,
        ])->assertOk()->assertJsonPath('message', 'Workout program assignment updated successfully.');

        $this->assertDatabaseHas('workout_program_assignments', [
            'id' => $assignment->id,
            'source_program_id' => $newProgram->id,
        ]);
    }

    public function testWorkoutAssignmentDestroyDeletesAssignment(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $program = $this->createProgram();
        $assignment = $this->createAssignment($program);

        $this->deleteJson('/api/workout-program-assignments/' . $assignment->id)
            ->assertOk()
            ->assertJsonPath('message', 'Workout program assignment deleted successfully.');

        $this->assertDatabaseMissing('workout_program_assignments', ['id' => $assignment->id]);
    }

    public function testStoreMemberWorkoutWithUploadedPdfFile(): void
    {
        $disk = (string) config('filesystems.media_disk', 'public');
        Storage::fake($disk);
        $this->actingAsUser(['workouts.manage', 'members.view']);
        $member = $this->createMember();

        $file = UploadedFile::fake()->create('custom_routine.pdf', 500, 'application/pdf');

        $response = $this->post('/api/members/' . $member->id . '/workouts', [
            'type' => 'file',
            'title' => 'Custom 4-Day PDF Split',
            'effective_date' => '2026-08-15',
            'file' => $file,
            'notes' => 'Trainer specific PDF notes',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'file')
            ->assertJsonPath('data.title', 'Custom 4-Day PDF Split')
            ->assertJsonPath('data.file_name', 'custom_routine.pdf');

        $this->assertDatabaseHas('workout_program_assignments', [
            'member_id' => $member->id,
            'type' => 'file',
            'title' => 'Custom 4-Day PDF Split',
            'file_name' => 'custom_routine.pdf',
        ]);
    }

    public function testStoreMemberWorkoutWithRichFormattedText(): void
    {
        $this->actingAsUser(['workouts.manage', 'members.view']);
        $member = $this->createMember();

        $htmlContent = '<h3>Day 1: Chest & Triceps</h3><ul><li>Bench Press 4x10</li><li>Tricep Dips 3x12</li></ul>';

        $response = $this->postJson('/api/members/' . $member->id . '/workouts', [
            'type' => 'text',
            'title' => 'Hypertrophy Block A',
            'effective_date' => '2026-08-15',
            'formatted_text' => $htmlContent,
            'notes' => 'Rest 90s between sets',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'text')
            ->assertJsonPath('data.title', 'Hypertrophy Block A')
            ->assertJsonPath('data.formatted_text', $htmlContent);

        $this->assertDatabaseHas('workout_program_assignments', [
            'member_id' => $member->id,
            'type' => 'text',
            'title' => 'Hypertrophy Block A',
            'formatted_text' => $htmlContent,
        ]);
    }

    public function testShowWorkoutAssignmentReturnsDetails(): void
    {
        $this->actingAsUser(['workouts.manage']);
        $program = $this->createProgram(['title' => 'Powerlifting 101']);
        $assignment = $this->createAssignment($program);

        $this->getJson('/api/workout-program-assignments/' . $assignment->id)
            ->assertOk()
            ->assertJsonPath('data.id', $assignment->id)
            ->assertJsonPath('data.assigned_program_title', 'Powerlifting 101');
    }

    public function testMemberWorkoutsListReturnsAllTypes(): void
    {
        $this->actingAsUser(['workouts.manage', 'members.view']);
        $member = $this->createMember();
        $program = $this->createProgram(['title' => 'Strength Base']);

        $this->createAssignment($program, ['member_id' => $member->id]);

        WorkoutProgramAssignment::create([
            'member_id' => $member->id,
            'type' => 'text',
            'title' => 'Custom Core Protocol',
            'effective_date' => '2026-08-14',
            'formatted_text' => '<p>Plank 3x60s</p>',
        ]);

        $response = $this->getJson('/api/members/' . $member->id . '/workouts');

        $response->assertOk()
            ->assertJsonCount(2, 'data.data');
    }
}
