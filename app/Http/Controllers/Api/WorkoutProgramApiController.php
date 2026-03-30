<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Workout\StoreWorkoutDayExerciseRequest;
use App\Http\Requests\Api\Workout\StoreWorkoutProgramDayRequest;
use App\Http\Requests\Api\Workout\StoreWorkoutProgramExtraRequest;
use App\Http\Requests\Api\Workout\StoreWorkoutProgramRequest;
use App\Http\Requests\Api\Workout\UpdateWorkoutDayExerciseRequest;
use App\Http\Requests\Api\Workout\UpdateWorkoutProgramDayRequest;
use App\Http\Requests\Api\Workout\UpdateWorkoutProgramExtraRequest;
use App\Http\Requests\Api\Workout\UpdateWorkoutProgramRequest;
use App\Models\WorkoutDayExercise;
use App\Models\WorkoutProgram;
use App\Models\WorkoutProgramDay;
use App\Models\WorkoutProgramExtra;
use App\Services\WorkoutProgramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkoutProgramApiController extends Controller
{
    public function __construct(private readonly WorkoutProgramService $workoutProgramService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 10), 50);

        return $this->success(
            'Workout programs fetched successfully.',
            $this->workoutProgramService->programs(app('tenant')->id, $perPage)
        );
    }

    public function store(StoreWorkoutProgramRequest $request): JsonResponse
    {
        $program = $this->workoutProgramService->storeProgram(
            app('tenant')->id,
            $request->user()?->id,
            $request->validated()
        );

        return $this->success('Workout program created successfully.', [
            'id' => $program->id,
        ], 201);
    }

    public function show(WorkoutProgram $program): JsonResponse
    {
        return $this->success(
            'Workout program fetched successfully.',
            $this->workoutProgramService->fullProgram($program, app('tenant')->id)
        );
    }

    public function update(UpdateWorkoutProgramRequest $request, WorkoutProgram $program): JsonResponse
    {
        $this->workoutProgramService->updateProgram($program, app('tenant')->id, $request->validated());

        return $this->success('Workout program updated successfully.');
    }

    public function destroy(WorkoutProgram $program): JsonResponse
    {
        $this->workoutProgramService->destroyProgram($program, app('tenant')->id);

        return $this->success('Workout program deleted successfully.');
    }

    public function addDay(StoreWorkoutProgramDayRequest $request, WorkoutProgram $program): JsonResponse
    {
        $day = $this->workoutProgramService->addDay($program, app('tenant')->id, $request->validated());

        return $this->success('Workout day added successfully.', [
            'id' => $day->id,
        ], 201);
    }

    public function updateDay(UpdateWorkoutProgramDayRequest $request, WorkoutProgramDay $day): JsonResponse
    {
        $this->workoutProgramService->updateDay($day, app('tenant')->id, $request->validated());

        return $this->success('Workout day updated successfully.');
    }

    public function destroyDay(WorkoutProgramDay $day): JsonResponse
    {
        $this->workoutProgramService->destroyDay($day, app('tenant')->id);

        return $this->success('Workout day deleted successfully.');
    }

    public function addDayExercise(StoreWorkoutDayExerciseRequest $request, WorkoutProgramDay $day): JsonResponse
    {
        $dayExercise = $this->workoutProgramService->addDayExercise($day, app('tenant')->id, $request->validated());

        return $this->success('Workout day exercise added successfully.', [
            'id' => $dayExercise->id,
        ], 201);
    }

    public function updateDayExercise(UpdateWorkoutDayExerciseRequest $request, WorkoutDayExercise $dayExercise): JsonResponse
    {
        $this->workoutProgramService->updateDayExercise($dayExercise, app('tenant')->id, $request->validated());

        return $this->success('Workout day exercise updated successfully.');
    }

    public function destroyDayExercise(WorkoutDayExercise $dayExercise): JsonResponse
    {
        $this->workoutProgramService->destroyDayExercise($dayExercise, app('tenant')->id);

        return $this->success('Workout day exercise deleted successfully.');
    }

    public function addExtra(StoreWorkoutProgramExtraRequest $request, WorkoutProgram $program): JsonResponse
    {
        $extra = $this->workoutProgramService->addExtra($program, app('tenant')->id, $request->validated());

        return $this->success('Workout program extra added successfully.', [
            'id' => $extra->id,
        ], 201);
    }

    public function updateExtra(UpdateWorkoutProgramExtraRequest $request, WorkoutProgramExtra $extra): JsonResponse
    {
        $this->workoutProgramService->updateExtra($extra, app('tenant')->id, $request->validated());

        return $this->success('Workout program extra updated successfully.');
    }

    public function destroyExtra(WorkoutProgramExtra $extra): JsonResponse
    {
        $this->workoutProgramService->destroyExtra($extra, app('tenant')->id);

        return $this->success('Workout program extra deleted successfully.');
    }

    public function customerView(WorkoutProgram $program): JsonResponse
    {
        return $this->success(
            'Customer workout view fetched successfully.',
            $this->workoutProgramService->getCustomerView($program->id)
        );
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
