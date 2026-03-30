<?php

namespace App\Http\Requests\Api\Workout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkoutProgramExtraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = (string) $this->input('type');

        return [
            'type' => ['required', Rule::in(['core', 'cardio'])],
            'exercise_name' => [Rule::requiredIf($type === 'core'), 'nullable', 'string', 'max:255'],
            'sets' => [Rule::requiredIf($type === 'core'), 'nullable', 'integer', 'min:1'],
            'reps_or_time' => [Rule::requiredIf($type === 'core'), 'nullable', 'string', 'max:255'],
            'rest' => [Rule::requiredIf($type === 'core'), 'nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'frequency_per_week' => [Rule::requiredIf($type === 'cardio'), 'nullable', 'integer', 'min:1', 'max:14'],
            'duration_minutes' => [Rule::requiredIf($type === 'cardio'), 'nullable', 'integer', 'min:1'],
            'cardio_type' => [Rule::requiredIf($type === 'cardio'), 'nullable', 'string', 'max:255'],
        ];
    }
}
