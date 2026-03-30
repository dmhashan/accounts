<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\WorkoutDayExercise;
use App\Models\WorkoutProgram;
use App\Models\WorkoutProgramDay;
use App\Models\WorkoutProgramExtra;

class WorkoutProgramService
{
    public function exercises(int $tenantId, int $perPage): array
    {
        $exercises = Exercise::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->paginate($perPage);

        return [
            'data' => collect($exercises->items())->map(fn (Exercise $exercise) => $this->serializeExercise($exercise)),
            'meta' => [
                'current_page' => $exercises->currentPage(),
                'last_page' => $exercises->lastPage(),
                'per_page' => $exercises->perPage(),
                'total' => $exercises->total(),
            ],
        ];
    }

    public function showExercise(Exercise $exercise, int $tenantId): array
    {
        $this->ensureExerciseTenant($exercise, $tenantId);

        return $this->serializeExercise($exercise);
    }

    public function storeExercise(int $tenantId, array $validated): Exercise
    {
        return Exercise::create([
            'tenant_id' => $tenantId,
            'name' => trim($validated['name']),
            'muscle_group' => trim($validated['muscle_group']),
            'category' => $validated['category'],
            'equipment' => filled($validated['equipment'] ?? null) ? trim((string) $validated['equipment']) : null,
            'difficulty' => $validated['difficulty'],
            'description' => filled($validated['description'] ?? null) ? trim((string) $validated['description']) : null,
            'status' => $validated['status'],
        ]);
    }

    public function updateExercise(Exercise $exercise, int $tenantId, array $validated): void
    {
        $this->ensureExerciseTenant($exercise, $tenantId);

        $exercise->update([
            'name' => trim($validated['name']),
            'muscle_group' => trim($validated['muscle_group']),
            'category' => $validated['category'],
            'equipment' => filled($validated['equipment'] ?? null) ? trim((string) $validated['equipment']) : null,
            'difficulty' => $validated['difficulty'],
            'description' => filled($validated['description'] ?? null) ? trim((string) $validated['description']) : null,
            'status' => $validated['status'],
        ]);
    }

    public function destroyExercise(Exercise $exercise, int $tenantId): void
    {
        $this->ensureExerciseTenant($exercise, $tenantId);
        $exercise->delete();
    }

    public function programs(int $tenantId, int $perPage): array
    {
        $programs = WorkoutProgram::query()
            ->where('tenant_id', $tenantId)
            ->withCount('days', 'extras')
            ->with('creator:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => collect($programs->items())->map(fn (WorkoutProgram $program) => [
                'id' => $program->id,
                'title' => $program->title,
                'description' => $program->description,
                'duration_weeks' => (int) $program->duration_weeks,
                'days_per_week' => (int) $program->days_per_week,
                'level' => $program->level,
                'status' => $program->status,
                'created_by' => $program->created_by,
                'created_by_name' => $program->creator?->name,
                'days_count' => $program->days_count,
                'extras_count' => $program->extras_count,
                'created_at' => optional($program->created_at)->format('Y-m-d H:i'),
            ]),
            'meta' => [
                'current_page' => $programs->currentPage(),
                'last_page' => $programs->lastPage(),
                'per_page' => $programs->perPage(),
                'total' => $programs->total(),
            ],
        ];
    }

    public function storeProgram(int $tenantId, ?int $createdBy, array $validated): WorkoutProgram
    {
        return WorkoutProgram::create([
            'tenant_id' => $tenantId,
            'title' => trim($validated['title']),
            'description' => filled($validated['description'] ?? null) ? trim((string) $validated['description']) : null,
            'duration_weeks' => $validated['duration_weeks'],
            'days_per_week' => $validated['days_per_week'],
            'level' => $validated['level'],
            'status' => $validated['status'],
            'created_by' => $createdBy,
        ]);
    }

    public function updateProgram(WorkoutProgram $program, int $tenantId, array $validated): void
    {
        $this->ensureProgramTenant($program, $tenantId);

        $program->update([
            'title' => trim($validated['title']),
            'description' => filled($validated['description'] ?? null) ? trim((string) $validated['description']) : null,
            'duration_weeks' => $validated['duration_weeks'],
            'days_per_week' => $validated['days_per_week'],
            'level' => $validated['level'],
            'status' => $validated['status'],
        ]);
    }

    public function destroyProgram(WorkoutProgram $program, int $tenantId): void
    {
        $this->ensureProgramTenant($program, $tenantId);
        $program->delete();
    }

    public function addDay(WorkoutProgram $program, int $tenantId, array $validated): WorkoutProgramDay
    {
        $this->ensureProgramTenant($program, $tenantId);

        return $program->days()->create([
            'day_number' => $validated['day_number'],
            'title' => trim($validated['title']),
        ]);
    }

    public function updateDay(WorkoutProgramDay $day, int $tenantId, array $validated): void
    {
        $this->ensureDayTenant($day, $tenantId);

        $day->update([
            'day_number' => $validated['day_number'],
            'title' => trim($validated['title']),
        ]);
    }

    public function destroyDay(WorkoutProgramDay $day, int $tenantId): void
    {
        $this->ensureDayTenant($day, $tenantId);
        $day->delete();
    }

    public function addDayExercise(WorkoutProgramDay $day, int $tenantId, array $validated): WorkoutDayExercise
    {
        $this->ensureDayTenant($day, $tenantId);
        $this->ensureExerciseIdBelongsToTenant((int) $validated['exercise_id'], $tenantId);

        return $day->dayExercises()->create([
            'exercise_id' => $validated['exercise_id'],
            'display_name' => filled($validated['display_name'] ?? null) ? trim((string) $validated['display_name']) : null,
            'w1_w3_exercise' => trim($validated['w1_w3_exercise']),
            'w2_w4_exercise' => trim($validated['w2_w4_exercise']),
            'sets' => $validated['sets'],
            'reps' => trim($validated['reps']),
            'tempo' => trim($validated['tempo']),
            'rest_seconds' => $validated['rest_seconds'],
            'exercise_order' => $validated['exercise_order'],
        ]);
    }

    public function updateDayExercise(WorkoutDayExercise $dayExercise, int $tenantId, array $validated): void
    {
        $this->ensureDayExerciseTenant($dayExercise, $tenantId);
        $this->ensureExerciseIdBelongsToTenant((int) $validated['exercise_id'], $tenantId);

        $dayExercise->update([
            'exercise_id' => $validated['exercise_id'],
            'display_name' => filled($validated['display_name'] ?? null) ? trim((string) $validated['display_name']) : null,
            'w1_w3_exercise' => trim($validated['w1_w3_exercise']),
            'w2_w4_exercise' => trim($validated['w2_w4_exercise']),
            'sets' => $validated['sets'],
            'reps' => trim($validated['reps']),
            'tempo' => trim($validated['tempo']),
            'rest_seconds' => $validated['rest_seconds'],
            'exercise_order' => $validated['exercise_order'],
        ]);
    }

    public function destroyDayExercise(WorkoutDayExercise $dayExercise, int $tenantId): void
    {
        $this->ensureDayExerciseTenant($dayExercise, $tenantId);
        $dayExercise->delete();
    }

    public function addExtra(WorkoutProgram $program, int $tenantId, array $validated): WorkoutProgramExtra
    {
        $this->ensureProgramTenant($program, $tenantId);

        return $program->extras()->create($this->normalizeExtraPayload($validated));
    }

    public function updateExtra(WorkoutProgramExtra $extra, int $tenantId, array $validated): void
    {
        $this->ensureExtraTenant($extra, $tenantId);
        $extra->update($this->normalizeExtraPayload($validated));
    }

    public function destroyExtra(WorkoutProgramExtra $extra, int $tenantId): void
    {
        $this->ensureExtraTenant($extra, $tenantId);
        $extra->delete();
    }

    public function fullProgram(WorkoutProgram $program, int $tenantId): array
    {
        $this->ensureProgramTenant($program, $tenantId);

        $program->load([
            'creator:id,name',
            'days' => fn ($query) => $query->orderBy('day_number'),
            'days.dayExercises' => fn ($query) => $query->orderBy('exercise_order'),
            'days.dayExercises.exercise:id,name,muscle_group,category,difficulty,status',
            'extras',
        ]);

        return [
            'id' => $program->id,
            'title' => $program->title,
            'description' => $program->description,
            'duration_weeks' => (int) $program->duration_weeks,
            'days_per_week' => (int) $program->days_per_week,
            'level' => $program->level,
            'status' => $program->status,
            'created_by' => $program->created_by,
            'created_by_name' => $program->creator?->name,
            'days' => $program->days->map(function (WorkoutProgramDay $day) {
                return [
                    'id' => $day->id,
                    'day_number' => (int) $day->day_number,
                    'title' => $day->title,
                    'exercises' => $day->dayExercises->map(function (WorkoutDayExercise $item) {
                        return [
                            'id' => $item->id,
                            'exercise_id' => $item->exercise_id,
                            'exercise_name' => $item->exercise?->name,
                            'display_name' => $item->display_name,
                            'w1_w3_exercise' => $item->w1_w3_exercise,
                            'w2_w4_exercise' => $item->w2_w4_exercise,
                            'sets' => (int) $item->sets,
                            'reps' => $item->reps,
                            'tempo' => $item->tempo,
                            'rest_seconds' => (int) $item->rest_seconds,
                            'exercise_order' => (int) $item->exercise_order,
                        ];
                    })->values(),
                ];
            })->values(),
            'extras' => $program->extras->map(fn (WorkoutProgramExtra $extra) => $this->serializeExtra($extra))->values(),
            'created_at' => optional($program->created_at)->format('Y-m-d H:i'),
            'updated_at' => optional($program->updated_at)->format('Y-m-d H:i'),
        ];
    }

    public function getCustomerView($programId): array
    {
        $tenantId = app('tenant')->id;

        $program = WorkoutProgram::query()
            ->where('tenant_id', $tenantId)
            ->with([
                'days' => fn ($query) => $query->orderBy('day_number'),
                'days.dayExercises' => fn ($query) => $query->orderBy('exercise_order'),
                'days.dayExercises.exercise:id,name',
                'extras',
            ])
            ->find($programId);

        if (!$program) {
            abort(404);
        }

        return [
            'programTitle' => $program->title,
            'duration' => $program->duration_weeks.' weeks',
            'days' => $program->days->map(function (WorkoutProgramDay $day) {
                return [
                    'day' => 'Day '.str_pad((string) $day->day_number, 2, '0', STR_PAD_LEFT),
                    'title' => $day->title,
                    'exercises' => $day->dayExercises->map(function (WorkoutDayExercise $item) {
                        return [
                            'name' => $item->display_name ?: ($item->exercise?->name ?? 'Exercise'),
                            'W1/W3' => $item->w1_w3_exercise,
                            'W2/W4' => $item->w2_w4_exercise,
                            'sets' => (int) $item->sets,
                            'reps' => $item->reps,
                            'tempo' => $item->tempo,
                            'rest' => (int) $item->rest_seconds.'s',
                        ];
                    })->values(),
                ];
            })->values(),
            'core' => $program->extras
                ->where('type', 'core')
                ->values()
                ->map(fn (WorkoutProgramExtra $extra) => [
                    'exercise_name' => $extra->exercise_name,
                    'sets' => $extra->sets,
                    'reps_or_time' => $extra->reps_or_time,
                    'rest' => $extra->rest,
                    'notes' => $extra->notes,
                ]),
            'cardio' => $program->extras
                ->where('type', 'cardio')
                ->values()
                ->map(fn (WorkoutProgramExtra $extra) => [
                    'frequency_per_week' => $extra->frequency_per_week,
                    'duration_minutes' => $extra->duration_minutes,
                    'type' => $extra->cardio_type,
                    'notes' => $extra->notes,
                ]),
        ];
    }

    private function serializeExercise(Exercise $exercise): array
    {
        return [
            'id' => $exercise->id,
            'name' => $exercise->name,
            'muscle_group' => $exercise->muscle_group,
            'category' => $exercise->category,
            'equipment' => $exercise->equipment,
            'difficulty' => $exercise->difficulty,
            'description' => $exercise->description,
            'status' => $exercise->status,
            'created_at' => optional($exercise->created_at)->format('Y-m-d H:i'),
            'updated_at' => optional($exercise->updated_at)->format('Y-m-d H:i'),
        ];
    }

    private function serializeExtra(WorkoutProgramExtra $extra): array
    {
        return [
            'id' => $extra->id,
            'type' => $extra->type,
            'exercise_name' => $extra->exercise_name,
            'sets' => $extra->sets !== null ? (int) $extra->sets : null,
            'reps_or_time' => $extra->reps_or_time,
            'rest' => $extra->rest,
            'notes' => $extra->notes,
            'frequency_per_week' => $extra->frequency_per_week !== null ? (int) $extra->frequency_per_week : null,
            'duration_minutes' => $extra->duration_minutes !== null ? (int) $extra->duration_minutes : null,
            'cardio_type' => $extra->cardio_type,
        ];
    }

    private function normalizeExtraPayload(array $validated): array
    {
        $type = $validated['type'];

        if ($type === 'core') {
            return [
                'type' => 'core',
                'exercise_name' => trim((string) $validated['exercise_name']),
                'sets' => $validated['sets'],
                'reps_or_time' => trim((string) $validated['reps_or_time']),
                'rest' => trim((string) $validated['rest']),
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
                'frequency_per_week' => null,
                'duration_minutes' => null,
                'cardio_type' => null,
            ];
        }

        return [
            'type' => 'cardio',
            'exercise_name' => null,
            'sets' => null,
            'reps_or_time' => null,
            'rest' => null,
            'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            'frequency_per_week' => $validated['frequency_per_week'],
            'duration_minutes' => $validated['duration_minutes'],
            'cardio_type' => trim((string) $validated['cardio_type']),
        ];
    }

    private function ensureProgramTenant(WorkoutProgram $program, int $tenantId): void
    {
        if ($program->tenant_id !== $tenantId) {
            abort(404);
        }
    }

    private function ensureDayTenant(WorkoutProgramDay $day, int $tenantId): void
    {
        $program = WorkoutProgram::query()
            ->where('id', $day->program_id)
            ->where('tenant_id', $tenantId)
            ->exists();

        if (!$program) {
            abort(404);
        }
    }

    private function ensureDayExerciseTenant(WorkoutDayExercise $dayExercise, int $tenantId): void
    {
        $exists = WorkoutDayExercise::query()
            ->where('id', $dayExercise->id)
            ->whereHas('day.program', fn ($query) => $query->where('tenant_id', $tenantId))
            ->exists();

        if (!$exists) {
            abort(404);
        }
    }

    private function ensureExtraTenant(WorkoutProgramExtra $extra, int $tenantId): void
    {
        $exists = WorkoutProgramExtra::query()
            ->where('id', $extra->id)
            ->whereHas('program', fn ($query) => $query->where('tenant_id', $tenantId))
            ->exists();

        if (!$exists) {
            abort(404);
        }
    }

    private function ensureExerciseTenant(Exercise $exercise, int $tenantId): void
    {
        if ($exercise->tenant_id !== $tenantId) {
            abort(404);
        }
    }

    private function ensureExerciseIdBelongsToTenant(int $exerciseId, int $tenantId): void
    {
        $exists = Exercise::query()
            ->where('id', $exerciseId)
            ->where('tenant_id', $tenantId)
            ->exists();

        if (!$exists) {
            abort(422, 'Invalid exercise selection.');
        }
    }
}
