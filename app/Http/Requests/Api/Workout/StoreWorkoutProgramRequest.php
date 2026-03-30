<?php

namespace App\Http\Requests\Api\Workout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkoutProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_weeks' => ['required', 'integer', 'min:1', 'max:52'],
            'days_per_week' => ['required', 'integer', 'min:1', 'max:7'],
            'level' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
