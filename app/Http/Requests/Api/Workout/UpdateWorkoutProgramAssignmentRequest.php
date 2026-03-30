<?php

namespace App\Http\Requests\Api\Workout;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkoutProgramAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'program_id' => ['required', 'integer', 'exists:workout_programs,id'],
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'effective_date' => ['required', 'date'],
            'program_title_override' => ['nullable', 'string', 'max:255'],
            'program_description_override' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
