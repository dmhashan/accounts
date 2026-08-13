<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\ExerciseVariation;
use App\Models\Member;
use App\Models\WorkoutDayExercise;
use App\Models\WorkoutProgram;
use App\Models\WorkoutProgramAssignment;
use App\Models\WorkoutProgramDay;
use App\Models\WorkoutProgramExtra;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class WorkoutProgramService
{
    public function __construct(
        private readonly MediaStorageService $media,
    ) {}

    public function assignmentMembers(int $tenantId, int $perPage, string $search = ''): array
    {
        $members = Member::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('member_id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage);

        return [
            'data' => collect($members->items())->map(fn (Member $member) => [
                'id' => $member->id,
                'member_id' => $member->biometric_member_id,
                'name' => $member->name,
                'email' => $member->email,
                'phone_number' => $member->phone_number,
            ]),
            'meta' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
        ];
    }

    public function memberAssignments(int $memberId, int $tenantId, int $perPage): array
    {
        $assignments = WorkoutProgramAssignment::query()
            ->where('member_id', $memberId)
            ->with([
                'sourceProgram:id,title',
                'assignedProgram:id,title',
                'creator:id,name',
            ])
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->paginate($perPage);

        return [
            'data' => collect($assignments->items())->map(fn (WorkoutProgramAssignment $assignment) => $this->serializeAssignment($assignment)),
            'meta' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'per_page' => $assignments->perPage(),
                'total' => $assignments->total(),
            ],
        ];
    }

    public function programAssignments(int $tenantId, int $perPage): array
    {
        $assignments = WorkoutProgramAssignment::query()
            ->with([
                'member:id,name,biometric_member_id,email,phone_number',
                'sourceProgram:id,title',
                'assignedProgram:id,title',
                'creator:id,name',
            ])
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->paginate($perPage);

        return [
            'data' => collect($assignments->items())->map(fn (WorkoutProgramAssignment $assignment) => $this->serializeAssignment($assignment)),
            'meta' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'per_page' => $assignments->perPage(),
                'total' => $assignments->total(),
            ],
        ];
    }

    public function storeProgramAssignments(int $tenantId, ?int $createdBy, array $validated): array
    {
        $sourceProgram = WorkoutProgram::query()->findOrFail((int) $validated['program_id']);
        $this->ensureProgramTenant($sourceProgram, $tenantId);

        $assignedProgram = $this->resolveAssignedProgramWithSnapshot($sourceProgram, $tenantId, $createdBy, $validated);

        $created = [];

        foreach ($validated['member_ids'] as $memberId) {
            $member = Member::query()->findOrFail((int) $memberId);
            $this->ensureMemberTenant($member, $tenantId);

            $created[] = WorkoutProgramAssignment::query()->create([
                'member_id' => $member->id,
                'source_program_id' => $sourceProgram->id,
                'assigned_program_id' => $assignedProgram->id,
                'effective_date' => $validated['effective_date'],
                'created_by' => $createdBy,
            ]);
        }

        return [
            'count' => count($created),
            'ids' => collect($created)->pluck('id')->values(),
        ];
    }

    public function updateProgramAssignment(WorkoutProgramAssignment $assignment, int $tenantId, ?int $updatedBy, array $validated): void
    {
        $this->ensureAssignmentTenant($assignment, $tenantId);

        $sourceProgram = WorkoutProgram::query()->findOrFail((int) $validated['program_id']);
        $this->ensureProgramTenant($sourceProgram, $tenantId);

        $member = Member::query()->findOrFail((int) $validated['member_id']);
        $this->ensureMemberTenant($member, $tenantId);

        $assignedProgram = $this->resolveAssignedProgramWithSnapshot($sourceProgram, $tenantId, $updatedBy, $validated);

        $assignment->update([
            'member_id' => $member->id,
            'source_program_id' => $sourceProgram->id,
            'assigned_program_id' => $assignedProgram->id,
            'effective_date' => $validated['effective_date'],
        ]);
    }

    public function storeMemberWorkout(Member $member, int $tenantId, ?int $createdBy, array $validated, ?UploadedFile $file = null): WorkoutProgramAssignment
    {
        $this->ensureMemberTenant($member, $tenantId);
        $type = $validated['type'] ?? (isset($validated['program_id']) ? 'program' : 'text');

        $title = filled($validated['title'] ?? null) ? trim((string) $validated['title']) : '';

        if ($type === 'file') {
            if (!$file) {
                throw new \InvalidArgumentException('A workout file is required.');
            }

            if ($title === '') {
                $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $title = ucwords(trim(str_replace(['_', '-'], ' ', $base))) ?: 'Workout File';
            }

            $path = $this->media->store($file, "members/{$member->id}/workouts");

            return WorkoutProgramAssignment::create([
                'member_id' => $member->id,
                'type' => 'file',
                'title' => $title,
                'effective_date' => $validated['effective_date'],
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
                'created_by' => $createdBy,
            ]);
        }

        if ($type === 'text') {
            if ($title === '') {
                $title = 'Workout Routine - ' . \Carbon\Carbon::parse($validated['effective_date'])->format('d M Y');
            }

            return WorkoutProgramAssignment::create([
                'member_id' => $member->id,
                'type' => 'text',
                'title' => $title,
                'effective_date' => $validated['effective_date'],
                'formatted_text' => $validated['formatted_text'] ?? '',
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
                'created_by' => $createdBy,
            ]);
        }

        // Program assignment
        $sourceProgram = WorkoutProgram::query()->findOrFail((int) $validated['program_id']);
        $this->ensureProgramTenant($sourceProgram, $tenantId);

        $assignedProgram = $this->resolveAssignedProgramWithSnapshot($sourceProgram, $tenantId, $createdBy, $validated);

        return WorkoutProgramAssignment::create([
            'member_id' => $member->id,
            'type' => 'program',
            'title' => trim($validated['title'] ?? '') ?: null,
            'source_program_id' => $sourceProgram->id,
            'assigned_program_id' => $assignedProgram->id,
            'effective_date' => $validated['effective_date'],
            'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            'created_by' => $createdBy,
        ]);
    }

    public function showAssignment(WorkoutProgramAssignment $assignment, int $tenantId): array
    {
        $this->ensureAssignmentTenant($assignment, $tenantId);
        $assignment->load([
            'member:id,name,biometric_member_id,email,phone_number',
            'creator:id,name',
            'sourceProgram:id,title',
            'assignedProgram.creator:id,name',
            'assignedProgram.days.dayExercises.exercise:id,name',
            'assignedProgram.extras',
        ]);

        $data = $this->serializeAssignment($assignment);

        if (($assignment->type === 'program' || !$assignment->type) && $assignment->assignedProgram) {
            $data['program_details'] = $this->fullProgram($assignment->assignedProgram, $tenantId);
        }

        return $data;
    }

    public function destroyProgramAssignment(WorkoutProgramAssignment $assignment, int $tenantId): void
    {
        $this->ensureAssignmentTenant($assignment, $tenantId);

        if ($assignment->file_path) {
            $this->media->delete($assignment->file_path);
        }
        $assignment->delete();
    }

    public function exercises(int $tenantId, int $perPage): array
    {
        $exercises = Exercise::query()
            ->with('variations')
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
        $exercise->load('variations');

        return $this->serializeExercise($exercise);
    }

    public function storeExercise(int $tenantId, array $validated): Exercise
    {
        $exercise = Exercise::create([
            'name' => trim($validated['name']),
            'status' => $validated['status'],
            'default_sets' => (int) $validated['default_sets'],
            'default_reps' => trim((string) $validated['default_reps']),
            'default_tempo' => trim((string) $validated['default_tempo']),
            'default_rest' => (int) $validated['default_rest'],
        ]);

        $this->syncExerciseVariations($exercise, $validated['variations'] ?? []);

        return $exercise;
    }

    public function updateExercise(Exercise $exercise, int $tenantId, array $validated): void
    {
        $this->ensureExerciseTenant($exercise, $tenantId);

        $exercise->update([
            'name' => trim($validated['name']),
            'status' => $validated['status'],
            'default_sets' => (int) $validated['default_sets'],
            'default_reps' => trim((string) $validated['default_reps']),
            'default_tempo' => trim((string) $validated['default_tempo']),
            'default_rest' => (int) $validated['default_rest'],
        ]);

        $this->syncExerciseVariations($exercise, $validated['variations'] ?? []);
    }

    public function destroyExercise(Exercise $exercise, int $tenantId): void
    {
        $this->ensureExerciseTenant($exercise, $tenantId);
        $exercise->delete();
    }

    public function programs(int $tenantId, int $perPage): array
    {
        $programs = WorkoutProgram::query()
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
            'title' => trim($validated['title']),
            'description' => filled($validated['description'] ?? null) ? trim((string) $validated['description']) : null,
            'duration_weeks' => $validated['duration_weeks'],
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
            'w1_w3_exercise' => trim((string) ($validated['w1_w3_exercise'] ?? '')),
            'w2_w4_exercise' => trim((string) ($validated['w2_w4_exercise'] ?? '')),
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
            'w1_w3_exercise' => trim((string) ($validated['w1_w3_exercise'] ?? '')),
            'w2_w4_exercise' => trim((string) ($validated['w2_w4_exercise'] ?? '')),
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
            'days.dayExercises.exercise:id,name,status',
            'extras',
        ]);

        return [
            'id' => $program->id,
            'title' => $program->title,
            'description' => $program->description,
            'duration_weeks' => (int) $program->duration_weeks,
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
            'duration' => $program->duration_weeks . ' weeks',
            'days' => $program->days->map(function (WorkoutProgramDay $day) {
                return [
                    'day' => 'Day ' . str_pad((string) $day->day_number, 2, '0', STR_PAD_LEFT),
                    'title' => $day->title,
                    'exercises' => $day->dayExercises->map(function (WorkoutDayExercise $item) {
                        return [
                            'name' => $item->exercise?->name ?? 'Exercise',
                            'W1/W3' => $item->w1_w3_exercise,
                            'W2/W4' => $item->w2_w4_exercise,
                            'sets' => (int) $item->sets,
                            'reps' => $item->reps,
                            'tempo' => $item->tempo,
                            'rest' => (int) $item->rest_seconds . 's',
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
            'status' => $exercise->status,
            'default_sets' => (int) $exercise->default_sets,
            'default_reps' => $exercise->default_reps,
            'default_tempo' => $exercise->default_tempo,
            'default_rest' => (int) $exercise->default_rest,
            'variations' => $exercise->variations->map(fn (ExerciseVariation $variation) => [
                'id' => $variation->id,
                'variation_name' => $variation->variation_name,
            ])->values(),
            'created_at' => optional($exercise->created_at)->format('Y-m-d H:i'),
            'updated_at' => optional($exercise->updated_at)->format('Y-m-d H:i'),
        ];
    }

    private function syncExerciseVariations(Exercise $exercise, array $variationPayload): void
    {
        $existingIds = $exercise->variations()->pluck('id')->all();
        $keepIds = [];

        foreach ($variationPayload as $variation) {
            $data = [
                'variation_name' => trim((string) $variation['variation_name']),
            ];

            $variationId = isset($variation['id']) ? (int) $variation['id'] : null;

            if ($variationId) {
                $row = $exercise->variations()->where('id', $variationId)->first();

                if ($row) {
                    $row->update($data);
                    $keepIds[] = $variationId;
                }
                continue;
            }

            $created = $exercise->variations()->create($data);
            $keepIds[] = $created->id;
        }

        $deleteIds = array_diff($existingIds, $keepIds);

        if (!empty($deleteIds)) {
            $exercise->variations()->whereIn('id', $deleteIds)->delete();
        }
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

    private function serializeAssignment(WorkoutProgramAssignment $assignment): array
    {
        $type = $assignment->type ?: 'program';
        $title = $assignment->title ?: ($assignment->assignedProgram?->title ?? $assignment->sourceProgram?->title ?? 'Workout Plan');

        return [
            'id' => $assignment->id,
            'member_id' => $assignment->member_id,
            'member_name' => $assignment->member?->name,
            'member_code' => $assignment->member?->biometric_member_id,
            'member_email' => $assignment->member?->email,
            'member_phone' => $assignment->member?->phone_number,
            'type' => $type,
            'title' => $title,
            'source_program_id' => $assignment->source_program_id,
            'source_program_title' => $assignment->sourceProgram?->title,
            'assigned_program_id' => $assignment->assigned_program_id,
            'assigned_program_title' => $assignment->assignedProgram?->title,
            'file_path' => $assignment->file_path,
            'file_name' => $assignment->file_name,
            'mime_type' => $assignment->mime_type,
            'file_size' => $assignment->file_size,
            'file_url' => $assignment->file_path ? $this->media->url($assignment->file_path) : null,
            'formatted_text' => $assignment->formatted_text,
            'notes' => $assignment->notes,
            'effective_date' => optional($assignment->effective_date)->format('Y-m-d'),
            'created_by' => $assignment->created_by,
            'created_by_name' => $assignment->creator?->name,
            'created_at' => optional($assignment->created_at)->format('Y-m-d H:i'),
        ];
    }

    private function resolveAssignedProgramWithSnapshot(WorkoutProgram $sourceProgram, int $tenantId, ?int $createdBy, array $payload): WorkoutProgram
    {
        $titleOverride = trim((string) ($payload['program_title_override'] ?? ''));
        $descriptionOverrideRaw = $payload['program_description_override'] ?? null;
        $descriptionOverride = $descriptionOverrideRaw === null ? null : trim((string) $descriptionOverrideRaw);

        $sourceTitle = trim((string) $sourceProgram->title);
        $sourceDescription = $sourceProgram->description === null ? null : trim((string) $sourceProgram->description);

        $isTitleModified = $titleOverride !== '' && $titleOverride !== $sourceTitle;
        $isDescriptionModified = $descriptionOverride !== $sourceDescription;

        if (!$isTitleModified && !$isDescriptionModified) {
            return $sourceProgram;
        }

        $sourceProgram->load([
            'days' => fn ($query) => $query->orderBy('day_number'),
            'days.dayExercises' => fn ($query) => $query->orderBy('exercise_order'),
            'extras',
        ]);

        $timestamp = Carbon::now()->format('Ymd_His');
        $baseTitle = $titleOverride !== '' ? $titleOverride : $sourceTitle;
        $snapshotTitle = sprintf('%s (%s)', $baseTitle, $timestamp);

        $clonedProgram = WorkoutProgram::query()->create([
            'title' => $snapshotTitle,
            'description' => $descriptionOverride,
            'duration_weeks' => $sourceProgram->duration_weeks,
            'created_by' => $createdBy,
        ]);

        foreach ($sourceProgram->days as $day) {
            $clonedDay = $clonedProgram->days()->create([
                'day_number' => $day->day_number,
                'title' => $day->title,
            ]);

            foreach ($day->dayExercises as $exercise) {
                $clonedDay->dayExercises()->create([
                    'exercise_id' => $exercise->exercise_id,
                    'w1_w3_exercise' => $exercise->w1_w3_exercise,
                    'w2_w4_exercise' => $exercise->w2_w4_exercise,
                    'sets' => $exercise->sets,
                    'reps' => $exercise->reps,
                    'tempo' => $exercise->tempo,
                    'rest_seconds' => $exercise->rest_seconds,
                    'exercise_order' => $exercise->exercise_order,
                ]);
            }
        }

        foreach ($sourceProgram->extras as $extra) {
            $clonedProgram->extras()->create([
                'type' => $extra->type,
                'exercise_name' => $extra->exercise_name,
                'sets' => $extra->sets,
                'reps_or_time' => $extra->reps_or_time,
                'rest' => $extra->rest,
                'notes' => $extra->notes,
                'frequency_per_week' => $extra->frequency_per_week,
                'duration_minutes' => $extra->duration_minutes,
                'cardio_type' => $extra->cardio_type,
            ]);
        }

        return $clonedProgram;
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

    private function ensureProgramTenant(WorkoutProgram $program, int $tenantId): void {}

    private function ensureDayTenant(WorkoutProgramDay $day, int $tenantId): void
    {
        $program = WorkoutProgram::query()
            ->where('id', $day->program_id)
            ->exists();

        if (!$program) {
            abort(404);
        }
    }

    private function ensureDayExerciseTenant(WorkoutDayExercise $dayExercise, int $tenantId): void
    {
        $exists = WorkoutDayExercise::query()
            ->where('id', $dayExercise->id)
            ->whereHas('day.program', fn ($query) => $query)
            ->exists();

        if (!$exists) {
            abort(404);
        }
    }

    private function ensureExtraTenant(WorkoutProgramExtra $extra, int $tenantId): void
    {
        $exists = WorkoutProgramExtra::query()
            ->where('id', $extra->id)
            ->whereHas('program', fn ($query) => $query)
            ->exists();

        if (!$exists) {
            abort(404);
        }
    }

    private function ensureExerciseTenant(Exercise $exercise, int $tenantId): void {}

    private function ensureExerciseIdBelongsToTenant(int $exerciseId, int $tenantId): void
    {
        $exists = Exercise::query()
            ->where('id', $exerciseId)
            ->exists();

        if (!$exists) {
            abort(422, 'Invalid exercise selection.');
        }
    }

    private function ensureAssignmentTenant(WorkoutProgramAssignment $assignment, int $tenantId): void {}

    private function ensureMemberTenant(Member $member, int $tenantId): void {}
}
