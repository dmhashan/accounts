<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Workout\StoreExerciseRequest;
use App\Http\Requests\Api\Workout\UpdateExerciseRequest;
use App\Models\Exercise;
use App\Services\WorkoutProgramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExerciseApiController extends Controller
{
    public function __construct(private readonly WorkoutProgramService $workoutProgramService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 10), 50);

        return $this->success(
            'Exercises fetched successfully.',
            $this->workoutProgramService->exercises(app('tenant')->id, $perPage),
        );
    }

    public function store(StoreExerciseRequest $request): JsonResponse
    {
        $exercise = $this->workoutProgramService->storeExercise(app('tenant')->id, $request->validated());

        return $this->success('Exercise created successfully.', [
            'id' => $exercise->id,
        ], 201);
    }

    public function show(Exercise $exercise): JsonResponse
    {
        return $this->success(
            'Exercise fetched successfully.',
            $this->workoutProgramService->showExercise($exercise, app('tenant')->id),
        );
    }

    public function update(UpdateExerciseRequest $request, Exercise $exercise): JsonResponse
    {
        $this->workoutProgramService->updateExercise($exercise, app('tenant')->id, $request->validated());

        return $this->success('Exercise updated successfully.');
    }

    public function destroy(Exercise $exercise): JsonResponse
    {
        $this->workoutProgramService->destroyExercise($exercise, app('tenant')->id);

        return $this->success('Exercise deleted successfully.');
    }

    private function success(string $message, mixed $data = null, int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'error' => false,
            'statusCode' => $statusCode,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
}
