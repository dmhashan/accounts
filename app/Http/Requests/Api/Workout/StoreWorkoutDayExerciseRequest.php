<?php

namespace App\Http\Requests\Api\Workout;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutDayExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exercise_id' => ['required', 'integer', 'exists:exercises,id'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'w1_w3_exercise' => ['required', 'string', 'max:255'],
            'w2_w4_exercise' => ['required', 'string', 'max:255'],
            'sets' => ['required', 'integer', 'gt:0'],
            'reps' => ['required', 'string', 'max:100'],
            'tempo' => ['required', 'regex:/^\\d+-\\d+-\\d+-\\d+$/'],
            'rest_seconds' => ['required', 'integer', 'min:0'],
            'exercise_order' => ['required', 'integer', 'min:1'],
        ];
    }
}
