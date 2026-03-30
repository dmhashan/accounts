<?php

namespace App\Http\Requests\Api\Workout;

use App\Models\WorkoutProgram;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkoutProgramDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var WorkoutProgram $program */
        $program = $this->route('program');

        return [
            'day_number' => [
                'required',
                'integer',
                'min:1',
                'max:7',
                Rule::unique('workout_program_days', 'day_number')->where(
                    fn ($query) => $query->where('program_id', $program->id)
                ),
            ],
            'title' => ['required', 'string', 'max:255'],
        ];
    }
}
